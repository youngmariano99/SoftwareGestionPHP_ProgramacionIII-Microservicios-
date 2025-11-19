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
        /* ... (tu style existente) ... */
        #mapa { height: 300px; border: 1px solid #ccc; margin-bottom: 15px; }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.css" />
    <script src="https://unpkg.com/leaflet-geosearch@3.11.0/dist/geosearch.umd.js"></script>
</head>
<body>

    <h1 id="bienvenida">Bienvenido a tu Dashboard</h1>
    <p><span id="logout">(Cerrar sesión)</span></p>
    <div id="errorMensaje"></div>

    <div class="form-container">
    <h2>Gestión de Sucursales</h2>
            
            <form id="formCrearSucursal">
                    <h3>📍 Nueva Sucursal</h3>
                    <p>Utiliza el buscador (🔍) en el mapa para encontrar la dirección:</p>
                    
                    <div id="mapa"></div>

                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label for="suc-lat">Latitud:</label>
                            <input type="text" id="suc-lat" readonly required>
                        </div>
                        <div style="flex: 1;">
                            <label for="suc-lng">Longitud:</label>
                            <input type="text" id="suc-lng" readonly required>
                        </div>
                    </div>
                    <label for="suc-nombre">Nombre:</label>
                    <input type="text" id="suc-nombre" required>
                    
                    <label for="suc-direccion">Dirección:</label>
                    <input type="text" id="suc-direccion" required>
                    
                    <label for="suc-telefono">Teléfono (Opcional):</label>
                    <input type="text" id="suc-telefono">
                    
                    <button type="submit">Crear Sucursal</button>
                    <div id="msgSucursal" style="color: green; margin-top: 10px;"></div>
            </form>
            
            <hr style="margin: 20px 0;">

            <h3>Sucursales Actuales</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Coordenadas</th>
                    </tr>
                </thead>
                <tbody id="tablaSucursalesBody">
                    </tbody>
            </table>
        </div>

    <div id="panel-admin" class="panel">
            <h2>Gestión de Empleados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Rol</th>
                    <th>Sucursal</th> 
                    <th>Tarifa/hr</th> 
                </tr>
            </thead>
            <tbody id="tablaEmpleadosBody">
                </tbody>
        </table>

        <div class="form-container">
        <h2>Gestión de Empleados</h2>

            <form id="formRegistrarEmpleado">
                <h3>🧑‍💼 Registrar Nuevo Empleado</h3>
                
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label for="emp-nombre">Nombre:</label>
                        <input type="text" id="emp-nombre" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="emp-apellido">Apellido:</label>
                        <input type="text" id="emp-apellido" required>
                    </div>
                </div>
                
                <label for="emp-email">Email:</label>
                <input type="email" id="emp-email" required>
                
                <label for="emp-password">Contraseña Provisional:</label>
                <input type="password" id="emp-password" required>

                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label for="emp-rol">Rol:</label>
                        <select id="emp-rol" style="width: 100%; padding: 8px;">
                            <option value="vendedor">Vendedor</option>
                            <option value="administrador">Administrador</option>
                            </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="emp-sucursal">Sucursal:</label>
                        <select id="emp-sucursal" style="width: 100%; padding: 8px;" >
                            <option value="">-- Cargando sucursales... --</option>
                        </select>
                    </div>
                </div>

                <label for="emp-tarifa">Tarifa por Hora (ej: 1500.50):</label>
                <input type="number" step="0.01" id="emp-tarifa" >
                
                <button type="submit">Registrar Empleado</button>
                <div id="msgEmpleado" style="color: green; margin-top: 10px;"></div>
            </form>
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
                <tbody id="tablaVentasAdminBody"> <!-- ID CAMBIADO -->
                    </tbody>
            </table>
        </div>

        <div class="form-container">
            <h2>📈 Reportes de Horas y Liquidaciones</h2>

            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Sucursal</th>
                        <th>Total de Horas (Decimal)</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaResumenHorasBody">
                    </tbody>
            </table>
        </div>

        <div class="form-container">
            <h2>📊 Estadísticas de Negocio</h2>
        
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            
                <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                    <h3 style="text-align: center;">🏆 Top 5 Productos Más Vendidos</h3>
                    <canvas id="chartTopProductos"></canvas>
                </div>

                <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                <h3 style="text-align: center;">🏢 Ventas por Sucursal</h3>
                
                <div style="margin-bottom: 10px; text-align: center;">
                    <select id="select-sucursal-reporte" onchange="cargarReporteSucursal()">
                        <option value="">-- Selecciona una Sucursal --</option>
                    </select>
                </div>
                
                <div id="infoSucursal" style="text-align: center; display: none;">
                    <h1 style="color: green; margin: 10px 0;" id="totalVentasSucursal">$0.00</h1>
                    <p>Total recaudado histórico</p>
                    <button onclick="verDetalleSucursal()" style="margin-top: 5px; font-size: 0.8em;">Ver listado detallado</button>
                </div>

                <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                    <h3 style="text-align: center;">🍕 Ventas por Categoría</h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                    <canvas id="chartVentasCategoria"></canvas>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div class="form-container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>📦 Gestión de Inventario</h2>
                <button onclick="verStockBajo()" style="background-color: #ff9800; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">
                    ⚠️ Ver Alertas de Stock Bajo
                </button>
            </div>

            <input type="text" id="buscador-productos" placeholder="Filtrar productos..." onkeyup="filtrarProductos()" style="margin-bottom: 10px; padding: 8px; width: 100%; box-sizing: border-box;">

            <div style="max-height: 300px; overflow-y: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th> 
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaGestionProductosBody">
                        </tbody>
                </table>
            </div>
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

        <div class="form-container">
            <h2>🛡️ Logs del Sistema (Auditoría)</h2>
            <p>Mostrando las últimas 100 acciones registradas.</p>

            <table style="font-size: 0.9em;">
                <thead>
                    <tr>
                        <th>Cuándo</th>
                        <th>Usuario</th>
                        <th>Sucursal</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody id="tablaAuditoriaBody">
                    </tbody>
            </table>
        </div>

    </div>

 

    <div id="modalDetalle" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background: white; padding: 20px; border-radius: 8px; width: 80%; max-width: 800px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 id="tituloModal" style="margin: 0;">Detalle</h2>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 1.5em; cursor: pointer;">&times;</button>
            </div>
            
            <div id="contenidoModal">
                </div>
        </div>
    </div>

    <script>
    // --- DATOS GLOBALES ---
    const token = localStorage.getItem('jwt_token');
    const errorDiv = document.getElementById('errorMensaje');
    const bienvenida = document.getElementById('bienvenida');
    let mapa = null;       // Objeto del mapa
    let marcador = null;   // El pin rojo en el mapa
    // --- VARIABLES GLOBALES PARA GRÁFICOS ---
    let chartProductos = null;
    let ventasSucursalActual = []; // Para guardar la lista de la sucursal seleccionada
    // --- VARIABLES GLOBALES INVENTARIO ---
    let listaProductosGlobal = []; // Para el buscador local

    // --- FUNCIÓN HELPER PARA OBTENER EL TOKEN ---
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
            window.location.href = 'index.php';
            return;
        }

        const usuario = decodificarToken(token);
        
        if (!usuario) {
            localStorage.removeItem('jwt_token');
            window.location.href = 'index.php';
            return;
        }

        bienvenida.textContent = `Bienvenido, ${usuario.nombre} (Rol: ${usuario.rol})`;

        if (usuario.rol === 'administrador') {
            document.getElementById('panel-admin').style.display = 'block';
            cargarPanelAdmin();
        } else {
            errorDiv.textContent = 'Tu rol no es reconocido por el sistema.';
        }
    });

    // --- FUNCIÓN PARA EL PANEL DE ADMIN ---
    function cargarPanelAdmin() {
        cargarVentasAdmin(); // CAMBIADO: Ahora carga las ventas del admin
        cargarSucursales();     // Carga la tabla de sucursales
        inicializarMapa();      // Prepara el mapa interactivo
        setupFormSucursal();    // Activa el formulario de envío
        setupFormEmpleado();  // Activa el nuevo formulario
        cargarResumenHoras();
        cargarLogsAuditoria();
        cargarTopProductos();      // Dibuja el gráfico
        cargarSucursalesReporte(); // Llena el select del reporte
        cargarGestionProductos(); // Carga el inventario inicial
        cargarVentasPorCategoria() // Carga el nuevo grafico de ventas por categoria

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
                tbody.innerHTML = '';
                empleados.forEach(emp => {
                    const tr = document.createElement('tr');
                    
                    // --- ¡LÍNEA MODIFICADA! ---
                    tr.innerHTML = `
                        <td>${emp.id}</td>
                        <td>${emp.nombre}</td>
                        <td>${emp.apellido}</td>
                        <td>${emp.rol}</td>
                        <td>${emp.nombre_sucursal || 'N/A'}</td> <td>$${parseFloat(emp.tarifa_hora).toFixed(2)}</td> `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                errorDiv.textContent = `Error: ${error.message}`;
            });
    }

    



    // Función que carga la tabla de ventas para el ADMIN
    function cargarVentasAdmin() {
        fetch('http://localhost:8000/ventas', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(ventas => {
            const tbody = document.getElementById('tablaVentasAdminBody');
            tbody.innerHTML = '';

            // 1. Recorremos las "Cajas" (Ventas)
            ventas.forEach(venta => {
                
                // 2. Recorremos los "Items" dentro de cada caja
                venta.productos.forEach(prod => {
                    const tr = document.createElement('tr');
                    
                    // Nota: Usamos venta.usuario_id (nombre nuevo) y prod.subtotal
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
        .catch(err => {
            errorDiv.textContent = 'Error al cargar ventas.';
            console.error(err);
        });
    }


        // --- NUEVA FUNCIÓN: INICIALIZAR MAPA ---
    // REEMPLAZA tu función inicializarMapa con esta:
    function inicializarMapa() {
        // Si el mapa ya fue creado, no hacer nada
        if (mapa) return; 

        // SOLUCIÓN (Bug 1): Esperar 100ms a que el DIV se renderice
        // Esto soluciona el problema de "display: none"
        setTimeout(() => {
            // Coordenadas de Argentina
            mapa = L.map('mapa').setView([-34.61, -64.38], 4); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            // SOLUCIÓN (UX 2): Instalar el buscador (Geocoding)
            const provider = new GeoSearch.OpenStreetMapProvider();
            const searchControl = new GeoSearch.GeoSearchControl({
                provider: provider,
                style: 'bar', // Muestra una barra de búsqueda
                autoClose: true,
                keepResult: true, // Mantiene el pin en el mapa
                searchLabel: 'Buscar dirección (ej: Corrientes 100, CABA)'
            });
            mapa.addControl(searchControl);

            // Escuchar cuando el usuario selecciona un resultado
            mapa.on('geosearch/showlocation', function(resultado) {
                // "resultado" es un objeto con toda la info
                const loc = resultado.location; // loc.x = lng, loc.y = lat
                
                // 1. Autocompletar el formulario
                document.getElementById('suc-lat').value = loc.y.toFixed(6);
                document.getElementById('suc-lng').value = loc.x.toFixed(6);
                
                // 2. Autocompletar la dirección si está vacía
                const dirInput = document.getElementById('suc-direccion');
                if (dirInput.value === '') {
                    dirInput.value = loc.label.split(',').slice(0, 3).join(','); // Tomamos las primeras 3 partes
                }
                
                // 3. Mover el marcador (el pin)
                if (marcador) marcador.remove();
                marcador = L.marker([loc.y, loc.x]).addTo(mapa);
            });

        }, 100); // 100ms de espera
    }

    // --- NUEVA FUNCIÓN: CARGAR TABLA DE SUCURSALES ---
    // --- MODIFICADA: AHORA HACE DOS COSAS ---
    function cargarSucursales() {
        fetch('http://localhost:8000/sucursales', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(sucursales => {
            // 1. Llenar la tabla (como antes)
            const tbody = document.getElementById('tablaSucursalesBody');
            tbody.innerHTML = '';
            
            // 2. Llenar el NUEVO SELECT de empleados
            const selectSucursal = document.getElementById('emp-sucursal');
            selectSucursal.innerHTML = '<option value="">-- Seleccionar Sucursal --</option>'; // Reset

            sucursales.forEach(suc => {
                // Tarea 1: Llenar la tabla
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${suc.id}</td>
                    <td>${suc.nombre}</td>
                    <td>${suc.direccion}</td>
                    <td>${suc.latitud}, ${suc.longitud}</td>
                `;
                tbody.appendChild(tr);

                // Tarea 2: Llenar el select
                const option = document.createElement('option');
                option.value = suc.id; // Guardamos el ID
                option.textContent = suc.nombre; // Mostramos el Nombre
                selectSucursal.appendChild(option);
            });
        })
        .catch(err => errorDiv.textContent = 'Error al cargar sucursales');
    }

    // --- NUEVA FUNCIÓN: GUARDAR SUCURSAL (SUBMIT) ---
    function setupFormSucursal() {
        const form = document.getElementById('formCrearSucursal');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const msgDiv = document.getElementById('msgSucursal');

            const data = {
                nombre: document.getElementById('suc-nombre').value,
                direccion: document.getElementById('suc-direccion').value,
                telefono: document.getElementById('suc-telefono').value,
                latitud: document.getElementById('suc-lat').value,
                longitud: document.getElementById('suc-lng').value
            };

            fetch('http://localhost:8000/sucursales', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.textContent = data.mensaje;
                    form.reset(); // Limpiar formulario
                    if (marcador) marcador.remove(); // Limpiar pin
                    cargarSucursales(); // Recargar tabla
                } else {
                    msgDiv.textContent = `Error: ${data.error}`;
                }
            })
            .catch(err => msgDiv.textContent = 'Error de red.');
        });
    }
    
    // --- NUEVA FUNCIÓN: PARA EL FORMULARIO DE REGISTRO ---
    function setupFormEmpleado() {
        const form = document.getElementById('formRegistrarEmpleado');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const msgDiv = document.getElementById('msgEmpleado');
            msgDiv.style.color = 'red'; // Empezamos asumiendo un error

            const data = {
                nombre: getValueOrNull(document.getElementById('emp-nombre').value),
                apellido: getValueOrNull(document.getElementById('emp-apellido').value),
                email: getValueOrNull(document.getElementById('emp-email').value),
                password: getValueOrNull(document.getElementById('emp-password').value),
                rol: getValueOrNull(document.getElementById('emp-rol').value),
                sucursal_id: getValueOrNull(document.getElementById('emp-sucursal').value),
                tarifa_hora: getValueOrNull(document.getElementById('emp-tarifa').value, true)
            };

            if (data.rol == "vendedor") {
                    // Validación simple
                if (!data.sucursal_id) {
                    msgDiv.textContent = 'Por favor, selecciona una sucursal.';
                    return;
                }
            }
            

            fetch('http://localhost:8000/registro', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`, // ¡IMPORTANTE! Ahora está protegida
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.style.color = 'green';
                    msgDiv.textContent = data.mensaje;
                    form.reset(); 
                    
                    // ¡Recargamos la lista de empleados para ver al nuevo!
                    // (Asumiendo que la función que llena 'tablaEmpleadosBody' 
                    // se llama desde cargarPanelAdmin)
                    cargarPanelAdmin(); // Recargamos todo el panel
                } else {
                    msgDiv.textContent = `Error: ${data.error || data.mensaje}`;
                }
            })
            .catch(err => msgDiv.textContent = 'Error de red.');
        });
    }

    // --- NUEVA FUNCIÓN: Cargar Resumen de Horas (Sprint 4) ---
    function cargarResumenHoras() {
        // Usamos la ruta específica que protegiste en el gateway
        fetch('http://localhost:8000/horas-trabajadas/resumen', { 
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => {
            if (!res.ok) throw new Error('Error al cargar resumen');
            return res.json();
        })
        .then(resumen => {
            const tbody = document.getElementById('tablaResumenHorasBody');
            tbody.innerHTML = '';
            
            resumen.forEach(item => {
                const tr = document.createElement('tr');
                // Formateamos los números para que se vean bien
                const horas = parseFloat(item.total_horas_decimal).toFixed(2);
                
                tr.innerHTML = `
                    <td>${item.nombre} ${item.apellido} (ID: ${item.id})</td>
                    <td>${item.sucursal_nombre || 'N/A'}</td>
                    <td><b>${horas} hs</b></td>
                    <td><button onclick="liquidarEmpleado(${item.id})">Calcular Sueldo</button></td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error(err);
            errorDiv.textContent = 'Error al cargar el resumen de horas.';
        });
    }

    // --- NUEVA FUNCIÓN: Calcular Liquidación (Sprint 4) ---
    // La hacemos global (window.) para que el 'onclick' del botón la pueda ver
    window.liquidarEmpleado = function(userId) {
        // Llamamos al endpoint de liquidación que creamos
        fetch(`http://localhost:8000/liquidacion/${userId}`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                // Mostramos un alert simple con los resultados del cálculo
                const mensaje = `
                    Liquidación para Empleado ID: ${data.usuario_id}\n
                    Total Horas Registradas: ${data.total_horas_decimal} hs\n
                    Tarifa: $${data.tarifa_por_hora} / hora\n
                    --------------------\n
                    TOTAL A PAGAR: $${data.total_a_pagar}
                `;
                alert(mensaje);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al calcular la liquidación.');
        });
    }


    // --- NUEVA FUNCIÓN: Cargar Logs de Auditoría (Sprint 4) ---
    function cargarLogsAuditoria() {
        fetch('http://localhost:8000/logs', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(logs => {
            const tbody = document.getElementById('tablaAuditoriaBody');
            tbody.innerHTML = '';
            
            logs.forEach(log => {
                const tr = document.createElement('tr');
                
                // Damos formato a la fecha para que sea más legible
                const fecha = new Date(log.creado_en).toLocaleString('es-AR');
                const usuario = log.usuario_nombre ? `${log.usuario_nombre} ${log.usuario_apellido}` : 'Sistema';
                
                // Resaltamos los fallos en rojo
                const esError = log.tipo_accion.includes('FALLO') || log.tipo_accion.includes('ERROR');
                const estiloError = esError ? 'style="color: red; font-weight: bold;"' : '';

                tr.innerHTML = `
                    <td>${fecha}</td>
                    <td>${usuario}</td>
                    <td>${log.sucursal_nombre || 'N/A'}</td>
                    <td ${estiloError}>${log.tipo_accion}</td>
                    <td ${estiloError}>${log.descripcion}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error("Error al cargar logs:", err);
            errorDiv.textContent = 'Error al cargar los logs de auditoría.';
        });
    }

        // --- FUNCIÓN 1: Cargar Top Productos (Gráfico) ---
    function cargarTopProductos() {
        fetch('http://localhost:8000/ventas/top-productos', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(data => {
            // Preparamos los datos para Chart.js
            const nombres = data.map(item => item.nombre);
            const cantidades = data.map(item => item.total_vendido);

            const ctx = document.getElementById('chartTopProductos').getContext('2d');
            
            // Si ya existía el gráfico, lo destruimos para no sobreescribir
            if (chartProductos) chartProductos.destroy();

            chartProductos = new Chart(ctx, {
                type: 'bar', // Tipo de gráfico: Barras
                data: {
                    labels: nombres,
                    datasets: [{
                        label: 'Unidades Vendidas',
                        data: cantidades,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true } }
                }
            });
        })
        .catch(err => console.error("Error cargando top productos:", err));
    }

    // --- FUNCIÓN 2: Llenar el Select de Sucursales para Reportes ---
    function cargarSucursalesReporte() {
        // Reutilizamos el endpoint de sucursales
        fetch('http://localhost:8000/sucursales', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(sucursales => {
            const select = document.getElementById('select-sucursal-reporte');
            select.innerHTML = '<option value="">-- Selecciona una Sucursal --</option>';
            sucursales.forEach(suc => {
                const option = document.createElement('option');
                option.value = suc.id;
                option.textContent = suc.nombre;
                select.appendChild(option);
            });
        });
    }

    // --- FUNCIÓN 3: Cargar Datos de la Sucursal Seleccionada ---
    // Esta función se llama cada vez que cambias el select (onchange)
    window.cargarReporteSucursal = function() {
        const idSucursal = document.getElementById('select-sucursal-reporte').value;
        const divInfo = document.getElementById('infoSucursal');
        const h1Total = document.getElementById('totalVentasSucursal');

        if (!idSucursal) {
            divInfo.style.display = 'none';
            return;
        }

        fetch(`http://localhost:8000/ventas/sucursal/${idSucursal}`, {
            method: 'GET',
            headers: { 
                'Authorization': `Bearer ${token}`, // <--- Revisa esta línea con cuidado
                'Content-Type': 'application/json'
            }
    })
    .then(res => res.json())
    .then(ventas => {
        
        // --- ¡AÑADIR ESTO! Guardamos los datos en memoria ---
        ventasSucursalActual = ventas;
        // ---------------------------------------------------

        let totalRecaudado = 0;
        ventas.forEach(venta => {
            totalRecaudado += parseFloat(venta.total);
        });

        h1Total.textContent = `$${totalRecaudado.toFixed(2)}`;
        divInfo.style.display = 'block';
    })
    };

    // --- NUEVA FUNCIÓN: Abrir Modal con Detalle ---
    window.verDetalleSucursal = function() {
        if (ventasSucursalActual.length === 0) {
            alert("No hay ventas para mostrar.");
            return;
        }

        const titulo = document.getElementById('tituloModal');
        const contenido = document.getElementById('contenidoModal');
        const select = document.getElementById('select-sucursal-reporte');
        const nombreSucursal = select.options[select.selectedIndex].text;

        // 1. Configurar Título
        titulo.textContent = `Detalle de Ventas: ${nombreSucursal}`;

        // 2. Construir Tabla HTML
        let html = `
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#eee;">
                        <th style="border:1px solid #ddd; padding:8px;">ID Venta</th>
                        <th style="border:1px solid #ddd; padding:8px;">Fecha</th>
                        <th style="border:1px solid #ddd; padding:8px;">Usuario</th>
                        <th style="border:1px solid #ddd; padding:8px;">Productos</th>
                        <th style="border:1px solid #ddd; padding:8px;">Total</th>
                    </tr>
                </thead>
                <tbody>
        `;

        ventasSucursalActual.forEach(v => {
            // Formateamos la lista de productos en un string bonito
            const listaProductos = v.productos.map(p => 
                `<div>${p.cantidad}x ${p.nombre} ($${p.subtotal})</div>`
            ).join('');

            html += `
                <tr>
                    <td style="border:1px solid #ddd; padding:8px;">#${v._id}</td>
                    <td style="border:1px solid #ddd; padding:8px;">${new Date(v.fecha).toLocaleDateString()}</td>
                    <td style="border:1px solid #ddd; padding:8px;">ID: ${v.usuario_id}</td>
                    <td style="border:1px solid #ddd; padding:8px;">${listaProductos}</td>
                    <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">$${v.total}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        contenido.innerHTML = html;

        // 3. Mostrar Modal
        document.getElementById('modalDetalle').style.display = 'flex';
    };

    // --- Función para cerrar modal ---
    window.cerrarModal = function() {
        document.getElementById('modalDetalle').style.display = 'none';
    };

    // Cerrar si hacen clic fuera del modal (fondo oscuro)
    document.getElementById('modalDetalle').addEventListener('click', (e) => {
        if (e.target.id === 'modalDetalle') cerrarModal();
    });

        // --- FUNCIÓN 1: Cargar TODOS los productos (Gestión) ---
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

    // --- FUNCIÓN 2: Ver Stock Bajo (Alerta) ---
    window.verStockBajo = function() {
        fetch('http://localhost:8000/stock/bajo', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(productos => {
            if (productos.length === 0) {
                alert("¡Todo en orden! No hay productos con stock crítico (<= 5).");
                cargarGestionProductos(); // Volvemos a mostrar todos
            } else {
                renderizarTablaInventario(productos); // Mostramos solo los críticos
                alert(`⚠️ Atención: Se encontraron ${productos.length} productos con stock bajo.`);
            }
        })
        .catch(err => alert("Error al consultar stock bajo."));
    };

    // --- Helper para dibujar la tabla ---
    function renderizarTablaInventario(lista) {
        const tbody = document.getElementById('tablaGestionProductosBody');
        tbody.innerHTML = '';
        
        lista.forEach(prod => {
            const tr = document.createElement('tr');
            // Si el stock es bajo, lo pintamos de rojo suave
            const estiloAlerta = prod.stock <= 5 ? 'background-color: #ffebee;' : '';
            
            tr.style = estiloAlerta;
            tr.innerHTML = `
               <td>${prod.nombre}</td>
                <td>${prod.categoria || 'Sin Categoría'}</td> 
                <td>$${prod.precio}</td>
                <td style="font-weight: bold;">${prod.stock}</td>
                <td>
                    <button onclick="abrirModalEditar('${prod.id}', '${prod.nombre}', ${prod.precio}, ${prod.stock}, '${prod.categoria || ''}')" ...>
                    ✏️ Editar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- FUNCIÓN 3: Buscador en tiempo real (Frontend) ---
    window.filtrarProductos = function() {
        const texto = document.getElementById('buscador-productos').value.toLowerCase();
        const filtrados = listaProductosGlobal.filter(p => 
            p.nombre.toLowerCase().includes(texto)
        );
        renderizarTablaInventario(filtrados);
    };

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
    window.guardarEdicionProducto = function() {
    const id = document.getElementById('edit-id').value;
    const usuario = decodificarToken(token);

    const data = {
        nombre: document.getElementById('edit-nombre').value,
        categoria: document.getElementById('edit-categoria').value, // Nuevo
        precio: document.getElementById('edit-precio').value,
        stock: document.getElementById('edit-stock').value,
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
                cargarGestionProductos(); // Recargar la lista
                
                // También actualizamos la tabla de auditoría para ver el cambio reflejado
                cargarLogsAuditoria(); 
            } else {
                alert('❌ Error: ' + respuesta.mensaje);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al editar producto.');
        });
    };

        // --- FUNCIÓN NUEVA: Cargar Gráfico de Categorías ---
    function cargarVentasPorCategoria() {
        fetch('http://localhost:8000/ventas/por-categoria', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(data => {
            const categorias = data.map(item => item.categoria);
            const totales = data.map(item => item.total);

            const ctx = document.getElementById('chartVentasCategoria').getContext('2d');
            
            new Chart(ctx, {
                type: 'pie', // ¡Gráfico de Torta!
                data: {
                    labels: categorias,
                    datasets: [{
                        data: totales,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        })
        .catch(err => console.error("Error cargando categorías:", err));
    }

    //HELPER

    function getValueOrNull(value, isNumber = false) {
    const val = value.trim();
    if (val === '') return null;
    return isNumber ? parseFloat(val) : val;
    }

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


    
    </script>

</body>
</html>