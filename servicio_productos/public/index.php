<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\ConexionMongo; // <-- Importamos nuestra clase de conexión

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// ---- Endpoint para CREAR un producto ----
// Corresponde a tu requisito "crear productos"
// POST /productos
$app->post('/productos', function (Request $request, Response $response) {
    
    // Obtenemos los datos (JSON) que envía el frontend
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);
    

    
    // Validación simple
    if (empty($data['nombre']) || empty($data['precio']) || !isset($data['stock'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');
    
    // Insertamos el nuevo producto
    $resultado = $coleccion->insertOne([
        'nombre' => $data['nombre'],
        'precio' => (float) $data['precio'],
        'stock' => (int) $data['stock']
    ]);
    
    $nuevoId = (string) $resultado->getInsertedId();

    $response->getBody()->write(json_encode([
        'status' => 'success',
        'mensaje' => 'Producto creado',
        'id' => $nuevoId
    ]));
    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
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
            'stock' => $documento['stock']
        ];
    }
    
    $response->getBody()->write(json_encode($productos));
    return $response->withHeader('Content-Type', 'application/json');
});


// ---- Endpoint para ACTUALIZAR STOCK ----
// Corresponde a tu requisito "manejar stock"
// PUT /productos/{id}/stock
$app->put('/productos/{id}/stock', function (Request $request, Response $response, array $args) {
    
    $idProducto = $args['id'];
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);
    

    if (!isset($data['stock'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Stock no especificado']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $db = ConexionMongo::obtenerConexion();
    $coleccion = $db->selectCollection('productos');

    try {
        // Buscamos por el ObjectId de Mongo
        $resultado = $coleccion->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($idProducto)],
            ['$set' => ['stock' => (int) $data['stock']]]
        );

        if ($resultado->getMatchedCount() === 0) {
            $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Producto no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Stock actualizado']));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\MongoDB\Exception\InvalidArgumentException $e) {
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


$app->run();