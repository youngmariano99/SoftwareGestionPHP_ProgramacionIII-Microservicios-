<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController {

    // GET /empleados
    public function listarEmpleados(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $sql = "SELECT u.id, u.nombre, u.apellido, u.email, u.rol, u.tarifa_hora, s.nombre as nombre_sucursal 
                    FROM usuarios u
                    LEFT JOIN sucursales s ON u.sucursal_id = s.id
                    ORDER BY u.id ASC";
            $stmt = $pdo->query($sql);
            
            $response->getBody()->write(json_encode($stmt->fetchAll()));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /horas-trabajadas/resumen
    public function resumenHoras(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $sql = "SELECT u.id, u.nombre, u.apellido, s.nombre as sucursal_nombre,
                        SUM(TIMESTAMPDIFF(MINUTE, ht.hora_entrada, ht.hora_salida)) / 60.0 as total_horas_decimal
                    FROM horas_trabajadas ht
                    JOIN usuarios u ON ht.usuario_id = u.id
                    LEFT JOIN sucursales s ON u.sucursal_id = s.id
                    WHERE ht.hora_salida IS NOT NULL
                    GROUP BY u.id
                    ORDER BY total_horas_decimal DESC";
            
            $stmt = $pdo->query($sql);
            $response->getBody()->write(json_encode($stmt->fetchAll()));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /liquidacion/{user_id}
    public function liquidarSueldo(Request $request, Response $response, array $args) {
        $userId = $args['user_id'];
        try {
            $pdo = \getDbConnection();
            $sql = "SELECT u.tarifa_hora,
                        SUM(TIMESTAMPDIFF(MINUTE, ht.hora_entrada, ht.hora_salida)) / 60.0 as total_horas_decimal
                    FROM horas_trabajadas ht
                    JOIN usuarios u ON ht.usuario_id = u.id
                    WHERE ht.usuario_id = ? AND ht.hora_salida IS NOT NULL
                    GROUP BY u.tarifa_hora";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $calculo = $stmt->fetch();

            if (!$calculo) {
                $response->getBody()->write(json_encode(['error' => 'Sin datos para liquidar.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $tarifa = (float) $calculo['tarifa_hora'];
            $horas = (float) $calculo['total_horas_decimal'];
            
            $response->getBody()->write(json_encode([
                'usuario_id' => $userId,
                'total_horas_decimal' => round($horas, 2),
                'tarifa_por_hora' => $tarifa,
                'total_a_pagar' => round($tarifa * $horas, 2)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}