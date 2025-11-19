<?php
use Slim\Factory\AppFactory;
use App\Controllers\VentaController;

require __DIR__ . '/../vendor/autoload.php';

// Cargar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Incluir conexión
require __DIR__ . '/../src/conexionMySQL.php';

$app = AppFactory::create();

// ==========================================
// 💰 RUTAS DE VENTAS (EL ORQUESTADOR)
// ==========================================

// Transacción Principal
$app->post('/ventas', VentaController::class . ':registrar');

// Listados
$app->get('/ventas', VentaController::class . ':listar');
$app->get('/ventas/sucursal/{id}', VentaController::class . ':porSucursal');

// Reportes Gráficos
$app->get('/ventas/top-productos', VentaController::class . ':topProductos');
$app->get('/ventas/por-categoria', VentaController::class . ':porCategoria');

$app->run();