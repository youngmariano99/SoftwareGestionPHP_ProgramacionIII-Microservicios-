<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;



require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// --- INICIO: MIDDLEWARE DE CORS ---
// Esto se ejecuta en CADA petición y añade los encabezados de permiso.
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000') // Tu frontend
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
// --- FIN: MIDDLEWARE DE CORS ---


// --- CONFIGURACIÓN DEL GATEWAY ---

// ¡¡ASEGÚRATE DE QUE ESTA CLAVE SEA IDÉNTICA A LA DE servicio_usuarios!!
$claveSecreta = '123456789'; // (O la clave que estés usando)

$guzzle = new Client();
/*$servicios = [
    'usuarios' => 'http://localhost:8080',
    'productos' => 'http://localhost:8081',
    'ventas' => 'http://localhost:8082',
];*/

//Para docker

$servicios = [
    'usuarios' => 'http://usuarios',  // <-- CAMBIO
    'productos' => 'http://productos', // <-- CAMBIO
    'ventas' => 'http://ventas',     // <-- CAMBIO
    'horas-trabajadas' => 'http://horas-trabajadas',
    'auditorias' => 'http://auditorias'
];
// --- FIN CONFIGURACIÓN ---


/**
 * ÚNICA RUTA "CATCH-ALL"
 * Atrapa todas las peticiones (GET, POST, OPTIONS, etc.)
 */
$app->any('/{proxy:.*}', function (Request $request, Response $response, array $args) use ($guzzle, $servicios, $claveSecreta) {

    // --- MANEJO DE PETICIÓN 'OPTIONS' (PERMISO CORS) ---
    // Si es una petición de permiso, solo devolvemos "OK".
    // El Middleware de arriba ya se encargó de poner los encabezados.
    if ($request->getMethod() === 'OPTIONS') {
        return $response;
    }

    $path = $args['proxy'];
    
    // --- INICIO: GUARDIA DE SEGURIDAD (JWT) ---
    if ($path !== 'login') {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token)) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado. No se proporcionó token.']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));
            $usuario = $decoded->data;
            $rol = $usuario->rol;

            // --- INICIO: CONTROL DE ROLES (RBAC) ---
            $rutaPermitida = false;


            if (str_starts_with($path, '/ventas/sucursal/')) {
                if ($rol === 'vendedor' || $rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif (str_starts_with($path, 'ventas/por-categoria')) {
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
             elseif (str_starts_with($path, 'stock/bajo')) { 
                
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            else if (str_starts_with($path, '/ventas/top-productos')) {
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            else if (str_starts_with($path, 'ventas')) {
                if ($rol === 'vendedor' || $rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }elseif (str_starts_with($path, 'productos/imagen')) {
                if ($rol === 'administrador' || $rol === 'vendedor') {
                    $rutaPermitida = true;
                }
            }elseif (str_starts_with($path, 'productos/')) {
                if ($rol === 'administrador' || $rol === 'vendedor') {
                    $rutaPermitida = true;
                }
            } elseif (str_starts_with($path, 'productos')) {
                if ($rol === 'vendedor' || $rol === 'administrador') {
                    $rutaPermitida = true;
                }
            } elseif (str_starts_with($path, 'empleados')) {
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif (str_starts_with($path, 'horas-trabajadas/resumen')) { 
                // Solo el Admin puede LEER los logs
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif(str_starts_with($path, 'horas-trabajadas')) {
                $rutaPermitida = true;
            }
            elseif(str_starts_with($path, 'sucursales')) {
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif(str_starts_with($path, 'registro')) {
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif (str_starts_with($path, 'logs')) { // <-- AÑADIR ESTO
                // Solo el Admin puede LEER los logs
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif (str_starts_with($path, 'liquidacion')) { 
                
                if ($rol === 'administrador') {
                    $rutaPermitida = true;
                }
            }
            elseif (str_starts_with($path, 'categorias')) {
            if ($rol === 'vendedor' || $rol === 'administrador') {
                $rutaPermitida = true;
            }
            }   
           


            if ($rutaPermitida === false) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'mensaje' => 'Acceso denegado. Tu rol no tiene permisos.'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            // --- FIN: CONTROL DE ROLES ---

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Token inválido o expirado.', 'mensaje_detalle' => $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
    // --- FIN: GUARDIA DE SEGURIDAD ---


    // --- Lógica de Redirección (Proxy) ---
    $metodo = $request->getMethod();
    $body = $request->getBody();
    $queryParams = $request->getQueryParams();
    $targetUrl = '';
    
    if (str_starts_with($path, 'stock/bajo')) {
        $targetUrl = $servicios['productos'] . '/' . $path;
    } elseif (str_starts_with($path, 'productos')) {
        $targetUrl = $servicios['productos'] . '/' . $path;
    }elseif (str_starts_with($path, 'categorias')) {
    $targetUrl = $servicios['productos'] . '/' . $path;
    }
    elseif (str_starts_with($path, 'ventas')) {
        $targetUrl = $servicios['ventas'] . '/' . $path;
    } elseif (str_starts_with($path, 'login') || str_starts_with($path, 'empleados')) {
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    } elseif (str_starts_with($path, 'horas-trabajadas/resumen')){
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    }elseif (str_starts_with($path, 'horas-trabajadas')){
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    }elseif (str_starts_with($path, 'sucursales')){
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    }elseif (str_starts_with($path, 'registro')){
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    }elseif (str_starts_with($path, 'logs')){
        $targetUrl = $servicios['auditorias'] . '/' . $path;
    }elseif (str_starts_with($path, 'liquidacion')){
        $targetUrl = $servicios['usuarios'] . '/' . $path;
    }else {
        $response->getBody()->write(json_encode(['error' => 'Ruta no encontrada']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

   try {
        // 1. Preparar Cabeceras
        $headers = $request->getHeaders();
        
        // Quitamos las que causan conflicto
        unset($headers['Host']);
        unset($headers['Content-Length']);
        unset($headers['Transfer-Encoding']); // Agregamos esta por seguridad

        $guzzleOptions = [
            'headers' => $headers,
            'query' => $queryParams,
            'http_errors' => false
        ];

        // 2. Adjuntar cuerpo (Solo si no es GET)
        if ($metodo !== 'GET') {
            // --- ¡EL TRUCO DEL REWIND! ---
            // Rebobinamos el stream para asegurarnos de leer desde el principio
            if ($request->getBody()->isSeekable()) {
                $request->getBody()->rewind();
            }
            $guzzleOptions['body'] = $request->getBody();
        }

        // 3. Enviar
        $apiResponse = $guzzle->request($metodo, $targetUrl, $guzzleOptions);
        
        $response->getBody()->write($apiResponse->getBody()->getContents());
        return $response
                ->withStatus($apiResponse->getStatusCode())
                ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type'));

    } catch (RequestException $e) {
        $response->getBody()->write(json_encode(['error' => 'Servicio interno no disponible', 'mensaje' => $e->getMessage()]));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();