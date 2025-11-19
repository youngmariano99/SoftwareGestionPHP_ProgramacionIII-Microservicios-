<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;

class AuthController {

    // --- Helper Privado para Logs (Copiado para que funcione aquí dentro) ---
    private function registrarLog($usuario_id, $tipo_accion, $descripcion, $sucursal_id = null) {
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

    // --- MÉTODO: LOGIN ---
    public function login(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response->getBody()->write(json_encode(['error' => 'Email y contraseña requeridos.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Usamos la función global getDbConnection() que cargaremos en el index
            $pdo = \getDbConnection(); 
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                
                // ¡Éxito! Creamos el token
                $claveSecreta = $_ENV['JWT_SECRET'];
                $payload = [
                    'iat' => time(),
                    'exp' => time() + (60 * 60), // 1 hora
                    'data' => [
                        'id' => $user['id'],
                        'nombre' => $user['nombre'],
                        'rol' => $user['rol'],
                        'sucursal_id' => $user['sucursal_id'] 
                    ]
                ];
                
                $token = JWT::encode($payload, $claveSecreta, 'HS256');
            
                $response->getBody()->write(json_encode([
                    'status' => 'success',
                    'token' => $token,
                    'rol' => $user['rol']
                ]));
                return $response->withHeader('Content-Type', 'application/json');

            } else {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'mensaje' => 'Credenciales incorrectas'
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error DB: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // --- MÉTODO: REGISTRO ---
    public function registro(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        
        $nombre = $data['nombre'] ?? '';
        $apellido = $data['apellido'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $rol = $data['rol'] ?? 'vendedor'; 
        $sucursal_id = $data['sucursal_id'] ?? null;
        $tarifa_hora = $data['tarifa_hora'] ?? 0.00;

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($sucursal_id)) {
            $response->getBody()->write(json_encode(['error' => 'Datos incompletos.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo = \getDbConnection(); // La barra invertida \ indica namespace global
            
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $this->registrarLog(null, 'REGISTRO_FALLO', "Email '$email' ya existe.", $sucursal_id);
                $response->getBody()->write(json_encode(['error' => 'El email ya está registrado.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json'); 
            }

            $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, sucursal_id, tarifa_hora) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([$nombre, $apellido, $email, $passwordHash, $rol, $sucursal_id, $tarifa_hora]);
            $nuevoUsuarioId = $pdo->lastInsertId();

            $this->registrarLog($nuevoUsuarioId, 'REGISTRO_OK', "Usuario '$nombre $apellido' creado.", $sucursal_id);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'mensaje' => 'Empleado registrado exitosamente.',
                'id_usuario' => $nuevoUsuarioId
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            $this->registrarLog(null, 'REGISTRO_ERROR', 'Error BD: ' . $e->getMessage(), $sucursal_id);
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}