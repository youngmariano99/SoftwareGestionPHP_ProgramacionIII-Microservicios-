document.addEventListener('DOMContentLoaded', () => {
    
    // Si ya tengo token, no debería estar aquí -> al Dashboard
    if(localStorage.getItem('jwt_token')) {
        // Decodificamos para saber a dónde mandarlo (opcional, o directo a dashboard.php)
        window.location.href = 'dashboard.php'; 
        return;
    }

    const loginForm = document.getElementById('loginForm');
    if(loginForm) {
        loginForm.addEventListener('submit', manejarLogin);
    }
});

function manejarLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('errorMensaje');
    const btnSubmit = e.target.querySelector('button');

    // Feedback visual
    btnSubmit.disabled = true;
    btnSubmit.textContent = "Ingresando...";
    errorDiv.textContent = "";

    fetch('http://localhost:8000/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.token && data.rol) {
            // 1. Guardar Token
            localStorage.setItem('jwt_token', data.token);
            
            // 2. Decodificar para obtener ID
            const usuario = decodificarToken(data.token);
            
            if (!usuario || !usuario.id) {
                throw new Error("Token inválido");
            }

            // 3. Lógica de Redirección y Fichaje
            if (data.rol === 'vendedor') {
                registrarEntradaVendedor(data.token, usuario.id);
            } else {
                window.location.href = 'dashboard.php';
            }
            
        } else {
            throw new Error(data.mensaje || 'Credenciales incorrectas');
        }
    })
    .catch(error => {
        errorDiv.textContent = error.message;
        btnSubmit.disabled = false;
        btnSubmit.textContent = "Ingresar";
    });
}

function registrarEntradaVendedor(token, userId) {
    fetch('http://localhost:8000/horas-trabajadas/horario-entrada', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ usuario_id: userId })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Clock-in:', data.mensaje);
        // Redirigir SIEMPRE, haya éxito o warning (ya fichado)
        window.location.href = 'dashboard.php?seccion=vendedor';
    })
    .catch(err => {
        console.error('Error clock-in', err);
        // En caso de error de red, dejamos pasar igual al panel
        window.location.href = 'dashboard.php?seccion=vendedor';
    });
}

// Helper interno
function decodificarToken(token) {
    try {
        return JSON.parse(atob(token.split('.')[1])).data;
    } catch (e) {
        return null;
    }
}