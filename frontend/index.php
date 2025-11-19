<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Gestión</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }
        h2 { margin-top: 0; color: #333; }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
        }
        button:hover { background-color: #34495e; }
        #errorMensaje { color: red; margin-top: 10px; font-size: 0.9em; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>🔐 Iniciar Sesión</h2>
        <form id="loginForm">
            <div>
                <input type="email" id="email" name="email" placeholder="Correo electrónico" required>
            </div>
            <div>
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit">Ingresar</button>
            <div id="errorMensaje"></div>
        </form>
    </div>

    <script src="js/login.js"></script>

</body>
</html>