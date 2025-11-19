// Variables Globales del Módulo
let carritoVenta = [];
const tokenVendedor = localStorage.getItem('jwt_token');
let usuarioGlobal = null;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Obtener usuario del token
    try {
        const payload = JSON.parse(atob(tokenVendedor.split('.')[1]));
        usuarioGlobal = payload.data;
    } catch (e) {
        console.error("Error token", e);
        window.location.href = 'index.php';
        return;
    }

    // 2. Cargas Iniciales
    cargarProductosVendedor();
    cargarVentasVendedor();
    cargarCategoriasSugeridas();

    // 3. Listeners
    document.getElementById('btn-agregar-carrito').addEventListener('click', agregarAlCarrito);
    document.getElementById('formRegistrarVenta').addEventListener('submit', finalizarVenta);
    document.getElementById('formCrearProducto').addEventListener('submit', crearProducto);
    
    const btnTurno = document.getElementById('btnCerrarTurno');
    if(btnTurno) btnTurno.addEventListener('click', cerrarTurno);

    // Modal Listeners
    document.getElementById('btnGuardarEdicion').addEventListener('click', guardarEdicionProducto);
    document.getElementById('btnCancelarEdicion').addEventListener('click', () => {
        document.getElementById('modalEditarProducto').style.display = 'none';
    });
});

// --- LÓGICA DEL CARRITO ---

function agregarAlCarrito() {
    const select = document.getElementById('select-producto');
    const cantidadInput = document.getElementById('input-cantidad');
    
    const idProducto = select.value;
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

    renderizarCarrito();
    
    // Reset inputs para seguir vendiendo rápido
    select.value = "";
    cantidadInput.value = "1";
    select.focus();
}

function renderizarCarrito() {
    const tbody = document.getElementById('carrito-body');
    tbody.innerHTML = '';

    carritoVenta.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding: 10px;">${item.nombre}</td>
            <td style="padding: 10px; text-align: center;">${item.cantidad}</td>
            <td style="padding: 10px; text-align: center;">
                <button type="button" onclick="eliminarItemCarrito(${index})" 
                style="background:#e74c3c; color:white; border:none; border-radius:4px; cursor:pointer; padding: 5px 10px;">X</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Hacemos esta función global para que el HTML pueda llamarla
window.eliminarItemCarrito = function(index) {
    carritoVenta.splice(index, 1);
    renderizarCarrito();
};

function finalizarVenta(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('msgVenta');

    if (carritoVenta.length === 0) {
        alert("El carrito está vacío.");
        return;
    }

    const ventaData = {
        id_empleado: usuarioGlobal.id,
        sucursal_id: usuarioGlobal.sucursal_id,
        productos: carritoVenta
    };

    fetch('http://localhost:8000/ventas', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${tokenVendedor}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(ventaData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            msgDiv.textContent = `✅ Venta #${data.id_venta} exitosa!`;
            msgDiv.style.color = 'green';
            carritoVenta = [];
            renderizarCarrito();
            cargarVentasVendedor(); 
            cargarProductosVendedor(); // Actualizar stock
            
            setTimeout(() => msgDiv.textContent = '', 3000); // Borrar mensaje
        } else {
            msgDiv.textContent = `❌ Error: ${data.mensaje}`;
            msgDiv.style.color = 'red';
        }
    })
    .catch(err => msgDiv.textContent = 'Error de conexión.');
}

// --- CARGA DE DATOS ---

