<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client; // <-- Importamos Guzzle

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// ---- Endpoint para REGISTRAR una venta ----
// POST /ventas
$app->post('/ventas', function (Request $request, Response $response) {
    
    // El frontend nos enviará algo como:
    // { "id_empleado": 1, "productos": [ {"id": "ID_MONGO_COCA", "cantidad": 2} ] }
    $data = json_decode($request->getBody()->getContents(), true);

    if (empty($data['id_empleado']) || empty($data['productos'])) {
        $response->getBody()->write(json_encode(['status' => 'error', 'mensaje' => 'Datos de venta incompletos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Instanciamos el cliente Guzzle (nuestro "Postman" en código)
    $clienteApiProductos = new Client();

    // --- (Aquí iría la lógica de base de datos de ESTE servicio) ---
    // Por ejemplo, iniciar una transacción en la tabla 'ventas' de MySQL.
    // $pdoVentas = getDbConexionVentas();
    // $pdoVentas->beginTransaction();
    // -------------------------------------------------------------

    try {
        // ----- INICIO DE LA COMUNICACIÓN ENTRE SERVICIOS -----
        
        foreach ($data['productos'] as $productoVendido) {
            $id = $productoVendido['id'];
            $cantidadVendida = (int) $productoVendido['cantidad'];

            // ...
            // 1. Preguntamos al Servicio de Productos (directamente por la red interna)
            $urlGetProducto = "http://productos/productos/{$id}"; // <-- ¡Correcto!
            $responseGet = $clienteApiProductos->get($urlGetProducto);
            // ...

            $contenidoCrudo = $responseGet->getBody()->getContents();
    
            
            // (Nos aseguramos de que el JSON esté limpio, sin var_dumps)
            $producto = json_decode($contenidoCrudo, true);

             if (!isset($producto['stock'])) {
                throw new Exception("El campo 'stock' no está presente en la respuesta del producto");
            }

            $stockActual = (int) $producto['stock'];

           
            // 2. Validamos el stock
            if ($stockActual < $cantidadVendida) {
                // Si no hay stock, cancelamos todo.
                // $pdoVentas->rollBack();
                echo "Stock actual del producto {$producto['nombre']}: {$stockActual}\n";

                throw new Exception("Stock insuficiente para el producto: {$producto['nombre']}");
            }

            // 3. Calculamos y le decimos al Servicio de Productos que actualice el stock
            $nuevoStock = $stockActual - $cantidadVendida;
            $urlPutStock = "http://productos/productos/{$id}/stock"; // <-- ¡Correcto!
            
            $clienteApiProductos->put($urlPutStock, [
                'json' => ['stock' => $nuevoStock] // Guzzle envía esto como JSON
            ]);
        }
        // ----- FIN DE LA COMUNICACIÓN -----

        // Si todo salió bien, guardamos la venta en nuestra propia BD
        // $pdoVentas->commit();
        // (Simulamos el guardado...)
        $idVentaGuardada = rand(1000, 9999); 
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'mensaje' => 'Venta registrada exitosamente',
            'id_venta' => $idVentaGuardada
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        // Si algo falla (no hay stock, o el servicio de productos está caído),
        // revertimos nuestra transacción y devolvemos un error.
        
        // $pdoVentas->rollBack();
        
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'mensaje' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();