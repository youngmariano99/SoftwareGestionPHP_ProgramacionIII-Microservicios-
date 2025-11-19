<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;

class VentaController {

    // --- Helper Privado: Auditoría ---
    private function registrarLog($usuario_id, $sucursal_id, $tipo_accion, $descripcion) {
        try {
            $clienteAuditoria = new Client();
            $clienteAuditoria->post('http://auditorias/logs', [
                'json' => [
                    'usuario_id' => $usuario_id,
                    'sucursal_id' => $sucursal_id,
                    'tipo_accion' => $tipo_accion,
                    'descripcion' => $descripcion
                ],
                'timeout' => 2
            ]);
        } catch (\Exception $e) {
            error_log("Fallo al registrar log de auditoría: " . $e->getMessage());
        }
    }

    // POST /ventas
    public function registrar(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['id_empleado']) || empty($data['productos'])) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $clienteApiProductos = new Client();
        $pdo = \getDbConnection(); // Usamos la función global

        $idEmpleado = $data['id_empleado'];
        $idSucursal = $data['sucursal_id'] ?? 1; // Fallback por si no viene

        try {
            $pdo->beginTransaction();

            $totalVenta = 0;
            $itemsParaInsertar = [];

            foreach ($data['productos'] as $prodVenta) {
                $idMongo = $prodVenta['id'];
                $cantidad = (int) $prodVenta['cantidad'];

                // 1. Consultar Stock (Microservicio Productos)
                $res = $clienteApiProductos->get("http://productos/productos/{$idMongo}");
                $productoMongo = json_decode($res->getBody()->getContents(), true);

                if ($productoMongo['stock'] < $cantidad) {
                    throw new \Exception("Stock insuficiente para: " . $productoMongo['nombre']);
                }

                $precioUnitario = (float) $productoMongo['precio'];
                $subtotal = $precioUnitario * $cantidad;
                $totalVenta += $subtotal;

                $itemsParaInsertar[] = [
                    'producto_id' => $idMongo,
                    'cantidad' => $cantidad,
                    'precio' => $precioUnitario,
                    'subtotal' => $subtotal
                ];

                // 2. Descontar Stock
                $nuevoStock = $productoMongo['stock'] - $cantidad;
                $clienteApiProductos->put("http://productos/productos/{$idMongo}/stock", [
                    'json' => ['stock' => $nuevoStock]
                ]);
            }

            // 3. Insertar Venta (MySQL)
            $stmtVenta = $pdo->prepare("INSERT INTO ventas (usuario_id, sucursal_id, monto_total, fecha_venta) VALUES (?, ?, ?, NOW())");
            $stmtVenta->execute([$idEmpleado, $idSucursal, $totalVenta]);
            $idVentaMySQL = $pdo->lastInsertId();

            // 4. Insertar Items
            $stmtItem = $pdo->prepare("INSERT INTO items_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($itemsParaInsertar as $item) {
                $stmtItem->execute([
                    $idVentaMySQL, $item['producto_id'], $item['cantidad'], $item['precio'], $item['subtotal']
                ]);
            }

            $pdo->commit();

            $this->registrarLog($idEmpleado, $idSucursal, 'NUEVA_VENTA', "Venta #$idVentaMySQL registrada. Monto: $$totalVenta");

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'mensaje' => 'Venta registrada',
                'id_venta' => $idVentaMySQL
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->registrarLog($idEmpleado, $idSucursal, 'FALLO_VENTA', $e->getMessage());
            
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /ventas
    public function listar(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $clienteApiProductos = new Client();

            // 1. Traer diccionario de nombres (Mongo)
            $resProductos = $clienteApiProductos->get('http://productos/productos');
            $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
            $mapaNombres = array_column($listaProductos, 'nombre', 'id'); // Truco PHP para crear mapa ID=>Nombre rápido

            // 2. Traer ventas (MySQL)
            $sql = "SELECT v.id as venta_id, v.fecha_venta, v.monto_total, v.usuario_id, 
                           iv.producto_id, iv.cantidad, iv.precio_unitario, iv.subtotal
                    FROM ventas v
                    JOIN items_venta iv ON v.id = iv.venta_id
                    ORDER BY v.fecha_venta DESC";
            $stmt = $pdo->query($sql);
            $filas = $stmt->fetchAll();

            // 3. Agrupar
            $ventasAgrupadas = $this->agruparVentas($filas, $mapaNombres);

            $response->getBody()->write(json_encode(array_values($ventasAgrupadas)));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /ventas/sucursal/{id}
    public function porSucursal(Request $request, Response $response, array $args) {
        $idSucursal = $args['id'];
        try {
            $pdo = \getDbConnection();
            $clienteApiProductos = new Client();

            $resProductos = $clienteApiProductos->get('http://productos/productos');
            $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
            $mapaNombres = array_column($listaProductos, 'nombre', 'id');

            $sql = "SELECT v.id as venta_id, v.fecha_venta, v.monto_total, v.usuario_id, 
                           iv.producto_id, iv.cantidad, iv.precio_unitario, iv.subtotal
                    FROM ventas v
                    JOIN items_venta iv ON v.id = iv.venta_id
                    WHERE v.sucursal_id = ?
                    ORDER BY v.fecha_venta DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idSucursal]);
            $filas = $stmt->fetchAll();

            $ventasAgrupadas = $this->agruparVentas($filas, $mapaNombres);

            $response->getBody()->write(json_encode(array_values($ventasAgrupadas)));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /ventas/top-productos
    public function topProductos(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $clienteApiProductos = new Client();

            $sql = "SELECT producto_id, SUM(cantidad) as total_vendido 
                    FROM items_venta GROUP BY producto_id ORDER BY total_vendido DESC LIMIT 5";
            $stmt = $pdo->query($sql);
            $ranking = $stmt->fetchAll();

            $resProductos = $clienteApiProductos->get('http://productos/productos');
            $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
            $mapaNombres = array_column($listaProductos, 'nombre', 'id');

            $resultado = [];
            foreach ($ranking as $item) {
                $resultado[] = [
                    'producto_id' => $item['producto_id'],
                    'nombre' => $mapaNombres[$item['producto_id']] ?? 'Desconocido',
                    'total_vendido' => (float)$item['total_vendido']
                ];
            }

            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /ventas/por-categoria
    public function porCategoria(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $clienteApiProductos = new Client();

            $sql = "SELECT producto_id, SUM(subtotal) as total_dinero FROM items_venta GROUP BY producto_id";
            $stmt = $pdo->query($sql);
            $ventasPorProducto = $stmt->fetchAll();

            $resProductos = $clienteApiProductos->get('http://productos/productos');
            $listaProductos = json_decode($resProductos->getBody()->getContents(), true);
            // Mapa ID => Categoria
            $mapaCategorias = array_column($listaProductos, 'categoria', 'id');

            $totales = [];
            foreach ($ventasPorProducto as $venta) {
                $cat = $mapaCategorias[$venta['producto_id']] ?? 'General';
                if (!isset($totales[$cat])) $totales[$cat] = 0;
                $totales[$cat] += (float)$venta['total_dinero'];
            }

            $resultado = [];
            foreach ($totales as $cat => $total) {
                $resultado[] = ['categoria' => $cat, 'total' => $total];
            }

            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // --- Helper Privado para no repetir código de agrupación ---
    private function agruparVentas($filas, $mapaNombres) {
        $ventas = [];
        foreach ($filas as $fila) {
            $id = $fila['venta_id'];
            if (!isset($ventas[$id])) {
                $ventas[$id] = [
                    '_id' => $id,
                    'fecha' => $fila['fecha_venta'],
                    'total' => $fila['monto_total'],
                    'usuario_id' => $fila['usuario_id'],
                    'productos' => []
                ];
            }
            $ventas[$id]['productos'][] = [
                'id_producto' => $fila['producto_id'],
                'nombre' => $mapaNombres[$fila['producto_id']] ?? 'Desconocido',
                'cantidad' => $fila['cantidad'],
                'precio' => $fila['precio_unitario'],
                'subtotal' => $fila['subtotal']
            ];
        }
        return $ventas;
    }
}