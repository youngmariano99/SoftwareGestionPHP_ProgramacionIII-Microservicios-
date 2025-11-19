<?php
use Slim\Factory\AppFactory;

// Importamos los controladores (Los Especialistas)
use App\Controllers\AuthController;
use App\Controllers\SucursalController;
use App\Controllers\AsistenciaController;
use App\Controllers\AdminController;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Incluir conexión a BD (namespace global)
require __DIR__ . '/../src/conexionMySQL.php';

$app = AppFactory::create();

// ==========================================
// 🚦 RUTAS DEL SISTEMA
// ==========================================

// --- Autenticación ---
$app->post('/login', AuthController::class . ':login');
$app->post('/registro', AuthController::class . ':registro');

// --- Gestión de Sucursales ---
$app->get('/sucursales', SucursalController::class . ':listar');
$app->post('/sucursales', SucursalController::class . ':crear');

// --- Asistencia (Vendedores) ---
$app->post('/horas-trabajadas/horario-entrada', AsistenciaController::class . ':registrarEntrada');
$app->put('/horas-trabajadas/horario-salida', AsistenciaController::class . ':registrarSalida');
$app->get('/horas-trabajadas/abierto/{user_id}', AsistenciaController::class . ':verificarTurnoAbierto');

// --- Administración y Reportes ---
$app->get('/empleados', AdminController::class . ':listarEmpleados');
$app->get('/horas-trabajadas/resumen', AdminController::class . ':resumenHoras');
$app->get('/liquidacion/{user_id}', AdminController::class . ':liquidarSueldo');


$app->run();