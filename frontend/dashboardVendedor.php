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
    <p><span id="logout">(Cerrar sesión)</span></p>
    <div id="errorMensaje"></div>
    <div style="text-align: right; margin-bottom: 20px;">
    <button id="btnCerrarTurno" style="background-color: #ff4444; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        🛑 Finalizar Turno
    </button>
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

                <label for="prod-categoria">Categoría:</label>
                <input type="text" id="prod-categoria" list="lista-categorias" placeholder="Escribe o selecciona una..." required>

                <datalist id="lista-categorias"></datalist>

                <label for="prod-stock">Stock Inicial:</label>
                <input type="number" id="prod-stock" required>

                
                
                <button type="submit">Crear Producto</button>
            </form>
            <div id="msgProducto" style="color: green;"></div>
        </div>

        <div class="form-container">
        <h3>Registrar Venta</h3>
        <form id="formRegistrarVenta">
        
        <div style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px;">
            <div style="flex-grow: 1;">
                <label for="select-producto">Producto:</label>
                <select id="select-producto" style="width: 100%; padding: 8px;">
                    <option value="">-- Seleccionar Producto --</option>
                </select>
            </div>
            
            <div style="width: 100px;">
                <label for="input-cantidad">Cant:</label>
                <input type="number" id="input-cantidad" value="1" min="1" style="width: 100%;">
            </div>

            <button type="button" id="btn-agregar-carrito" style="background-color: #4CAF50;">+</button>
        </div>

        <table style="width: 100%; margin-bottom: 15px; background: #f9f9f9;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="carrito-body">
                </tbody>
        </table>

        <button type="submit" style="width: 100%; font-weight: bold; font-size: 1.1em;">✅ Finalizar Venta</button>
        </form>
        <div id="msgVenta" style="color: green; margin-top: 10px;"></div>
        </div>
        <div class="form-container">
            <h3>Productos Disponibles</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th> 
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaProductosBody">
                    </tbody>
            </table>
        </div>
        <div class="form-container">
            <h3>Ventas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>ID Empleado</th>
                        <th>ID Producto</th>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody id="tablaVentasVendedorBody"> <!-- ID CAMBIADO -->
                    </tbody>
            </table>
        </div>

        <div id="modalEditarProducto" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1001;">
                <div style="background: white; padding: 20px; border-radius: 8px; width: 300px;">
                    <h3>Editar Producto</h3>
                    <input type="hidden" id="edit-id">
                    
                    <label>Nombre:</label>
                    <input type="text" id="edit-nombre" style="width: 90%; margin-bottom: 10px;">

                    <label>Categoría:</label>
                    <input type="text" id="edit-categoria" list="lista-categorias-edit" style="width: 90%; margin-bottom: 10px;">
                    <datalist id="lista-categorias-edit"></datalist>
                    
                    <label>Precio:</label>
                    <input type="number" id="edit-precio" step="0.01" style="width: 90%; margin-bottom: 10px;">
                    
                    <label>Stock:</label>
                    <input type="number" id="edit-stock" style="width: 90%; margin-bottom: 20px;">
                    
                    <div style="display: flex; justify-content: space-between;">
                        <button onclick="guardarEdicionProducto()" style="background: #4CAF50; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Guardar</button>
                        <button onclick="document.getElementById('modalEditarProducto').style.display='none'" style="background: #ccc; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Cancelar</button>
                    </div>
                </div>
        </div>
    </div>
    

