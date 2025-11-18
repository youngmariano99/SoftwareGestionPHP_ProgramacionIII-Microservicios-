<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client; // <-- Importamos Guzzle
use App\ConexionMongo;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/conexionMySQL.php';


// Función helper para registrar en el servicio de auditoría
// (Recuerda importar "use GuzzleHttp\Client;" al inicio de tu archivo)
function registrarLog($usuario_id, $sucursal_id, $tipo_accion, $descripcion) {
    try {
        $clienteAuditoria = new Client();
        // Usamos el nombre del servicio de Docker: 'auditorias'
        $clienteAuditoria->post('http://auditorias/logs', [
            'json' => [
                'usuario_id' => $usuario_id,
                'sucursal_id' => $sucursal_id,
                'tipo_accion' => $tipo_accion,
                'descripcion' => $descripcion
            ],
            'timeout' => 2 // 2 segundos de timeout
        ]);
    } catch (\Exception $e) {
        // Si el servicio de auditoría falla, no detenemos la venta.
        // Solo lo registramos en el log de errores del contenedor.
        error_log("Fallo al registrar log de auditoría: " . $e->getMessage());
    }
}
// --- ¡NUEVO! Cargar variables de entorno ---
// Carga el .env desde la raíz del servicio (un nivel arriba de /public)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app = AppFactory::create();

