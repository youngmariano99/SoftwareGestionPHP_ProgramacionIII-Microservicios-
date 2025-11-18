<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\ConexionMongo; // <-- Importamos nuestra clase de conexión
use GuzzleHttp\Client; // <-- ¡NUEVO!

require __DIR__ . '/../vendor/autoload.php';

// --- ¡NUEVO! Función helper ---
// (La misma que usamos en los otros servicios)
function registrarLog($usuario_id, $sucursal_id, $tipo_accion, $descripcion) {
    try {
        $clienteAuditoria = new Client();
        // Llamamos al servicio 'auditorias' (nombre de Docker)
        $clienteAuditoria->post('http://auditorias/logs', [ 
            'json' => [
                'usuario_id' => $usuario_id,
                'sucursal_id' => $sucursal_id,
                'tipo_accion' => $tipo_accion,
                'descripcion' => $descripcion
            ],
            'timeout' => 2 // No queremos que una falla de log retrase al usuario
        ]);
    } catch (\Exception $e) {
        // Si el servicio de auditoría falla, no detenemos la acción principal
        error_log("Fallo al registrar log: " . $e->getMessage());
    }
}

$app = AppFactory::create();

// ---- Endpoint para CREAR un producto ----
// Corresponde a tu requisito "crear productos"
// POST /productos
// ---- Endpoint para CREAR un producto (CON AUDITORÍA) ----
$app->post('/productos', function (Request $request, Response $response) {
    
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);
    
    // NOTA: Para que el log sea completo, el frontend (dashboardVendedor.php) 
    // debería enviar 'usuario_id' y 'sucursal_id' junto con los datos del producto.
    // Usamos '?? null' para que no falle si no los envía.
    $auth_user_id = $data['usuario_id'] ?? null; 
    $auth_sucursal_id = $data['sucursal_id'] ?? null;
    $categoria = $data['categoria'] ?? 'General';

    if (empty($data['nombre']) || empty($data['precio']) || !isset($data['stock'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $db = ConexionMongo::obtenerConexion();
        $coleccion = $db->selectCollection('productos');
        
        $resultado = $coleccion->insertOne([
            'nombre' => $data['nombre'],
            'precio' => (float) $data['precio'],
            'stock' => (int) $data['stock'],
            'categoria' => $categoria // <-- ¡AÑADIR ESTO!
        ]);
        
        $nuevoId = (string) $resultado->getInsertedId();

        // ¡ÉXITO! Registramos el log
        $desc = "Producto '{$data['nombre']}' (ID: $nuevoId) creado en categoría '{$categoria}' con stock {$data['stock']}.";
        registrarLog($auth_user_id, $auth_sucursal_id, 'CREAR_PRODUCTO_OK', $desc);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Producto creado',
            'id' => $nuevoId
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        
        // ¡FALLO! Registramos el log de error
        $errorMsg = "Error al crear producto '{$data['nombre']}': " . $e->getMessage();
        registrarLog($auth_user_id, $auth_sucursal_id, 'CREAR_PRODUCTO_ERROR', $errorMsg);

        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// ---- Endpoint para LISTAR todos los productos ----
// GET /productos
$app->get('/productos', function (Request $request, Response $response) {
    
    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');
    
    $cursor = $coleccion->find();
    
    $productos = [];
    foreach ($cursor as $documento) {
        // Convertimos el BSON a un array PHP más limpio
        $productos[] = [
            'id' => (string) $documento['_id'],
            'nombre' => $documento['nombre'],
            'precio' => $documento['precio'],
            'stock' => $documento['stock'],
            'categoria' => $documento['categoria'] ?? 'General'
        ];
    }
    
    $response->getBody()->write(json_encode($productos));
    return $response->withHeader('Content-Type', 'application/json');
});


// ---- Endpoint para ACTUALIZAR STOCK ----
// Corresponde a tu requisito "manejar stock"
// PUT /productos/{id}/stock
// ---- Endpoint para ACTUALIZAR STOCK (CON AUDITORÍA) ----
// (Este endpoint es llamado por el servicio_ventas)
$app->put('/productos/{id}/stock', function (Request $request, Response $response, array $args) {
    
    $idProducto = $args['id'];
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);
    
    if (!isset($data['stock'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Stock no especificado']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $nuevoStock = (int) $data['stock'];
    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');

    try {
        // Buscamos por el ObjectId de Mongo
        $resultado = $coleccion->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($idProducto)],
            ['$set' => ['stock' => $nuevoStock]]
        );

        if ($resultado->getMatchedCount() === 0) {
            
            // ¡FALLO LÓGICO!
            $errorMsg = "Fallo al actualizar stock: Producto ID '$idProducto' no encontrado.";
            // Nota: No tenemos usuario/sucursal, porque este log es 'hijo' de una venta
            registrarLog(null, null, 'STOCK_UPDATE_FALLO', $errorMsg);

            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // ¡ÉXITO!
        $desc = "Stock actualizado para ID '$idProducto'. Nuevo stock: $nuevoStock. (Acción gatillada por una venta)";
        registrarLog(null, null, 'STOCK_UPDATE_OK', $desc);

        $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Stock actualizado']));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\MongoDB\Exception\InvalidArgumentException $e) {
        
        // ¡FALLO DE EXCEPCIÓN!
        $errorMsg = "Error al actualizar stock para ID '$idProducto': " . $e->getMessage();
        registrarLog(null, null, 'STOCK_UPDATE_ERROR', $errorMsg);

        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'ID de producto inválido']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

// ---- Endpoint para OBTENER UN producto por ID ----
// GET /productos/{id}
$app->get('/productos/{id}', function (Request $request, Response $response, array $args) {
    
    $idProducto = $args['id'];

    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');

    try {
        $documento = $coleccion->findOne(['_id' => new \MongoDB\BSON\ObjectId($idProducto)]);

        if ($documento === null) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $producto = [
            'id' => (string) $documento['_id'],
            'nombre' => $documento['nombre'],
            'precio' => $documento['precio'],
            'stock' => $documento['stock']
        ];
        
        $response->getBody()->write(json_encode($producto));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\MongoDB\Exception\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'ID de producto inválido']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

// ---- Endpoint para ALERTAS DE STOCK BAJO ----
$app->get('/stock/bajo', function (Request $request, Response $response) {
    
    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');
    
    try {
        // ¡AQUÍ ESTÁ TU LÓGICA!
        // Buscamos productos con stock <= 5
        $cursor = $coleccion->find(['stock' => ['$lte' => 5]]);
        
        $productosBajos = [];
        foreach ($cursor as $documento) {
            $productosBajos[] = [
                'id' => (string) $documento['_id'],
                'nombre' => $documento['nombre'],
                'precio' => $documento['precio'],
                'stock' => $documento['stock']
            ];
        }
        
        $response->getBody()->write(json_encode($productosBajos));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// ---- Endpoint para EDITAR un producto (CON AUDITORÍA) ----
$app->put('/productos/{id}', function (Request $request, Response $response, array $args) {
    
    $idProducto = $args['id'];
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);

    // Datos para el log (si vienen del frontend)
    $auth_user_id = $data['usuario_id'] ?? null;
    $auth_sucursal_id = $data['sucursal_id'] ?? null;

    // 1. Construimos el paquete de actualización dinámicamente
    $datosParaActualizar = [];

    if (!empty($data['nombre'])) {
        $datosParaActualizar['nombre'] = $data['nombre'];
    }

    if (!empty($data['categoria'])) {
        $datosParaActualizar['categoria'] = $data['categoria'];
    }
    
    // Usamos isset() para permitir el valor 0
    if (isset($data['precio'])) {
        $datosParaActualizar['precio'] = (float) $data['precio'];
    }
    
    if (isset($data['stock'])) {
        $datosParaActualizar['stock'] = (int) $data['stock'];
    }

    

    // Si no hay nada que actualizar, devolvemos error
    if (empty($datosParaActualizar)) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'No se enviaron datos para actualizar']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');

    try {
        // 2. Ejecutamos la actualización
        $resultado = $coleccion->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($idProducto)],
            ['$set' => $datosParaActualizar]
        );

        if ($resultado->getMatchedCount() === 0) {
            // LOG FALLO
            registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_FALLO', "Producto ID $idProducto no encontrado para edición.");
            
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // LOG ÉXITO (Convertimos el array de cambios a string para que sea legible en el log)
        $cambios = json_encode($datosParaActualizar);
        registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_OK', "Producto ID $idProducto editado. Cambios: $cambios");

        $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Producto actualizado']));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        // LOG ERROR
        registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_ERROR', "Error al editar ID $idProducto: " . $e->getMessage());

        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Error interno: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// ---- Endpoint para LISTAR CATEGORÍAS ÚNICAS ----
$app->get('/categorias', function (Request $request, Response $response) {
    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');

    try {
        // "Dame todos los valores únicos del campo 'categoria'"
        $categorias = $coleccion->distinct('categoria');
        
        // Filtramos nulos y vacíos por si acaso
        $categorias = array_filter($categorias);
        
        $response->getBody()->write(json_encode(array_values($categorias)));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();