<form id="loginForm">
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Ingresar</button>
    <div id="errorMensaje" style="color: red;"></div>
</form>

<script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Evita que el formulario se envíe
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('errorMensaje');

        // 1. Llamamos a nuestro API GATEWAY (puerto 8000)
        fetch('http://localhost:8000/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email, password: password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                // 2. ¡Éxito! Guardamos el "carnet" (token) en el navegador
                localStorage.setItem('jwt_token', data.token);
                
                // 3. Redirigimos al dashboard
                window.location.href = 'dashboard.php'; 
            } else {
                // Mostramos el error que nos dio la API
                errorDiv.textContent = data.mensaje || 'Error desconocido';
            }
        })
        .catch(error => {
            errorDiv.textContent = 'Error de conexión con la API.';
            console.error('Error:', error);
        });
    });
</script>