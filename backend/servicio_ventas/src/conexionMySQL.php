<?php
// src/conexionMySQL.php

function getDbConnection() {
    // Lee las variables de entorno cargadas por Dotenv
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $db   = $_ENV['DB_NAME'] ?? 'gestionEmpleados';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
         $pdo = new PDO($dsn, $user, $pass, $options);
         return $pdo;
    } catch (\PDOException $e) {
         // En un entorno real, loguearías este error
         // error_log($e->getMessage());
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

// ========== NOTAS ADICIONALES SOBRE EL USO ==========

/**
 * Cómo usar este archivo en la aplicación:
 * 
 * 1. En cualquier archivo que necesite base de datos:
 *    require_once __DIR__ . '/../bootstrap.php';
 *    
 * 2. Obtener conexión:
 *    $conn = obtenerConexion();
 *    
 * 3. Verificar éxito:
 *    if (!$conn) {
 *        // Manejar error - mostrar mensaje al usuario o redirigir
 *        header("Location: error.php");
 *        exit;
 *    }
 *    
 * 4. Usar la conexión:
 *    $resultado = $conn->query("SELECT ...");
 *    
 * 5. Cerrar conexión cuando ya no se necesite:
 *    $conn->close();
 */

/**
 * Estructura esperada del archivo .env:
 * 
 * DB_HOST=127.0.0.1
 * DB_USER=tu_usuario
 * DB_PASSWORD=tu_contraseña_segura
 * DB_NAME=nombre_base_datos
 * 
 * El archivo .env debe estar en la raíz del proyecto y agregado al .gitignore
 */

?>