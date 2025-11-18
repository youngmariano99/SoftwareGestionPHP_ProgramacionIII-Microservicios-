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
    // --- ¡NUEVO! Función helper para decodificar el token ---
    // (La copiamos de dashboardVendedor.php)
    function decodificarToken(token) {
        try {
            const payloadBase64 = token.split('.')[1];
            const payloadJson = atob(payloadBase64);
            const payload = JSON.parse(payloadJson);
            return payload.data;
        } catch (e) {
            console.error('Error al decodificar token:', e);
            return null;
        }
    }

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
            if (data.token && data.rol) {
                // --- ¡LÓGICA MEJORADA! ---
                
                // 1. Guardamos el token
                localStorage.setItem('jwt_token', data.token);

                // 2. Decodificamos el token para obtener el ID
                const usuario = decodificarToken(data.token);
                
                if (!usuario || !usuario.id) {
                    throw new Error("No se pudo obtener el ID del usuario desde el token.");
                }

                // 3. ¡NUEVO! Si es vendedor, intentamos registrar la entrada (clock-in)
                if (data.rol === 'vendedor') {
                    
                    fetch('http://localhost:8000/horas-trabajadas/horario-entrada', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${data.token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ usuario_id: usuario.id })
                    })
                    .then(clockInRes => clockInRes.json())
                    .then(clockInData => {
                        console.log('Respuesta de Clock-in:', clockInData.mensaje);
                        // 4. Solo después del clock-in, redirigimos
                        window.location.href = 'dashboardVendedor.php';
                    })
                    .catch(err => {
                        // Si el clock-in falla, mostramos error pero redirigimos igual
                        console.error('Error en clock-in:', err);
                        window.location.href = 'dashboardVendedor.php';
                    });

                } else if (data.rol === 'administrador') {
                    // El admin no ficha, solo redirige
                    window.location.href = 'dashboard.php'; 
                }
                
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