<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Recibimos qué sección quiere ver el usuario (por defecto 'inicio')
$seccion = $_GET['seccion'] ?? 'inicio';
?>



<?php include 'includes/header.php'; ?>

<?php


    // Definimos qué archivos están permitidos para evitar hackeos (LFI)
    $secciones_permitidas = [
        'inicio', 
        'sucursales', 
        'empleados', 
        'productos', 
        'ventas', 
        'logs'
    ];

    if (in_array($seccion, $secciones_permitidas)) {
        // Construimos la ruta: views/admin/nombre_seccion.php
        $archivo = "views/admin/$seccion.php";
        
        if (file_exists($archivo)) {
            include $archivo;
        } else {
            // Si el archivo aún no existe (porque lo estamos migrando), mostramos aviso
            echo "<div style='text-align:center; padding: 50px;'>";
            echo "<h2>🚧 Sección en Construcción: " . ucfirst($seccion) . "</h2>";
            echo "<p>Estamos mudando el contenido aquí...</p>";
            echo "</div>";
        }
    } else {
        echo "<h2 style='color:red'>❌ Sección no encontrada</h2>";
    }
?>

<?php include 'includes/footer.php'; ?>