<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SucursalController {

    // GET /sucursales
    public function listar(Request $request, Response $response) {
        try {
            $pdo = \getDbConnection();
            $stmt = $pdo->query("SELECT * FROM sucursales ORDER BY id DESC");
            $sucursales = $stmt->fetchAll();

            $response->getBody()->write(json_encode($sucursales));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /sucursales
    public function crear(Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['nombre']) || empty($data['direccion']) || empty($data['latitud']) || empty($data['longitud'])) {
            $response->getBody()->write(json_encode(['error' => 'Faltan datos requeridos.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdo = \getDbConnection();
            $stmt = $pdo->prepare("INSERT INTO sucursales (nombre, direccion, telefono, latitud, longitud) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['nombre'],
                $data['direccion'],
                $data['telefono'] ?? '',
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
    }
}