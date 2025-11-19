<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface; // <--- ¡NUEVO IMPORT!
use App\ConexionMongo;
use GuzzleHttp\Client;
use MongoDB\BSON\ObjectId;

class ProductoController {

    // ... (tu función registrarLog sigue igual) ...
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

    // --- ¡NUEVO MÉTODO! SUBIR IMAGEN ---
    public function subirImagen(Request $request, Response $response) {
        // Obtenemos los archivos subidos
        $archivos = $request->getUploadedFiles();
        
        // Verificamos si viene un archivo llamado 'imagen'
        if (empty($archivos['imagen'])) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'No se envió ninguna imagen.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $archivo = $archivos['imagen'];

        if ($archivo->getError() === UPLOAD_ERR_OK) {
            // Definimos dónde guardar (la carpeta compartida por Docker)
            $directorioUploads = __DIR__ . '/../../public/uploads';
            
            // Crear carpeta si no existe
            if (!is_dir($directorioUploads)) {
                mkdir($directorioUploads, 0777, true);
            }

            // Generamos un nombre único para no sobreescribir
            $extension = pathinfo($archivo->getClientFilename(), PATHINFO_EXTENSION);
            $nombreBase = bin2hex(random_bytes(8)); // Nombre aleatorio
            $nombreArchivo = sprintf('%s.%0.8s', $nombreBase, $extension);
            
            // Movemos el archivo
            $archivo->moveTo($directorioUploads . DIRECTORY_SEPARATOR . $nombreArchivo);

            // Devolvemos la ruta pública
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'ruta' => 'uploads/' . $nombreArchivo
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Error al subir archivo.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // --- MÉTODO CREAR (ACTUALIZADO PARA GUARDAR URL IMAGEN) ---
    public function crear(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        
        $auth_user_id = $data['usuario_id'] ?? null; 
        $auth_sucursal_id = $data['sucursal_id'] ?? null;
        $categoria = $data['categoria'] ?? 'General';
        $imagen = $data['imagen'] ?? null; // <--- ¡NUEVO CAMPO!

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
                'categoria' => $categoria,
                'imagen' => $imagen // <--- GUARDAMOS EN MONGO
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

    // --- MÉTODO EDITAR (ACTUALIZADO) ---
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
        if (!empty($data['imagen'])) $datosParaActualizar['imagen'] = $data['imagen']; // <--- ¡NUEVO!

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
    
    // ... (Tus otros métodos: listar, obtenerUno, actualizarStock, listarStockBajo, listarCategorias siguen IGUAL) ...
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
                'categoria' => $documento['categoria'] ?? 'General',
                'imagen' => $documento['imagen'] ?? null // <--- Incluir en el listado
            ];
        }
        
        $response->getBody()->write(json_encode($productos));
        return $response->withHeader('Content-Type', 'application/json');
    }

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
                'stock' => $documento['stock'],
                'imagen' => $documento['imagen'] ?? null // <--- Incluir aquí también
            ];
            
            $response->getBody()->write(json_encode($producto));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'ID inválido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
    // ... El resto de métodos (actualizarStock, listarStockBajo, listarCategorias) no necesitan cambios por ahora.
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