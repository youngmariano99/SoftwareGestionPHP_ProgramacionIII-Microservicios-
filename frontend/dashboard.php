<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { font-family: sans-serif; }
        .panel { display: none; } /* Oculta ambos paneles por defecto */
        .form-container { border: 1px solid #ccc; padding: 15px; margin-top: 15px; border-radius: 8px; }
        .form-container h3 { margin-top: 0; }
        .form-container input { width: 95%; padding: 8px; margin-bottom: 10px; }
        .form-container button { padding: 10px 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        #errorMensaje { color: red; font-weight: bold; }
        #logout { cursor: pointer; color: blue; text-decoration: underline; }
    </style>
</head>
<body>

    <h1 id="bienvenida">Bienvenido a tu Dashboard</h1>
    <p>Cargando tu información... <span id="logout">(Cerrar sesión)</span></p>
    <div id="errorMensaje"></div>

    <div id="panel-admin" class="panel">
        <h2>Gestión de Empleados</h2>
        <table>
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
    </div>

    <div id="panel-vendedor" class="panel">
        <h2>Panel de Vendedor</h2>

        <div class="form-container">
            <h3>Crear Nuevo Producto</h3>
            <form id="formCrearProducto">
                <label for="prod-nombre">Nombre:</label>
                <input type="text" id="prod-nombre" required>
                
                <label for="prod-precio">Precio:</label>
                <input type="number" step="0.01" id="prod-precio" required>
                
                <label for="prod-stock">Stock Inicial:</label>
                <input type="number" id="prod-stock" required>
                
                <button type="submit">Crear Producto</button>
            </form>
            <div id="msgProducto" style="color: green;"></div>
        </div>

        <div class="form-container">
            <h3>Registrar Venta</h3>
            <form id="formRegistrarVenta">
                <label for="venta-producto-id">ID del Producto:</label>
                <input type="text" id="venta-producto-id" required>
                
                <label for="venta-cantidad">Cantidad:</label>
                <input type="number" id="venta-cantidad" required>
                
                <button type="submit">Registrar Venta</button>
            </form>
            <div id="msgVenta" style="color: green;"></div>
        </div>

        <div class="form-container">
            <h3>Productos Disponibles</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody id="tablaProductosBody">
                    </tbody>
            </table>
        </div>
    </div>
    <script>
    // --- DATOS GLOBALES ---
    const token = localStorage.getItem('jwt_token');
    const errorDiv = document.getElementById('errorMensaje');
    const bienvenida = document.getElementById('bienvenida');

    // --- FUNCIÓN HELPER PARA OBTENER EL TOKEN ---
    // (Esta es una función simple para decodificar la parte de "datos" del JWT)
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

    // --- FUNCIÓN HELPER PARA CERRAR SESIÓN ---
    document.getElementById('logout').addEventListener('click', () => {
        localStorage.removeItem('jwt_token');
        window.location.href = 'index.php';
    });

    // --- FUNCIÓN PRINCIPAL (EL "ROUTER") ---
    document.addEventListener('DOMContentLoaded', () => {
        if (!token) {
            // Si no hay token, no está logueado. Lo echamos.
            window.location.href = 'index.php';
            return;
        }

        const usuario = decodificarToken(token);
        
        if (!usuario) {
            // Token corrupto o inválido
            localStorage.removeItem('jwt_token');
            window.location.href = 'index.php';
            return;
        }

        // Personalizamos la bienvenida
        bienvenida.textContent = `Bienvenido, ${usuario.nombre} (Rol: ${usuario.rol})`;

        // --- AQUÍ OCURRE LA MAGIA ---
        // Decidimos qué panel mostrar basado en el rol del token
        if (usuario.rol === 'administrador') {
            document.getElementById('panel-admin').style.display = 'block';
            cargarPanelAdmin();
        } else if (usuario.rol === 'vendedor') {
            document.getElementById('panel-vendedor').style.display = 'block';
            cargarPanelVendedor(usuario.id); // Pasamos el ID del vendedor
        } else {
            errorDiv.textContent = 'Tu rol no es reconocido por el sistema.';
        }
    });

    // --- FUNCIÓN PARA EL PANEL DE ADMIN ---
    function cargarPanelAdmin() {
        fetch('http://localhost:8000/empleados', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar empleados');
            return response.json();
        })
        .then(empleados => {
            const tbody = document.getElementById('tablaEmpleadosBody');
            tbody.innerHTML = ''; // Limpiamos tabla
            empleados.forEach(emp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${emp.id}</td><td>${emp.nombre}</td><td>${emp.apellido}</td><td>${emp.rol}</td>`;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            errorDiv.textContent = `Error: ${error.message}`;
        });
    }

    // --- FUNCIÓN PARA EL PANEL DE VENDEDOR ---
    function cargarPanelVendedor(idVendedor) {
        // 1. Cargar la lista de productos
        cargarProductosVendedor();

        // 2. Escuchar el formulario de "Crear Producto"
        document.getElementById('formCrearProducto').addEventListener('submit', (e) => {
            e.preventDefault();
            const nombre = document.getElementById('prod-nombre').value;
            const precio = parseFloat(document.getElementById('prod-precio').value);
            const stock = parseInt(document.getElementById('prod-stock').value, 10);
            const msgDiv = document.getElementById('msgProducto');

            fetch('http://localhost:8000/productos', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ nombre, precio, stock })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.textContent = `Producto "${nombre}" creado con ID: ${data.id}`;
                    cargarProductosVendedor(); // Recargamos la lista de productos
                    e.target.reset(); // Limpiamos el formulario
                } else {
                    msgDiv.textContent = `Error: ${data.mensaje}`;
                }
            })
            .catch(err => {
                msgDiv.textContent = 'Error de red al crear producto.';
            });
        });

        // 3. Escuchar el formulario de "Registrar Venta"
        document.getElementById('formRegistrarVenta').addEventListener('submit', (e) => {
            e.preventDefault();
            const productoId = document.getElementById('venta-producto-id').value;
            const cantidad = parseInt(document.getElementById('venta-cantidad').value, 10);
            const msgDiv = document.getElementById('msgVenta');

            const ventaData = {
                id_empleado: idVendedor,
                productos: [
                    { "id": productoId, "cantidad": cantidad }
                ]
            };

            fetch('http://localhost:8000/ventas', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(ventaData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.textContent = `Venta registrada con ID: ${data.id_venta}`;
                    cargarProductosVendedor(); // Recargamos la lista (stock actualizado)
                    e.target.reset(); // Limpiamos el formulario
                } else {
                    msgDiv.textContent = `Error: ${data.mensaje}`;
                }
            })
            .catch(err => {
                msgDiv.textContent = 'Error de red al registrar venta.';
            });
        });
    }

    // Función que carga la tabla de productos para el vendedor
    function cargarProductosVendedor() {
        fetch('http://localhost:8000/productos', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(productos => {
            const tbody = document.getElementById('tablaProductosBody');
            tbody.innerHTML = ''; // Limpiamos la tabla
            productos.forEach(prod => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${prod.id}</td>
                    <td>${prod.nombre}</td>
                    <td>${prod.precio}</td>
                    <td>${prod.stock}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            errorDiv.textContent = 'Error al cargar productos.';
        });
    }

</script>

</body>
</html>