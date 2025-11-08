<h2>Lista de Empleados</h2>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Rol</th>
        </tr>
    </thead>
    <tbody id="tablaEmpleadosBody">
        </tbody>
</table>

<div id="errorMensaje" style="color: red;"></div>

<script>
    // Se ejecuta cuando la página termina de cargar
    document.addEventListener('DOMContentLoaded', () => {
        
        // 1. Buscamos el "carnet" (token) en el navegador
        const token = localStorage.getItem('jwt_token');
        const errorDiv = document.getElementById('errorMensaje');
        
        if (!token) {
            // Si no hay token, no está logueado. Lo echamos.
            window.location.href = 'index.php';
            return;
        }

        // 2. Intentamos pedir los datos al API GATEWAY usando el token
        fetch('http://localhost:8000/empleados', {
            method: 'GET',
            headers: {
                // 3. ¡MUY IMPORTANTE! Adjuntamos el carnet en el header
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            if (response.status === 403) {
                // El Guardia nos rebotó por falta de permisos
                throw new Error('Acceso denegado. Tu rol no tiene permisos.');
            }
            if (response.status === 401) {
                // El token es inválido o expiró
                localStorage.removeItem('jwt_token'); // Limpiamos el token viejo
                window.location.href = 'index.php'; // Lo mandamos al login
                throw new Error('Token inválido o expirado.');
            }
            return response.json();
        })
        .then(empleados => {
            // 4. ¡Éxito! Tenemos los datos. Construimos la tabla.
            const tbody = document.getElementById('tablaEmpleadosBody');
            empleados.forEach(emp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${emp.id}</td>
                    <td>${emp.nombre}</td>
                    <td>${emp.apellido}</td>
                    <td>${emp.rol}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            errorDiv.textContent = error.message;
            console.error('Error:', error);
        });
    });
</script>