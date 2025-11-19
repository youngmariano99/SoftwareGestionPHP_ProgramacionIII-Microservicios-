<?php
use Slim\Factory\AppFactory;
use App\Controllers\ProductoController; // Importamos el nuevo controlador

require __DIR__ . '/../vendor/autoload.php';

// Cargar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app = AppFactory::create();

// ==========================================
// 📦 RUTAS DE PRODUCTOS (ALMACÉN)
// ==========================================

// Listar y Obtener uno
$app->get('/productos', ProductoController::class . ':listar');
$app->get('/productos/{id}', ProductoController::class . ':obtenerUno');

// Crear y Editar
$app->post('/productos', ProductoController::class . ':crear');
$app->put('/productos/{id}', ProductoController::class . ':editar');

// Stock y Categorías
$app->put('/productos/{id}/stock', ProductoController::class . ':actualizarStock');
$app->get('/stock/bajo', ProductoController::class . ':listarStockBajo');
$app->get('/categorias', ProductoController::class . ':listarCategorias');

$app->run();