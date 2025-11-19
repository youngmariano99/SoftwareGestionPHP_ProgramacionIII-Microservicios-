const tokenEmpleados = localStorage.getItem('jwt_token');

document.addEventListener('DOMContentLoaded', () => {
    cargarEmpleados();
    cargarSucursalesSelect(); // Necesario para el dropdown del formulario

    const form = document.getElementById('formRegistrarEmpleado');
    if (form) {
        form.addEventListener('submit', registrarEmpleado);
    }
});

function cargarEmpleados() {
    fetch('http://localhost:8000/empleados', {
        headers: { 'Authorization': `Bearer ${tokenEmpleados}` }
    })
    .then(res => res.json())
    .then(empleados => {
        const tbody = document.getElementById('tablaEmpleadosBody');
        tbody.innerHTML = '';
        empleados.forEach(emp => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${emp.id}</td>
                <td>${emp.nombre}</td>
                <td>${emp.apellido}</td>
                <td>${emp.rol}</td>
                <td>${emp.nombre_sucursal || 'N/A'}</td> 
                <td>$${parseFloat(emp.tarifa_hora || 0).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(console.error);
}

function cargarSucursalesSelect() {
    // Reutilizamos el endpoint de sucursales para llenar el select
    fetch('http://localhost:8000/sucursales', {
        headers: { 'Authorization': `Bearer ${tokenEmpleados}` }
    })
    .then(res => res.json())
    .then(sucursales => {
        const select = document.getElementById('emp-sucursal');
        select.innerHTML = '<option value="">-- Seleccionar Sucursal --</option>';
        
        sucursales.forEach(suc => {
            const option = document.createElement('option');
            option.value = suc.id;
            option.textContent = suc.nombre;
            select.appendChild(option);
        });
    });
}

function registrarEmpleado(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('msgEmpleado');
    msgDiv.textContent = "Procesando...";
    msgDiv.style.color = "blue";

    const data = {
        nombre: document.getElementById('emp-nombre').value,
        apellido: document.getElementById('emp-apellido').value,
        email: document.getElementById('emp-email').value,
        password: document.getElementById('emp-password').value,
        rol: document.getElementById('emp-rol').value,
        sucursal_id: document.getElementById('emp-sucursal').value,
        tarifa_hora: parseFloat(document.getElementById('emp-tarifa').value) || 0
    };

    // Validación básica
    if (data.rol === 'vendedor' && !data.sucursal_id) {
        msgDiv.style.color = 'red';
        msgDiv.textContent = 'Error: Un vendedor debe pertenecer a una sucursal.';
        return;
    }

    fetch('http://localhost:8000/registro', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${tokenEmpleados}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'success') {
            msgDiv.style.color = 'green';
            msgDiv.textContent = resp.mensaje;
            e.target.reset();
            cargarEmpleados(); // Recargar la tabla
        } else {
            msgDiv.style.color = 'red';
            msgDiv.textContent = `Error: ${resp.error || resp.mensaje}`;
        }
    })
    .catch(err => {
        msgDiv.style.color = 'red';
        msgDiv.textContent = 'Error de conexión.';
    });
}