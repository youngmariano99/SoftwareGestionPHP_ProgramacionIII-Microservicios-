<?php
namespace App;




// Carga las dependencias de Composer
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MongoDB\Client;

class ConexionMongo {
    
    private static $db = null;

    /**
     * Retorna una instancia de la base de datos de MongoDB
     * Usaremos una base de datos llamada 'tienda_db'
     */
    public static function obtenerConexion() {
        if (self::$db === null) {
            try {

                $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
                $dotenv->load();
               

                $uri = $_ENV['MONGODB_URI'] ?? '';
                $dbName = $_ENV['MONGODB_DB'] ?? 'sistema_administracion';

                

                if (empty($uri)) {
                    // Lanzar excepción si no hay URI configurada
                    throw new Exception("MongoDB URI no configurada");
                    // Esto interrumpe el flujo y va al catch
                }

                
               $cliente = new Client($uri, [
                    'connectTimeoutMS' => 10000,    // 10 segundos máximo para conectar
                    'socketTimeoutMS' => 10000,     // 10 segundos máximo para operaciones
                ]);
                
                // Seleccionamos la base de datos para este microservicio
                self::$db = $cliente->selectDatabase($dbName);
            } catch (\Exception $e) {
                // Manejo básico de error
                die("Error al conectar con MongoDB: " . $e->getMessage());
            }
        }
        
        return self::$db;
    }
}