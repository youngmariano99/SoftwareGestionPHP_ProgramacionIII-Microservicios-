<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Asumimos que has copiado el archivo conexionMySQL.php en ../src/
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/conexionMySQL.php'; 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


$app = AppFactory::create();

// 1. Endpoint para CREAR un nuevo log (Uso interno)
// POST /logs
$app->post('/logs', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);

    // Campos que esperamos recibir de otros servicios
    $usuario_id = $data['usuario_id'] ?? null;
    $sucursal_id = $data['sucursal_id'] ?? null;
    $tipo_accion = $data['tipo_accion'] ?? 'INDEFINIDO';
    $descripcion = $data['descripcion'] ?? ''; // La descripción la genera el servicio que llama

    try {
        $pdo = getDbConnection();
        // Insertamos en la tabla logs_auditoria
        // Omitimos direccion_ip como decidimos
        $sql = "INSERT INTO logs_auditoria (usuario_id, sucursal_id, tipo_accion, descripcion, creado_en) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $sucursal_id, $tipo_accion, $descripcion]);

        $response->getBody()->write(json_encode([
            'status' => 'success', 
            'log_id' => $pdo->lastInsertId()
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD (Auditoría): ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// 2. Endpoint para LISTAR logs (Para el Admin)
// GET /logs
$app->get('/logs', function (Request $request, Response $response) {
    
    try {
        $pdo = getDbConnection();
        
        // ¡Mejoramos la consulta! Hacemos JOIN con usuarios y sucursales
        // para mostrar los NOMBRES en el dashboard, no solo los IDs.
        $sql = "SELECT 
                    l.id, l.tipo_accion, l.descripcion, l.creado_en,
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido,
                    s.nombre as sucursal_nombre
                FROM logs_auditoria l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                LEFT JOIN sucursales s ON l.sucursal_id = s.id
                ORDER BY l.creado_en DESC
                LIMIT 100"; // Limitamos a los 100 más recientes
        
        $stmt = $pdo->query($sql);
        $logs = $stmt->fetchAll();

        $response->getBody()->write(json_encode($logs));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD (Auditoría): ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();