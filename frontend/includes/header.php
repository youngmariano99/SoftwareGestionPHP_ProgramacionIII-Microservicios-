<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Gestión</title>
    
    <style>
        body { font-family: sans-serif; margin: 0; padding-bottom: 50px; background-color: #f9f9f9; }
        
        /* Navbar Profesional */
        nav { background: #2c3e50; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        nav .brand { font-size: 1.2em; font-weight: bold; }
        nav ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 20px; }
        nav a { color: #ecf0f1; text-decoration: none; font-size: 0.95em; transition: color 0.3s; }
        nav a:hover { color: #3498db; }
        nav a.active { color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 2px; }
        
        /* Contenedor Principal */
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; background: white; min-height: 80vh; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-top: 20px; border-radius: 5px; }
        
        /* Tus estilos originales (Formularios, Tablas, etc.) */
        .form-container { border: 1px solid #ccc; padding: 15px; margin-top: 15px; border-radius: 8px; background: #fff; }
        .form-container h3 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .form-container input, .form-container select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-container button { padding: 10px 15px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .form-container button:hover { background-color: #34495e; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        
        #errorMensaje { color: #e74c3c; font-weight: bold; margin: 10px 0; text-align: center; }
        #mapa { height: 300px; border: 1px solid #ccc; margin-bottom: 15px; border-radius: 4px; }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <nav>
        <div class="brand">🏢 Sistema Gestión</div>
        
        <ul id="menu-navegacion">
            <li><a href="dashboard.php?seccion=inicio" class="<?= ($seccion == 'inicio') ? 'active' : '' ?>">🏠 Inicio</a></li>
            <li><a href="dashboard.php?seccion=sucursales" class="<?= ($seccion == 'sucursales') ? 'active' : '' ?>">📍 Sucursales</a></li>
            <li><a href="dashboard.php?seccion=empleados" class="<?= ($seccion == 'empleados') ? 'active' : '' ?>">👥 Empleados</a></li>
            <li><a href="dashboard.php?seccion=productos" class="<?= ($seccion == 'productos') ? 'active' : '' ?>">📦 Productos</a></li>
            <li><a href="dashboard.php?seccion=ventas" class="<?= ($seccion == 'ventas') ? 'active' : '' ?>">💰 Ventas</a></li>
            <li><a href="dashboard.php?seccion=logs" class="<?= ($seccion == 'logs') ? 'active' : '' ?>">🛡️ Auditoría</a></li>
        </ul>

        <div>
            <span id="nombreUsuario">Bienvenido</span> | 
            <span id="logout" style="cursor: pointer; color: #e74c3c; font-weight: bold;">Salir</span>
        </div>
    </nav>

    <div class="container">
        <div id="errorMensaje"></div>