function cargarProductosVendedor() {
    fetch('http://localhost:8000/productos', {
        headers: { 'Authorization': `Bearer ${tokenVendedor}` }
    })
    .then(res => res.json())
    .then(productos => {
        const tbody = document.getElementById('tablaProductosBody');
        const select = document.getElementById('select-producto');
        
        tbody.innerHTML = '';
        select.innerHTML = '<option value="">-- Seleccionar Producto --</option>';

        productos.forEach(prod => {
            // Llenar Tabla
            const tr = document.createElement('tr');
            // Formateamos un poco el objeto para pasarlo al modal
            const prodString = JSON.stringify(prod).replace(/"/g, '&quot;');
            
            const imgHtml = prod.imagen 
                ? `<img src="http://localhost:3000/${prod.imagen}" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">`
                : '📦';

            tr.innerHTML = `
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        ${imgHtml}
                        <span>${prod.nombre}</span>
                    </div>
                </td>
                <td>$${prod.precio}</td>
                <td style="font-weight: bold; ${prod.stock < 5 ? 'color:red' : ''}">${prod.stock}</td>
                <td>
                    <button onclick="abrirModalVendedor(${prodString})" ...>✏️</button>
                </td>
            `;
            tbody.appendChild(tr);

            // Llenar Select (Solo si hay stock)
            if (prod.stock > 0) {
                const option = document.createElement('option');
                option.value = prod.id;
                option.textContent = `${prod.nombre} ($${prod.precio}) - Stock: ${prod.stock}`;
                option.setAttribute('data-nombre', prod.nombre);
                select.appendChild(option);
            }
        });
    });
}

function cargarVentasVendedor() {
    if(!usuarioGlobal.sucursal_id) return;

    fetch(`http://localhost:8000/ventas/sucursal/${usuarioGlobal.sucursal_id}`, {
        headers: { 'Authorization': `Bearer ${tokenVendedor}` }
    })
    .then(res => res.json())
    .then(ventas => {
        const tbody = document.getElementById('tablaVentasVendedorBody');
        tbody.innerHTML = '';
        
        // Mostramos solo las últimas 5 ventas del usuario actual
        const misVentas = ventas.filter(v => parseInt(v.usuario_id) === usuarioGlobal.id);
        
        misVentas.slice(0, 5).forEach(venta => {
            const resumen = venta.productos.map(p => `(${p.cantidad}) ${p.nombre}`).join(', ');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${venta._id}</td>
                <td style="font-size: 0.9em;">${resumen}</td>
                <td style="font-weight: bold;">$${venta.total}</td>
            `;
            tbody.appendChild(tr);
        });
    });
}

function cargarCategoriasSugeridas() {
    fetch('http://localhost:8000/categorias', {
        headers: { 'Authorization': `Bearer ${tokenVendedor}` }
    })
    .then(res => res.json())
    .then(cats => {
        const lista = document.getElementById('lista-categorias');
        const listaEdit = document.getElementById('lista-categorias-edit');
        lista.innerHTML = '';
        listaEdit.innerHTML = '';
        
        cats.forEach(c => {
            const op1 = document.createElement('option');
            op1.value = c;
            lista.appendChild(op1);
            
            const op2 = document.createElement('option');
            op2.value = c;
            listaEdit.appendChild(op2);
        });
    });
}

// --- CREAR PRODUCTO ---

async function crearProducto(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('msgProducto');
    msgDiv.textContent = "Subiendo...";
    msgDiv.style.color = "blue";
    
    const fileInput = document.getElementById('prod-imagen');
    let rutaImagen = null;

    // 1. Si hay archivo, lo subimos primero
    if (fileInput.files.length > 0) {
        try {
            const respuestaImg = await subirImagen(fileInput.files[0]);
            if (respuestaImg.status === 'success') {
                rutaImagen = respuestaImg.ruta;
            } else {
                msgDiv.textContent = "Error al subir imagen: " + respuestaImg.mensaje;
                msgDiv.style.color = "red";
                return; // Detenemos todo si falla la imagen
            }
        } catch (err) {
            console.error(err);
            msgDiv.textContent = "Error de red al subir imagen.";
            return;
        }
    }

    // 2. Preparamos los datos del producto (incluyendo la ruta de la imagen)
    const datos = {
        nombre: document.getElementById('prod-nombre').value,
        precio: parseFloat(document.getElementById('prod-precio').value),
        categoria: document.getElementById('prod-categoria').value,
        stock: parseInt(document.getElementById('prod-stock').value),
        usuario_id: usuarioGlobal.id,
        sucursal_id: usuarioGlobal.sucursal_id,
        imagen: rutaImagen // <--- Aquí va la ruta que nos dio el backend (o null)
    };

    // 3. Guardamos el producto en Mongo
    try {
        const res = await fetch('http://localhost:8000/productos', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${tokenVendedor}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });
        
        const data = await res.json();

        if (data.status === 'success') {
            msgDiv.textContent = "✅ Producto creado con imagen!";
            msgDiv.style.color = "green";
            e.target.reset();
            cargarProductosVendedor(); 
        } else {
            msgDiv.textContent = data.mensaje;
            msgDiv.style.color = "red";
        }
    } catch (err) {
        msgDiv.textContent = "Error al guardar producto.";
        msgDiv.style.color = "red";
    }
}

// --- EDICIÓN (MODAL) ---

window.abrirModalVendedor = function(prod) {
    document.getElementById('edit-id').value = prod.id;
    document.getElementById('edit-nombre').value = prod.nombre;
    document.getElementById('edit-precio').value = prod.precio;
    document.getElementById('edit-stock').value = prod.stock;
    document.getElementById('edit-categoria').value = prod.categoria || '';
    
    document.getElementById('modalEditarProducto').style.display = 'flex';
};

function guardarEdicionProducto() {
    const id = document.getElementById('edit-id').value;
    
    const data = {
        nombre: document.getElementById('edit-nombre').value,
        precio: document.getElementById('edit-precio').value,
        stock: document.getElementById('edit-stock').value,
        categoria: document.getElementById('edit-categoria').value,
        usuario_id: usuarioGlobal.id,
        sucursal_id: usuarioGlobal.sucursal_id
    };

    fetch(`http://localhost:8000/productos/${id}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${tokenVendedor}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(respuesta => {
        if (respuesta.status === 'success') {
            document.getElementById('modalEditarProducto').style.display = 'none';
            cargarProductosVendedor();
            alert('Producto actualizado.');
        } else {
            alert('Error: ' + respuesta.mensaje);
        }
    });
}

// --- CERRAR TURNO ---

function cerrarTurno() {
    if (!confirm('¿Finalizar turno y salir del sistema?')) return;

    fetch('http://localhost:8000/horas-trabajadas/horario-salida', {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${tokenVendedor}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            usuario_id: usuarioGlobal.id,
            sucursal_id: usuarioGlobal.sucursal_id 
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje || 'Turno cerrado');
        localStorage.removeItem('jwt_token');
        window.location.href = 'index.php';
    });
}

//HELPER

async function subirImagen(archivo) {
    const formData = new FormData();
    formData.append('imagen', archivo); // 'imagen' debe coincidir con lo que espera el PHP ($archivos['imagen'])

    const res = await fetch('http://localhost:8000/productos/imagen', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${tokenVendedor}` 
            // ¡OJO! No poner 'Content-Type': 'multipart/form-data'. 
            // El navegador lo pone solo con el boundary correcto.
        },
        body: formData
    });

    return await res.json();
}