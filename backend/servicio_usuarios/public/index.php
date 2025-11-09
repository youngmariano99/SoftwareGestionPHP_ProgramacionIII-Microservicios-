<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;

require __DIR__ . '/../vendor/autoload.php';

// --- ¡NUEVO! Cargar variables de entorno ---
// Carga el .env desde la raíz del servicio (un nivel arriba de /public)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// --- ¡NUEVO! Incluir nuestra función de conexión ---
require __DIR__ . '/../src/conexionMySQL.php';

$app = AppFactory::create();

// Endpoint de Login (Ahora con lógica de BD)
$app->post('/login', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response->getBody()->write(json_encode(['error' => 'Email y contraseña requeridos.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?"); // Asegúrate que tu tabla se llame 'usuarios'
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verificamos el usuario y la contraseña hasheada
        if ($user && $password == $user['password']) {
            
            // ¡Éxito! Creamos el token
            $claveSecreta = $_ENV['JWT_SECRET']; // Usamos la clave del .env
            $payload = [
                'iat' => time(),
                'exp' => time() + (60 * 60), // 1 hora de expiración
                'data' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol'] // 'administrador' o 'vendedor'
                ]
            ];
            
            $token  = JWT::encode($payload, $claveSecreta, 'HS256');
           
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'token' => $token,
                'rol' => $user['rol']
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } else {
            // Credenciales incorrectas
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'mensaje' => 'Credenciales incorrectas'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- ¡NUEVO! Endpoint de Registro ---
$app->post('/registro', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);
    $nombre = $data['nombre'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $rol = $data['rol'] ?? 'vendedor'; // Por defecto es 'vendedor'

    if (empty($nombre) || empty($email) || empty($password)) {
        $response->getBody()->write(json_encode(['error' => 'Nombre, email y contraseña son requeridos.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Hashear la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo = getDbConnection();
        
        // Primero, verificar si el email ya existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'El email ya está registrado.']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json'); // 409 Conflict
        }

        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $passwordHash, $rol]);

        $nuevoUsuarioId = $pdo->lastInsertId();

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Usuario registrado exitosamente.',
            'id_usuario' => $nuevoUsuarioId
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json'); // 201 Created

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});



// Endpoint para obtener empleados (sigue simulado, puedes cambiarlo)
$app->get('/empleados', function (Request $request, Response $response) {
    
    // TAREA: Reemplaza esto con una consulta a la BD usando getDbConnection()
    // $pdo = getDbConnection();
    // $stmt = $pdo->query("SELECT id, nombre, rol FROM usuarios WHERE rol != 'administrador'");
    // $empleados = $stmt->fetchAll();

    $pdo = getDbConnection();
    // ✅ FORMA RECOMENDADA - Parámetros preparados (más seguro)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = :rol");
    $stmt->execute(['rol' => 'vendedor']);
    $empleados = $stmt->fetchAll();


    $response->getBody()->write(json_encode($empleados));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('registroEntada/{id_empleado}', function (Request $request, Response $response) {
    
    // TAREA: Reemplaza esto con una consulta a la BD usando getDbConnection()
    // $pdo = getDbConnection();
    // $stmt = $pdo->query("SELECT id, nombre, rol FROM usuarios WHERE rol != 'administrador'");
    // $empleados = $stmt->fetchAll();

    $pdo = getDbConnection();
    // ✅ FORMA RECOMENDADA - Parámetros preparados (más seguro)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = :rol");
    $stmt->execute(['rol' => 'vendedor']);
    $empleados = $stmt->fetchAll();


    $response->getBody()->write(json_encode($empleados));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->run();