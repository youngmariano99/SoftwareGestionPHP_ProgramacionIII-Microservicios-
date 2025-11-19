<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;

class AsistenciaController {

    // Helper privado para logs (copiado para mantener independencia por ahora)
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

    // POST /horas-trabajadas/horario-entrada
    public function registrarEntrada(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        $userId = $data['usuario_id'] ?? null;

        if (empty($userId)) {
            $response->getBody()->write(json_encode(['error' => 'ID de usuario requerido.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdo = \getDbConnection();
            
            // Verificar si ya tiene turno abierto
            $stmtCheck = $pdo->prepare("SELECT id FROM horas_trabajadas WHERE usuario_id = ? AND hora_salida IS NULL");
            $stmtCheck->execute([$userId]);
            
            if ($stmtCheck->fetch()) {
                $response->getBody()->write(json_encode(['status' => 'warn', 'mensaje' => 'Ya tienes un turno abierto.']));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            }

            // Crear nuevo turno
            $stmtInsert = $pdo->prepare("INSERT INTO horas_trabajadas (usuario_id, hora_entrada) VALUES (?, NOW())");
            $stmtInsert->execute([$userId]);

            $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Entrada registrada exitosamente.']));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /horas-trabajadas/horario-salida
    public function registrarSalida(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);
        $userId = $data['usuario_id'] ?? null;
        $sucursal_id = $data['sucursal_id'] ?? null;

        if (empty($userId) || empty($sucursal_id)) {
            $response->getBody()->write(json_encode(['error' => 'Faltan datos (usuario o sucursal).']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdo = \getDbConnection();
            $stmt = $pdo->prepare("UPDATE horas_trabajadas SET hora_salida = NOW() WHERE usuario_id = ? AND hora_salida IS NULL");
            $stmt->execute([$userId]);

            if ($stmt->rowCount() > 0) {
                $this->registrarLog($userId, 'CIERRE_TURNO_OK', 'Cierre de turno manual exitoso.', $sucursal_id);
                $response->getBody()->write(json_encode(['status' => 'success', 'mensaje' => 'Turno cerrado exitosamente.']));
            } else {
                $this->registrarLog($userId, 'CIERRE_TURNO_OK', 'Intento de cierre sin turno abierto.', $sucursal_id);
                $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'No se encontró un turno abierto.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            $this->registrarLog($userId, 'CIERRE_TURNO_ERROR', 'Error BD: ' . $e->getMessage(), $sucursal_id);
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /horas-trabajadas/abierto/{user_id}
    public function verificarTurnoAbierto(Request $request, Response $response, array $args) {
        $userId = $args['user_id'];
        try {
            $pdo = \getDbConnection();
            $stmt = $pdo->prepare("SELECT * FROM horas_trabajadas WHERE usuario_id = ? AND hora_salida IS NULL LIMIT 1");
            $stmt->execute([$userId]);
            $turno = $stmt->fetch();

            if ($turno) {
                $response->getBody()->write(json_encode(['status' => 'open', 'turno' => $turno]));
            } else {
                $response->getBody()->write(json_encode(['status' => 'closed']));
            }
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}