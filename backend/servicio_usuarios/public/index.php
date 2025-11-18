<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;

require __DIR__ . '/../vendor/autoload.php';

// --- ¡NUEVO! Cargar variables de entorno ---
// Carga el .env desde la raíz del servicio (un nivel arriba de /public)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// --- ¡NUEVO! Incluir nuestra función de conexión ---
require __DIR__ . '/../src/conexionMySQL.php';

// --- ¡NUEVO! Función helper para registrar en el servicio de auditoría ---
// (Es la misma que usamos en servicio_ventas, pero con sucursal_id opcional)
function registrarLog($usuario_id, $tipo_accion, $descripcion, $sucursal_id = null) {
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
        // Si la auditoría falla, no detenemos la acción principal
        error_log("Fallo al registrar log de auditoría: " . $e->getMessage());
    }
}

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
        // El código correcto (seguro):
    if ($user && password_verify($password, $user['password'])) {
            
            // ¡Éxito! Creamos el token
            $claveSecreta = $_ENV['JWT_SECRET']; // Usamos la clave del .env
            $payload = [
                'iat' => time(),
                'exp' => time() + (60 * 60), // 1 hora de expiración
                'data' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'rol' => $user['rol'], // 'administrador' o 'vendedor'
                    'sucursal_id' => $user['sucursal_id'] 
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

// --- ¡ACTUALIZADO! Endpoint de Registro (Ahora para Admin) ---
// --- ¡ACTUALIZADO! Endpoint de Registro CON AUDITORÍA ---
$app->post('/registro', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);
    
    $nombre = $data['nombre'] ?? '';
    $apellido = $data['apellido'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $rol = $data['rol'] ?? 'vendedor'; 
    $sucursal_id = $data['sucursal_id'] ?? null;
    $tarifa_hora = $data['tarifa_hora'] ?? 0.00;

    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($sucursal_id)) {
        $response->getBody()->write(json_encode(['error' => 'Nombre, apellido, email, password y sucursal son requeridos.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo = getDbConnection();
        
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            
            // ¡FALLO LÓGICO! Registramos el error
            $errorMsg = "Intento de registro fallido: Email '$email' ya existe.";
            registrarLog(null, $sucursal_id, 'REGISTRO_FALLO', $errorMsg); // Aún no hay ID de usuario, por eso 'null'

            $response->getBody()->write(json_encode(['error' => 'El email ya está registrado.']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json'); 
        }

        $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, sucursal_id, tarifa_hora) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([$nombre, $apellido, $email, $passwordHash, $rol, $sucursal_id, $tarifa_hora]);
        $nuevoUsuarioId = $pdo->lastInsertId();

        // ¡ÉXITO! Registramos el alta
        $desc = "Nuevo empleado '$nombre $apellido' (ID: $nuevoUsuarioId) creado con rol '$rol' en sucursal ID $sucursal_id.";
        // Logueamos con el ID del *nuevo* usuario como referencia
        registrarLog($nuevoUsuarioId, $sucursal_id, 'REGISTRO_OK', $desc);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Empleado registrado exitosamente.',
            'id_usuario' => $nuevoUsuarioId
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        
        // ¡FALLO DE EXCEPCIÓN!
        $errorMsg = 'Error BD al registrar usuario: ' . $e->getMessage();
        registrarLog(null, $sucursal_id, 'REGISTRO_ERROR', $errorMsg);

        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});



// Endpoint para obtener empleados (sigue simulado, puedes cambiarlo)
// --- ¡ACTUALIZADO! Endpoint para obtener TODOS los empleados con su sucursal ---
$app->get('/empleados', function (Request $request, Response $response) {
    
    try {
        $pdo = getDbConnection();
        
        // Esta query usa LEFT JOIN para traer el nombre de la sucursal.
        // Usamos 's.nombre as nombre_sucursal' para que no haya conflicto
        // con la columna 'u.nombre' (del usuario).
        $sql = "SELECT 
                    u.id, 
                    u.nombre, 
                    u.apellido, 
                    u.email, 
                    u.rol, 
                    u.tarifa_hora,
                    s.nombre as nombre_sucursal 
                FROM usuarios u
                LEFT JOIN sucursales s ON u.sucursal_id = s.id
                ORDER BY u.id ASC";
        
        $stmt = $pdo->query($sql);
        $empleados = $stmt->fetchAll();

        $response->getBody()->write(json_encode($empleados));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- ¡NUEVO! Endpoint para registrar entrada (Clock-in) ---
// POST /work-hours/clock-in
$app->post('/horas-trabajadas/horario-entrada', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);
    $userId = $data['usuario_id'] ?? null;

    if (empty($userId)) {
        $response->getBody()->write(json_encode(['error' => 'ID de usuario requerido.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getDbConnection();
        
        // 1. Primero, verificar si ya tiene un turno abierto
        $stmtCheck = $pdo->prepare("SELECT id FROM horas_trabajadas WHERE usuario_id = ? AND hora_salida IS NULL");
        $stmtCheck->execute([$userId]);
        
        if ($stmtCheck->fetch()) {
            $response->getBody()->write(json_encode([
                'status' => 'warn', // Usamos 'warn' (advertencia)
                'mensaje' => 'Ya tienes un turno abierto.'
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json'); // No es un error, es un estado
        }

        // 2. Si no hay turno abierto, creamos uno nuevo
        // Usamos DATETIME y NOW() para la hora exacta
        $stmtInsert = $pdo->prepare("INSERT INTO horas_trabajadas (usuario_id, hora_entrada) VALUES (?, NOW())");
        $stmtInsert->execute([$userId]);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Entrada registrada exitosamente.'
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// --- ¡NUEVO! Endpoint para verificar si tiene turno abierto ---
// GET /work-hours/open/{user_id}
$app->get('/horas-trabajadas/abierto/{user_id}', function (Request $request, Response $response, array $args) {
    
    $userId = $args['user_id'];

    try {
        $pdo = getDbConnection();
        // Buscamos un turno sin hora_salida
        $stmt = $pdo->prepare("SELECT * FROM horas_trabajadas WHERE usuario_id = ? AND hora_salida IS NULL LIMIT 1");
        $stmt->execute([$userId]);
        $turno = $stmt->fetch();

        if ($turno) {
            $response->getBody()->write(json_encode([
                'status' => 'open',
                'turno' => $turno // Enviamos los datos del turno abierto
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'closed'
            ]));
        }
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- ¡NUEVO! Endpoint para registrar salida (Clock-out) ---
// PUT /work-hours/clock-out
// --- ¡ACTUALIZADO! Endpoint para registrar salida (Clock-out) CON AUDITORÍA ---
$app->put('/horas-trabajadas/horario-salida', function (Request $request, Response $response) {
    
    $data = json_decode($request->getBody()->getContents(), true);
    $userId = $data['usuario_id'] ?? null;
    $sucursal_id = $data['sucursal_id'] ?? null;

    if (empty($userId) || empty($sucursal_id)) {
        $response->getBody()->write(json_encode(['error' => 'ID de usuario y ID de sucursal requeridos.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("UPDATE horas_trabajadas SET hora_salida = NOW() WHERE usuario_id = ? AND hora_salida IS NULL");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            // ¡ÉXITO!
            registrarLog($userId, 'CIERRE_TURNO_OK', 'Cierre de turno manual exitoso.', $sucursal_id);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'mensaje' => 'Turno cerrado exitosamente. ¡Buen descanso!'
            ]));
        } else {
            // ¡FALLO LÓGICO!
            $errorMsg = 'No se encontró un turno abierto para cerrar.';
            registrarLog($userId, 'CIERRE_TURNO_OK', 'Cierre de turno manual exitoso.', $sucursal_id);
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'mensaje' => $errorMsg
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        // ¡FALLO DE EXCEPCIÓN!
        registrarLog($userId, 'CIERRE_TURNO_ERROR', 'Error BD: ' . $e->getMessage());

        $response->getBody()->write(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- GESTIÓN DE SUCURSALES (ADMIN) ---

// 1. Crear nueva sucursal
// POST /sucursales
$app->post('/sucursales', function (Request $request, Response $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validamos datos mínimos
    if (empty($data['nombre']) || empty($data['direccion']) || empty($data['latitud']) || empty($data['longitud'])) {
        $response->getBody()->write(json_encode(['error' => 'Faltan datos requeridos (nombre, direccion, coordenadas).']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO sucursales (nombre, direccion, telefono, latitud, longitud) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nombre'],
            $data['direccion'],
            $data['telefono'] ?? '', // Telefono opcional
            $data['latitud'],
            $data['longitud']
        ]);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Sucursal creada exitosamente.',
            'id' => $pdo->lastInsertId()
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// 2. Listar sucursales
// GET /sucursales
$app->get('/sucursales', function (Request $request, Response $response) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM sucursales ORDER BY id DESC");
        $sucursales = $stmt->fetchAll();

        $response->getBody()->write(json_encode($sucursales));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// --- REPORTES DE HORAS (ADMIN) ---

// 1. Endpoint para Resumen de Horas (Sprint 4)
// GET /work-hours/summary
$app->get('/horas-trabajadas/resumen', function (Request $request, Response $response) {
    
    try {
        $pdo = getDbConnection();
        // Esta query calcula el total de horas decimales trabajadas por cada empleado
        // y lo junta con su nombre y el nombre de su sucursal.
        $sql = "SELECT 
                    u.id, 
                    u.nombre, 
                    u.apellido,
                    s.nombre as sucursal_nombre,
                    SUM(TIMESTAMPDIFF(MINUTE, ht.hora_entrada, ht.hora_salida)) / 60.0 as total_horas_decimal
                FROM horas_trabajadas ht
                JOIN usuarios u ON ht.usuario_id = u.id
                LEFT JOIN sucursales s ON u.sucursal_id = s.id
                WHERE ht.hora_salida IS NOT NULL -- Solo contar turnos cerrados
                GROUP BY u.id
                ORDER BY total_horas_decimal DESC";
        
        $stmt = $pdo->query($sql);
        $resumen = $stmt->fetchAll();

        $response->getBody()->write(json_encode($resumen));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD (Summary): ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


// 2. Endpoint para Liquidación (Sueldo) de un empleado (Sprint 4)
// GET /liquidacion/{user_id}
$app->get('/liquidacion/{user_id}', function (Request $request, Response $response, array $args) {
    
    $userId = $args['user_id'];

    try {
        $pdo = getDbConnection();
        
        // 1. Buscamos la tarifa y el total de horas de ESE empleado
        $sql = "SELECT 
                    u.tarifa_hora,
                    SUM(TIMESTAMPDIFF(MINUTE, ht.hora_entrada, ht.hora_salida)) / 60.0 as total_horas_decimal
                FROM horas_trabajadas ht
                JOIN usuarios u ON ht.usuario_id = u.id
                WHERE ht.usuario_id = ? 
                  AND ht.hora_salida IS NOT NULL
                GROUP BY u.tarifa_hora"; // Agrupamos por tarifa
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $calculo = $stmt->fetch();

        if (!$calculo) {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron horas trabajadas para este empleado.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // 2. Hacemos el cálculo en PHP
        $tarifa = (float) $calculo['tarifa_hora'];
        $horas = (float) $calculo['total_horas_decimal'];
        $totalAPagar = $tarifa * $horas;

        $response->getBody()->write(json_encode([
            'usuario_id' => $userId,
            'total_horas_decimal' => round($horas, 2), // Redondeamos a 2 decimales
            'tarifa_por_hora' => $tarifa,
            'total_a_pagar' => round($totalAPagar, 2)
        ]));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Error BD (Liquidación): ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();