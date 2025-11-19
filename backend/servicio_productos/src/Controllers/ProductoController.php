<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\ConexionMongo;
use GuzzleHttp\Client;
use MongoDB\BSON\ObjectId; // Necesario para manejar IDs de Mongo

class ProductoController {

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
            error_log("Fallo al registrar log: " . $e->getMessage());
        }
    }

    // GET /productos
    public function listar(Request $request, Response $response) {
        $db = ConexionMongo::obtenerConexion();
        $coleccion = $db->selectCollection('productos');
        
        $cursor = $coleccion->find();
        
        $productos = [];
        foreach ($cursor as $documento) {
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
    }

    // GET /productos/{id}
    public function obtenerUno(Request $request, Response $response, array $args) {
        $idProducto = $args['id'];
        $db = ConexionMongo::obtenerConexion();
        $coleccion = $db->selectCollection('productos');

        try {
            $documento = $coleccion->findOne(['_id' => new ObjectId($idProducto)]);

            if (!$documento) {
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

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'ID inválido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /productos
    public function crear(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        
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
                'categoria' => $categoria
            ]);
            
            $nuevoId = (string) $resultado->getInsertedId();

            $this->registrarLog($auth_user_id, $auth_sucursal_id, 'CREAR_PRODUCTO_OK', "Producto '{$data['nombre']}' creado.");

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'mensaje' => 'Producto creado',
                'id' => $nuevoId
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->registrarLog($auth_user_id, $auth_sucursal_id, 'CREAR_PRODUCTO_ERROR', $e->getMessage());
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /productos/{id}
    public function editar(Request $request, Response $response, array $args) {
        $idProducto = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        $auth_user_id = $data['usuario_id'] ?? null;
        $auth_sucursal_id = $data['sucursal_id'] ?? null;

        $datosParaActualizar = [];
        if (!empty($data['nombre'])) $datosParaActualizar['nombre'] = $data['nombre'];
        if (!empty($data['categoria'])) $datosParaActualizar['categoria'] = $data['categoria'];
        if (isset($data['precio'])) $datosParaActualizar['precio'] = (float) $data['precio'];
        if (isset($data['stock'])) $datosParaActualizar['stock'] = (int) $data['stock'];

        if (empty($datosParaActualizar)) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Sin datos para actualizar']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $db = ConexionMongo::obtenerConexion();
            $coleccion = $db->selectCollection('productos');

            $resultado = $coleccion->updateOne(
                ['_id' => new ObjectId($idProducto)],
                ['$set' => $datosParaActualizar]
            );

            if ($resultado->getMatchedCount() === 0) {
                $this->registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_FALLO', "ID $idProducto no encontrado.");
                $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $this->registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_OK', "Producto ID $idProducto editado.");
            $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Producto actualizado']));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->registrarLog($auth_user_id, $auth_sucursal_id, 'EDITAR_PRODUCTO_ERROR', $e->getMessage());
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Error interno']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /productos/{id}/stock
    public function actualizarStock(Request $request, Response $response, array $args) {
        $idProducto = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['stock'])) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Stock requerido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevoStock = (int) $data['stock'];

        try {
            $db = ConexionMongo::obtenerConexion();
            $coleccion = $db->selectCollection('productos');

            $resultado = $coleccion->updateOne(
                ['_id' => new ObjectId($idProducto)],
                ['$set' => ['stock' => $nuevoStock]]
            );

            if ($resultado->getMatchedCount() === 0) {
                $this->registrarLog(null, null, 'STOCK_UPDATE_FALLO', "Producto $idProducto no encontrado.");
                $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $this->registrarLog(null, null, 'STOCK_UPDATE_OK', "Stock ID $idProducto -> $nuevoStock (Venta).");
            $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Stock actualizado']));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->registrarLog(null, null, 'STOCK_UPDATE_ERROR', $e->getMessage());
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Error DB']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /stock/bajo
    public function listarStockBajo(Request $request, Response $response) {
        try {
            $db = ConexionMongo::obtenerConexion();
            $coleccion = $db->selectCollection('productos');
            
            $cursor = $coleccion->find(['stock' => ['$lte' => 5]]);
            
            $productos = [];
            foreach ($cursor as $doc) {
                $productos[] = [
                    'id' => (string) $doc['_id'],
                    'nombre' => $doc['nombre'],
                    'precio' => $doc['precio'],
                    'stock' => $doc['stock']
                ];
            }
            
            $response->getBody()->write(json_encode($productos));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /categorias
    public function listarCategorias(Request $request, Response $response) {
        try {
            $db = ConexionMongo::obtenerConexion();
            $coleccion = $db->selectCollection('productos');
            $categorias = $coleccion->distinct('categoria');
            $categorias = array_filter($categorias);
            
            $response->getBody()->write(json_encode(array_values($categorias)));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}