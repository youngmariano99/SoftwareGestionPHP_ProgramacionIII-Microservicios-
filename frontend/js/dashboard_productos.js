// Variables locales del módulo
let listaProductosGlobal = [];
const tokenProductos = localStorage.getItem('jwt_token');

document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar inicial
    cargarGestionProductos();

    // 2. Listeners
    const inputBuscador = document.getElementById('buscador-productos');
    if (inputBuscador) {
        inputBuscador.addEventListener('keyup', filtrarProductos);
    }

    const btnStockBajo = document.getElementById('btnStockBajo');
    if (btnStockBajo) {
        btnStockBajo.addEventListener('click', verStockBajo);
    }

    // Modal Listeners
    const btnGuardar = document.getElementById('btnGuardarEdicion');
    const btnCancelar = document.getElementById('btnCancelarEdicion');
    const modal = document.getElementById('modalEditarProducto');

    if (btnGuardar) btnGuardar.addEventListener('click', guardarEdicionProducto);
    if (btnCancelar) btnCancelar.addEventListener('click', () => modal.style.display = 'none');
});

// --- Funciones Principales ---

function cargarGestionProductos() {
    fetch('http://localhost:8000/productos', {
        headers: { 'Authorization': `Bearer ${tokenProductos}` }
    })
    .then(res => res.json())
    .then(productos => {
        listaProductosGlobal = productos; // Guardamos copia para el buscador
        renderizarTablaInventario(productos);
    })
    .catch(err => console.error("Error cargando inventario:", err));
}

function verStockBajo() {
    fetch('http://localhost:8000/stock/bajo', {
        headers: { 'Authorization': `Bearer ${tokenProductos}` }
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
}

function renderizarTablaInventario(lista) {
    const tbody = document.getElementById('tablaGestionProductosBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    lista.forEach(prod => {
        const tr = document.createElement('tr');
        // Si el stock es bajo, lo pintamos de rojo suave
        const estiloAlerta = prod.stock <= 5 ? 'background-color: #ffebee;' : '';
        
        // Creamos el botón de editar dinámicamente y le asignamos el evento click
        // Esto reemplaza al 'onclick="abrirModal..."' sucio del HTML
        const btnEditar = document.createElement('button');
        btnEditar.textContent = '✏️ Editar';
        btnEditar.style = "background: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;";
        btnEditar.onclick = () => abrirModalEditar(prod); // Usamos arrow function para pasar el objeto

        const tdAccion = document.createElement('td');
        tdAccion.appendChild(btnEditar);

        tr.style = estiloAlerta;
        tr.innerHTML = `
            <td>${prod.nombre}</td>
            <td>${prod.categoria || 'Sin Categoría'}</td> 
            <td>$${prod.precio}</td>
            <td style="font-weight: bold;">${prod.stock}</td>
        `;
        tr.appendChild(tdAccion); // Agregamos la celda con el botón
        tbody.appendChild(tr);
    });
}

function filtrarProductos() {
    const texto = document.getElementById('buscador-productos').value.toLowerCase();
    const filtrados = listaProductosGlobal.filter(p => 
        p.nombre.toLowerCase().includes(texto)
    );
    renderizarTablaInventario(filtrados);
}

// --- Funciones del Modal ---

function abrirModalEditar(prod) {
    document.getElementById('edit-id').value = prod.id;
    document.getElementById('edit-nombre').value = prod.nombre;
    document.getElementById('edit-precio').value = prod.precio;
    document.getElementById('edit-stock').value = prod.stock;
    document.getElementById('edit-categoria').value = prod.categoria || '';
    
    cargarCategoriasModal(); // Llenar el datalist
    document.getElementById('modalEditarProducto').style.display = 'flex';
}

function cargarCategoriasModal() {
    fetch('http://localhost:8000/categorias', {
        headers: { 'Authorization': `Bearer ${tokenProductos}` }
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

function guardarEdicionProducto() {
    const id = document.getElementById('edit-id').value;
    // Decodificamos token para obtener ID de usuario (Auditoría)
    let usuarioId = null;
    try {
        const payload = JSON.parse(atob(tokenProductos.split('.')[1]));
        usuarioId = payload.data.id;
    } catch(e) {}

    const data = {
        nombre: document.getElementById('edit-nombre').value,
        categoria: document.getElementById('edit-categoria').value,
        precio: document.getElementById('edit-precio').value,
        stock: document.getElementById('edit-stock').value,
        usuario_id: usuarioId, // Para el log
        sucursal_id: 1 // Hardcodeado por ahora o tomar del token si existe
    };

    fetch(`http://localhost:8000/productos/${id}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${tokenProductos}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(respuesta => {
        if (respuesta.status === 'success') {
            alert('✅ Producto actualizado correctamente.');
            document.getElementById('modalEditarProducto').style.display = 'none';
            cargarGestionProductos(); // Refrescar lista
        } else {
            alert('❌ Error: ' + respuesta.mensaje);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de red al editar producto.');
    });
}