<script>
    // --- 1. VARIABLES GLOBALES ---
    const token = localStorage.getItem('jwt_token');
    const errorDiv = document.getElementById('errorMensaje');
    const bienvenida = document.getElementById('bienvenida');
    let carritoVenta = []; // Carrito global

    // --- 2. FUNCIONES AUXILIARES (Globales) ---

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

    // Dibuja la tabla del carrito visual
    function renderizarCarrito() {
        const tbody = document.getElementById('carrito-body');
        tbody.innerHTML = '';

        carritoVenta.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.nombre}</td>
                <td>${item.cantidad}</td>
                <td><button type="button" onclick="eliminarDelCarrito(${index})" style="background:red; color:white; border:none; cursor:pointer;">X</button></td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Elimina un item del carrito (Global para que el onclick lo vea)
    window.eliminarDelCarrito = function(index) {
        carritoVenta.splice(index, 1);
        renderizarCarrito();
    };

    // --- 3. LÓGICA DEL PANEL VENDEDOR ---

    function cargarPanelVendedor(idVendedor, idSucursal) {
        // Cargas iniciales
        cargarProductosVendedor();
        cargarVentasVendedor();
        cargarCategoriasSugeridas();

        // A. Lógica del Botón "+" (Agregar al carrito)
        // Movi esto aquí adentro para tener toda la lógica del panel junta
        const btnAgregar = document.getElementById('btn-agregar-carrito');
        // Clonamos el botón para eliminar listeners viejos si se recarga la función
        const newBtn = btnAgregar.cloneNode(true); 
        btnAgregar.parentNode.replaceChild(newBtn, btnAgregar);
        
        newBtn.addEventListener('click', () => {
            const select = document.getElementById('select-producto');
            const cantidadInput = document.getElementById('input-cantidad');
            
            const idProducto = select.value;
            // Usamos el operador ?. para evitar error si no hay selección
            const nombreProducto = select.options[select.selectedIndex]?.getAttribute('data-nombre');
            const cantidad = parseInt(cantidadInput.value);

            if (!idProducto || cantidad < 1) {
                alert("Selecciona un producto y una cantidad válida.");
                return;
            }

            carritoVenta.push({
                id: idProducto,
                nombre: nombreProducto,
                cantidad: cantidad
            });

            renderizarCarrito(); // Ahora sí funciona porque es global
            
            select.value = "";
            cantidadInput.value = "1";
        });

        // B. Formulario Crear Producto
        const formCrear = document.getElementById('formCrearProducto');
        formCrear.onsubmit = (e) => { // Usamos onsubmit para evitar listeners duplicados
            e.preventDefault();
            const nombre = document.getElementById('prod-nombre').value;
            const precio = parseFloat(document.getElementById('prod-precio').value);
            const stock = parseInt(document.getElementById('prod-stock').value, 10);
            const msgDiv = document.getElementById('msgProducto');

            const datosProducto = {
                nombre: document.getElementById('prod-nombre').value,
                categoria: document.getElementById('prod-categoria').value, 
                precio: parseFloat(document.getElementById('prod-precio').value),
                stock: parseInt(document.getElementById('prod-stock').value, 10),
                usuario_id: idVendedor,
                sucursal_id: idSucursal
            };

            fetch('http://localhost:8000/productos', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datosProducto)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.textContent = `Producto creado ID: ${data.id}`;
                    cargarProductosVendedor();
                    e.target.reset();
                } else {
                    msgDiv.textContent = `Error: ${data.mensaje}`;
                }
            })
            .catch(err => msgDiv.textContent = 'Error de red.');
        };

        // C. Formulario Registrar Venta (Finalizar)
        const formVenta = document.getElementById('formRegistrarVenta');
        formVenta.onsubmit = (e) => {
            e.preventDefault();
            const msgDiv = document.getElementById('msgVenta');

            if (carritoVenta.length === 0) {
                alert("El carrito está vacío.");
                return;
            }

            const ventaData = {
                id_empleado: idVendedor,
                sucursal_id: 1, // Hardcodeado por ahora
                productos: carritoVenta
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
                    msgDiv.textContent = `Venta #${data.id_venta} exitosa!`;
                    
                    // Limpiar todo
                    carritoVenta = [];
                    renderizarCarrito(); // Ahora sí se vacía la tabla visual
                    cargarProductosVendedor();
                    cargarVentasVendedor();
                } else {
                    msgDiv.textContent = `Error: ${data.mensaje}`;
                }
            })
            .catch(err => {
                console.error(err);
                msgDiv.textContent = 'Error al registrar venta.';
            });
        };


            // Botón Cerrar Turno
        const btnCerrarTurno = document.getElementById('btnCerrarTurno');
        if(btnCerrarTurno) {
            btnCerrarTurno.addEventListener('click', () => {
                if (!confirm('¿Finalizar turno?')) return;
                const usuario = decodificarToken(token);

                const datosVendedor = {
                    usuario_id: idVendedor, // Usamos la variable que ya trae la función cargarPanelVendedor
                    sucursal_id: idSucursal
                };

                fetch('http://localhost:8000/horas-trabajadas/horario-salida', { // Ajusta la ruta si la cambiaste
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datosVendedor)
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.mensaje || 'Turno cerrado');
                    if(data.status === 'success') {
                        localStorage.removeItem('jwt_token');
                        window.location.href = 'index.php';
                    }
                });
            });
        }
    }

    // Funciones de Carga de Datos
    function cargarProductosVendedor() {
        fetch('http://localhost:8000/productos', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(productos => {
            if (!Array.isArray(productos)) return; // Validación simple

            const tbody = document.getElementById('tablaProductosBody');
            tbody.innerHTML = '';
            
            const select = document.getElementById('select-producto');
            select.innerHTML = '<option value="">-- Seleccionar Producto --</option>'; 

           

            productos.forEach(prod => {
                // Tabla
                const tr = document.createElement('tr');

                const cat = prod.categoria || 'General';

                tr.innerHTML = `<td>${prod.id}</td>
                <td>${prod.nombre}</td>
                <td>${cat}</td>
                <td>$${prod.precio}</td>
                <td>${prod.stock}</td>
                <td>
                    <button onclick="abrirModalEditar('${prod.id}', '${prod.nombre}', ${prod.precio}, ${prod.stock}, '${cat}')" 
                            style="background: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                        ✏️ Editar
                    </button>
                </td>`;
                tbody.appendChild(tr);

                // Select
                if (prod.stock > 0) {
                    const option = document.createElement('option');
                    option.value = prod.id;
                    option.textContent = `${prod.nombre} ($${prod.precio}) - Stock: ${prod.stock}`;
                    option.setAttribute('data-nombre', prod.nombre);
                    select.appendChild(option);
                }
            });
        })
        .catch(console.error);
    }

    function cargarVentasVendedor() {
        fetch('http://localhost:8000/ventas', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(ventas => {
            const tbody = document.getElementById('tablaVentasVendedorBody');
            tbody.innerHTML = '';

            ventas.forEach(venta => {
                venta.productos.forEach(prod => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${venta._id}</td>
                        <td>${venta.usuario_id}</td>
                        <td>${prod.id_producto}</td>
                        <td>${prod.nombre}</td>
                        <td>${prod.cantidad}</td>
                        <td>$${prod.subtotal}</td>
                    `;
                    tbody.appendChild(tr);
                });
            });
        })
        .catch(console.error);
    }

    // --- 4. INICIALIZACIÓN (Main) ---
    document.addEventListener('DOMContentLoaded', () => {
        if (!token) {
            window.location.href = 'index.php';
            return;
        }

        const usuario = decodificarToken(token);
        if (!usuario) {
            localStorage.removeItem('jwt_token');
            window.location.href = 'index.php';
            return;
        }

        bienvenida.textContent = `Bienvenido, ${usuario.nombre} (Rol: ${usuario.rol}) (Sucursal: ${usuario.sucursal_id}) `;

        if (usuario.rol === 'vendedor') {
            document.getElementById('panel-vendedor').style.display = 'block';
            cargarPanelVendedor(usuario.id, usuario.sucursal_id);
        } else {
            errorDiv.textContent = 'Tu rol no es reconocido.';
        }
    });

    // Botón Logout
    document.getElementById('logout').addEventListener('click', () => {
        localStorage.removeItem('jwt_token');
        window.location.href = 'index.php';
    });

    function cargarCategoriasSugeridas() {
    fetch('http://localhost:8000/categorias', {
        method: 'GET',
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(categorias => {
        const lista = document.getElementById('lista-categorias');
        lista.innerHTML = ''; // Limpiar
        
        categorias.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            lista.appendChild(option);
        });
    })
    .catch(err => console.error("Error cargando categorías", err));
    }


     // --- FUNCIÓN 4: Abrir Modal de Edición ---
    window.abrirModalEditar = function(id, nombre, precio, stock, categoria) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-precio').value = precio;
    document.getElementById('edit-stock').value = stock;
    document.getElementById('edit-categoria').value = categoria || ''; // Nuevo
    
    cargarCategoriasModal(); // Cargamos sugerencias
    document.getElementById('modalEditarProducto').style.display = 'flex';
    };

    // --- FUNCIÓN 5: Guardar Cambios (PUT) ---
    // --- FUNCIÓN CORREGIDA PARA VENDEDOR ---
    window.guardarEdicionProducto = function() {
        const id = document.getElementById('edit-id').value;
        const usuario = decodificarToken(token); 

        const data = {
            nombre: document.getElementById('edit-nombre').value,
            precio: document.getElementById('edit-precio').value,
            stock: document.getElementById('edit-stock').value,
            categoria: document.getElementById('edit-categoria').value, // No olvidar este
            usuario_id: usuario.id,
            sucursal_id: 1 
        };

        fetch(`http://localhost:8000/productos/${id}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.status === 'success') {
                alert('✅ Producto actualizado correctamente.');
                document.getElementById('modalEditarProducto').style.display = 'none';
                
                // --- CAMBIO CLAVE AQUÍ ---
                // Usamos la función DEL VENDEDOR para recargar la tabla
                cargarProductosVendedor(); 
                
                // Eliminamos la llamada a cargarLogsAuditoria() porque el vendedor no ve logs
                // -------------------------
            } else {
                alert('❌ Error: ' + respuesta.mensaje);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al editar producto.');
        });
    };


    //HELPERS

    function cargarCategoriasModal() {
    fetch('http://localhost:8000/categorias', {
        method: 'GET',
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(cats => {
        const lista = document.getElementById('lista-categorias-edit');
        lista.innerHTML = '';
        cats.forEach(c => {
            const op = document.createElement('option');
            op.value = c;
            lista.appendChild(op);
        });
    });
    }

    function cargarGestionProductos() {
        fetch('http://localhost:8000/productos', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(productos => {
            listaProductosGlobal = productos; // Guardamos para el buscador
            renderizarTablaInventario(productos);
        })
        .catch(err => console.error("Error cargando inventario:", err));
    }



    
</script>


</body>
</html>