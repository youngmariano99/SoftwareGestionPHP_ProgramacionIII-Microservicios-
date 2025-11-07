<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;

require __DIR__ . '/../vendor/autoload.php';

// --- (Simulación de tu conexión a BD) ---
// Mueve tu lógica de 'db/conexionMySQL.php' a un archivo aquí (ej. /src/Db.php)
// y llámala para obtener $pdo. Por ahora, lo simulamos.
function getDbConnection() {
    // Aquí iría tu lógica real de conexión PDO o MySQLi
    // require __DIR__ . '/../src/conexionMySQL.php';
    // return $pdo;
    return null; // Simulación
}
// -----------------------------------------


$app = AppFactory::create();

$claveSecreta = '123456789';

// Define el "endpoint" para el login
// Esto REEMPLAZA tu 'procesar_login.php'
$app->post('/login', function (Request $request, Response $response) {
    
    // Obtiene los datos del body (ej. JSON) en lugar de $_POST
    $data = json_decode($request->getBody()->getContents(), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // --- LÓGICA DE LOGIN ---
    // Aquí pegarías la lógica de tu 'procesar_login.php'
    // $pdo = getDbConnection();
    // $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    // $stmt->execute([$email]);
    // $user = $stmt->fetch();
    // 
    // if ($user && password_verify($password, $user['password'])) {
    // -----------------------
    global $claveSecreta;
    // Simulación de éxito
    if ($email === 'admin@admin.com' && $password === '1234') {
        
        $payload = [
            'iat' => time(),
            'exp' => time() + (60*60),
            'data' => [
                'id' => 1,
                'nombre' => 'Admin',
                'rol' => 'administrador'
            ]
            ];
        
        $token  = JWT::encode($payload, $claveSecreta, 'HS256');
       
        // En lugar de 'header(Location: ...)' y '$_SESSION',
        // un microservicio DEVUELVE datos (JSON).
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'token' => $token
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } elseif ($email === 'empleado@tienda.com' && $password === '1234') {
    global $claveSecreta; 
    $payload = [
        'iat' => time(),
        'exp' => time() + (60 * 60),
        'data' => [
            'id' => 10, // El ID de Juan Perez (simulado)
            'nombre' => 'Juan Perez',
            'rol' => 'vendedor' // <--- ROL CLAVE
        ]
    ];
    $token = JWT::encode($payload, $claveSecreta, 'HS256');
    $response->getBody()->write(json_encode([
        'status' => 'success',
        'token' => $token
    ]));
    return $response->withHeader('Content-Type', 'application/json');
} else {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'mensaje' => 'Credenciales incorrectas'
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    
});

// Define el "endpoint" para obtener empleados
// Esto REEMPLAZA tu 'core/obtener_empleados.php'
$app->get('/empleados', function (Request $request, Response $response) {
    
    // --- LÓGICA DE OBTENER EMPLEADOS ---
    // Aquí pegarías la lógica de 'obtener_empleados.php'
    // $pdo = getDbConnection();
    // $stmt = $pdo->query("SELECT id, nombre, email, rol FROM empleados");
    // $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ----------------------------------

    // Simulación de datos
    $empleados = [
        ['id' => 10, 'nombre' => 'Juan Perez', 'rol' => 'vendedor'],
        ['id' => 11, 'nombre' => 'Ana Gomez', 'rol' => 'vendedor'],
    ];

    // Simplemente devolvemos los datos como JSON
    $response->getBody()->write(json_encode($empleados));
    return $response->withHeader('Content-Type', 'application/json');
});

// Agrega aquí los otros endpoints:
// POST /empleados (para crear)
// DELETE /empleados/{id} (para borrar)

$app->run();