// ---- Endpoint para REGISTRAR una venta ----
// POST /ventas
$app->post('/ventas', function (Request $request, Response $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validamos datos básicos (sucursal_id es nuevo requisito de tu tabla SQL)
    // || empty($data['sucursal_id'])
    if (empty($data['id_empleado']) || empty($data['productos'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos (falta sucursal o productos)']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $clienteApiProductos = new Client();
    $pdo = getDbConnection(); // Conexión MySQL


    // Obtenemos los datos para el log ANTES del try
    $idEmpleado = $data['id_empleado'];
    $idSucursal = $data['sucursal_id'];
    
    try {
        // 1. Iniciamos transacción MySQL (Todo o nada)
        $pdo->beginTransaction();

        $totalVenta = 0;
        $itemsParaInsertar = []; // Guardamos datos aquí temporalmente

        // 2. Procesar Productos (Consultando a MongoDB vía API interna)
        foreach ($data['productos'] as $prodVenta) {
            $idMongo = $prodVenta['id'];
            $cantidad = (int) $prodVenta['cantidad'];

            // A. Pedir datos al microservicio de productos (Mongo)
            $res = $clienteApiProductos->get("http://productos/productos/{$idMongo}");
            $productoMongo = json_decode($res->getBody()->getContents(), true);

            // B. Validaciones
            if ($productoMongo['stock'] < $cantidad) {
                throw new Exception("Stock insuficiente para: " . $productoMongo['nombre']);
            }

            // C. Cálculos para SQL
            $precioUnitario = (float) $productoMongo['precio'];
            $subtotal = $precioUnitario * $cantidad;
            $totalVenta += $subtotal;

            // Guardamos esto para insertarlo después en MySQL
            $itemsParaInsertar[] = [
                'producto_id' => $idMongo,
                'cantidad' => $cantidad,
                'precio' => $precioUnitario,
                'subtotal' => $subtotal
            ];

            // D. Descontar stock en MongoDB (inmediato)
            $nuevoStock = $productoMongo['stock'] - $cantidad;
            $clienteApiProductos->put("http://productos/productos/{$idMongo}/stock", [
                'json' => ['stock' => $nuevoStock]
            ]);
        }

        // 3. Insertar Cabecera en MySQL (`ventas`)
        // Nota: 'sucursal_id' y 'monto_total' son requeridos según tu SQL
        //$data['sucursal_id']
        $stmtVenta = $pdo->prepare("INSERT INTO ventas (usuario_id, sucursal_id, monto_total, fecha_venta) VALUES (?, ?, ?, NOW())");
        $stmtVenta->execute([
            $data['id_empleado'], 
            1, 
            $totalVenta
        ]);
        
        $idVentaMySQL = $pdo->lastInsertId(); // ¡Recuperamos el ID generado por MySQL!

        // 4. Insertar Items en MySQL (`items_venta`)
        $stmtItem = $pdo->prepare("INSERT INTO items_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");

        foreach ($itemsParaInsertar as $item) {
            $stmtItem->execute([
                $idVentaMySQL,
                $item['producto_id'], // ID de Mongo (string)
                $item['cantidad'],
                $item['precio'],
                $item['subtotal']
            ]);
        }

        // 5. Confirmar transacción MySQL
        $pdo->commit();


        $descripcionLog = "Venta #" . $idVentaMySQL . " registrada. Monto: $" . $totalVenta;
        registrarLog($idEmpleado, $idSucursal, 'NUEVA_VENTA', $descripcionLog);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Venta registrada en MySQL y stock actualizado en Mongo',
            'id_venta' => $idVentaMySQL
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        // Si algo falla, deshacemos los cambios en MySQL
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // ¡FALLO! Registramos el log de error
        registrarLog($idEmpleado, $idSucursal, 'FALLO_VENTA', $e->getMessage());

        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// ---- Endpoint para LISTAR todos los productos ----
// GET /productos
// GET /ventas
$app->get('/ventas', function (Request $request, Response $response) {
    
    $pdo = getDbConnection();
    $clienteApiProductos = new Client(); // Guzzle

    try {
        // 1. Traer TODO el catálogo de productos (para tener los nombres)
        // Hacemos esto primero para tener un "diccionario" listo.
        $resProductos = $clienteApiProductos->get('http://productos/productos');
        $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
        
        // Optimizamos: Convertimos la lista en un mapa [ID => Nombre] para búsqueda rápida
        $mapaNombres = [];
        foreach ($listaProductos as $prod) {
            $mapaNombres[$prod['id']] = $prod['nombre'];
        }

        // 2. Traer las ventas y sus items de MySQL
        // Usamos un JOIN para traer todo en una sola consulta
        $sql = "SELECT 
                    v.id as venta_id, v.fecha_venta, v.monto_total, v.usuario_id, 
                    iv.producto_id, iv.cantidad, iv.precio_unitario, iv.subtotal
                FROM ventas v
                JOIN items_venta iv ON v.id = iv.venta_id
                ORDER BY v.fecha_venta DESC";
        
        $stmt = $pdo->query($sql);
        $filas = $stmt->fetchAll();

        // 3. Agrupar filas (porque el JOIN repite la cabecera por cada item)
        $ventasAgrupadas = [];

        foreach ($filas as $fila) {
            $idVenta = $fila['venta_id'];

            // Si es la primera vez que vemos esta venta, creamos la cabecera
            if (!isset($ventasAgrupadas[$idVenta])) {
                $ventasAgrupadas[$idVenta] = [
                    '_id' => $fila['venta_id'], // Mantenemos _id para compatibilidad con tu front
                    'fecha' => $fila['fecha_venta'],
                    'total' => $fila['monto_total'],
                    'usuario_id' => $fila['usuario_id'],
                    'productos' => [] // Aquí meteremos los items
                ];
            }

            // 4. "PEGAR" EL NOMBRE (La Fusión)
            // Usamos el mapa que creamos en el paso 1
            $idProd = $fila['producto_id'];
            $nombreProducto = $mapaNombres[$idProd] ?? 'Producto Desconocido'; // Fallback si no existe

            // Agregamos el item a la lista de productos de esa venta
            $ventasAgrupadas[$idVenta]['productos'][] = [
                'id_producto' => $idProd,
                'nombre' => $nombreProducto, // ¡Aquí está el dato que querías!
                'cantidad' => $fila['cantidad'],
                'precio' => $fila['precio_unitario'],
                'subtotal' => $fila['subtotal']
            ];
        }

        // Convertimos el array asociativo a una lista indexada para el JSON final
        $resultadoFinal = array_values($ventasAgrupadas);

        $response->getBody()->write(json_encode($resultadoFinal));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {

        
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// --- REPORTE 1: Historial de ventas de una Sucursal ---
$app->get('/ventas/sucursal/{id}', function (Request $request, Response $response, array $args) {
    $idSucursal = $args['id'];
    $pdo = getDbConnection();
    $clienteApiProductos = new Client();

    try {
        // 1. Traer diccionario de productos (Mongo)
        $resProductos = $clienteApiProductos->get('http://productos/productos');
        $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
        $mapaNombres = [];
        foreach ($listaProductos as $prod) {
            $mapaNombres[$prod['id']] = $prod['nombre'];
        }

        // 2. Traer ventas FILTRADAS por sucursal (MySQL)
        $sql = "SELECT 
                    v.id as venta_id, v.fecha_venta, v.monto_total, v.usuario_id, 
                    iv.producto_id, iv.cantidad, iv.precio_unitario, iv.subtotal
                FROM ventas v
                JOIN items_venta iv ON v.id = iv.venta_id
                WHERE v.sucursal_id = ?  -- <-- ¡EL FILTRO CLAVE!
                ORDER BY v.fecha_venta DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idSucursal]);
        $filas = $stmt->fetchAll();

        // 3. Agrupar y Pegar Nombres (Igual que en el listado general)
        $ventasAgrupadas = [];
        foreach ($filas as $fila) {
            $idVenta = $fila['venta_id'];
            if (!isset($ventasAgrupadas[$idVenta])) {
                $ventasAgrupadas[$idVenta] = [
                    '_id' => $fila['venta_id'],
                    'fecha' => $fila['fecha_venta'],
                    'total' => $fila['monto_total'],
                    'usuario_id' => $fila['usuario_id'],
                    'productos' => []
                ];
            }
            $idProd = $fila['producto_id'];
            $ventasAgrupadas[$idVenta]['productos'][] = [
                'nombre' => $mapaNombres[$idProd] ?? 'Desconocido',
                'cantidad' => $fila['cantidad'],
                'subtotal' => $fila['subtotal']
            ];
        }

        $response->getBody()->write(json_encode(array_values($ventasAgrupadas)));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// --- REPORTE 2: Top 5 Productos Más Vendidos ---
$app->get('/ventas/top-productos', function (Request $request, Response $response) {
    $pdo = getDbConnection();
    $clienteApiProductos = new Client();

    try {
        // 1. MySQL: Calcular Ranking (Suma de cantidades agrupada por producto)
        $sql = "SELECT producto_id, SUM(cantidad) as total_vendido 
                FROM items_venta 
                GROUP BY producto_id 
                ORDER BY total_vendido DESC 
                LIMIT 5";
        $stmt = $pdo->query($sql);
        $ranking = $stmt->fetchAll();

        // 2. Mongo: Traer nombres
        $resProductos = $clienteApiProductos->get('http://productos/productos');
        $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
        
        // Creamos mapa [ID => Nombre]
        $mapaNombres = [];
        foreach ($listaProductos as $prod) {
            $mapaNombres[$prod['id']] = $prod['nombre'];
        }

        // 3. Pegar Nombres al Ranking
        $resultadoFinal = [];
        foreach ($ranking as $item) {
            $idProd = $item['producto_id'];
            $resultadoFinal[] = [
                'producto_id' => $idProd,
                'nombre' => $mapaNombres[$idProd] ?? 'Desconocido',
                'total_vendido' => (float)$item['total_vendido'] // Aseguramos que sea número
            ];
        }

        $response->getBody()->write(json_encode($resultadoFinal));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- REPORTE 3: Ventas por Categoría (Híbrido) ---
$app->get('/ventas/por-categoria', function (Request $request, Response $response) {
    $pdo = getDbConnection();
    $clienteApiProductos = new Client();

    try {
        // 1. MySQL: Agrupar ventas por ID de producto
        // "Dime cuánto dinero generó cada ID"
        $sql = "SELECT producto_id, SUM(subtotal) as total_dinero 
                FROM items_venta 
                GROUP BY producto_id";
        $stmt = $pdo->query($sql);
        $ventasPorProducto = $stmt->fetchAll();

        // 2. Mongo: Traer catálogo para conocer las categorías
        $resProductos = $clienteApiProductos->get('http://productos/productos');
        $listaProductos = json_decode($resProductos->getBody()->getContents(), true);

        // Creamos un mapa [ID_Producto => Categoría]
        $mapaCategorias = [];
        foreach ($listaProductos as $prod) {
            // Si no tiene categoría, le ponemos 'General'
            $mapaCategorias[$prod['id']] = $prod['categoria'] ?? 'General';
        }

        // 3. PHP: La Gran Fusión (Agrupar por Categoría)
        $totalesPorCategoria = [];

        foreach ($ventasPorProducto as $venta) {
            $idProd = $venta['producto_id'];
            $monto = (float)$venta['total_dinero'];
            
            // Buscamos la categoría de este producto
            $cat = $mapaCategorias[$idProd] ?? 'Desconocido';

            // Sumamos al acumulador de esa categoría
            if (!isset($totalesPorCategoria[$cat])) {
                $totalesPorCategoria[$cat] = 0;
            }
            $totalesPorCategoria[$cat] += $monto;
        }

        // 4. Formatear para el Frontend
        // Convertimos el array asociativo en una lista de objetos limpia
        $resultadoFinal = [];
        foreach ($totalesPorCategoria as $cat => $total) {
            $resultadoFinal[] = [
                'categoria' => $cat,
                'total' => $total
            ];
        }

        $response->getBody()->write(json_encode($resultadoFinal));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();