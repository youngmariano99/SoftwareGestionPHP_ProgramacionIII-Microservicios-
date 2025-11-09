<?php
namespace App;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MongoDB\Client;

class ConexionMongo {
    
    private static $db = null;

    public static function obtenerConexion() {
        if (self::$db === null) {
            try {
                $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
                $dotenv->load();

                // URI por defecto para desarrollo local
                $uri = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
                $dbName = $_ENV['MONGODB_DB'] ?? 'sistema_administracion';

                // Configuración común para ambos entornos
                $opciones = [
                    'connectTimeoutMS' => 10000,
                    'socketTimeoutMS' => 10000,
                    'serverSelectionTimeoutMS' => 10000,
                ];

                // Si es Atlas (URI contiene "mongodb+srv://"), agregamos más opciones
                if (strpos($uri, 'mongodb+srv://') !== false) {
                    $opciones['retryWrites'] = true;
                    $opciones['w'] = 'majority';
                }

                $cliente = new Client($uri, $opciones);
                
                // Verificamos la conexión
                $cliente->listDatabases();
                
                self::$db = $cliente->selectDatabase($dbName);
                
            } catch (\Exception $e) {
                die("Error al conectar con MongoDB: " . $e->getMessage());
            }
        }
        
        return self::$db;
    }
}