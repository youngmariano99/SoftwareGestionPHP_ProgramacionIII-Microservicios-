// Variables locales para este módulo
let chartTop = null;
let chartCat = null;
let ventasSucursalActual = [];
const token = localStorage.getItem('jwt_token');

document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar datos iniciales
    cargarTopProductos();
    cargarVentasPorCategoria();
    cargarSucursalesReporte();

    // 2. Configurar Listeners (Eventos)
    const selectSucursal = document.getElementById('select-sucursal-reporte');
    if (selectSucursal) {
        selectSucursal.addEventListener('change', cargarReporteSucursal);
    }

    const btnDetalle = document.getElementById('btnVerDetalle');
    if (btnDetalle) {
        btnDetalle.addEventListener('click', verDetalleSucursal);
    }

    // Cerrar modal
    const modal = document.getElementById('modalDetalle');
    const btnCerrar = document.getElementById('btnCerrarModal');
    
    if(btnCerrar) btnCerrar.addEventListener('click', () => modal.style.display = 'none');
    if(modal) {
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.style.display = 'none';
        });
    }
});

// --- FUNCIONES DE GRÁFICOS ---

function cargarTopProductos() {
    fetch('http://localhost:8000/ventas/top-productos', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        const nombres = data.map(item => item.nombre);
        const cantidades = data.map(item => item.total_vendido);

        const ctx = document.getElementById('chartTopProductos').getContext('2d');
        if (chartTop) chartTop.destroy();

        chartTop = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: nombres,
                datasets: [{
                    label: 'Unidades',
                    data: cantidades,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            }
        });
    });
}

function cargarVentasPorCategoria() {
    fetch('http://localhost:8000/ventas/por-categoria', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        const categorias = data.map(item => item.categoria);
        const totales = data.map(item => item.total);
        const ctx = document.getElementById('chartVentasCategoria').getContext('2d');
        
        if (chartCat) chartCat.destroy();

        chartCat = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categorias,
                datasets: [{
                    data: totales,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
                }]
            }
        });
    });
}

// --- FUNCIONES DE SUCURSAL ---

function cargarSucursalesReporte() {
    fetch('http://localhost:8000/sucursales', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(sucursales => {
        const select = document.getElementById('select-sucursal-reporte');
        sucursales.forEach(suc => {
            const option = document.createElement('option');
            option.value = suc.id;
            option.textContent = suc.nombre;
            select.appendChild(option);
        });
    });
}

function cargarReporteSucursal() {
    const idSucursal = document.getElementById('select-sucursal-reporte').value;
    const divInfo = document.getElementById('infoSucursal');
    
    if (!idSucursal) {
        divInfo.style.display = 'none';
        return;
    }

    fetch(`http://localhost:8000/ventas/sucursal/${idSucursal}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(ventas => {
        ventasSucursalActual = ventas;
        let total = ventas.reduce((acc, v) => acc + parseFloat(v.total), 0);
        
        document.getElementById('totalVentasSucursal').textContent = `$${total.toFixed(2)}`;
        divInfo.style.display = 'block';
    });
}

function verDetalleSucursal() {
    if (ventasSucursalActual.length === 0) return alert("Sin datos");
    
    const contenedor = document.getElementById('contenidoModal');
    let html = '<table style="width:100%"><thead><tr><th>Fecha</th><th>Productos</th><th>Total</th></tr></thead><tbody>';
    
    ventasSucursalActual.forEach(v => {
        const prods = v.productos.map(p => `${p.cantidad}x ${p.nombre}`).join('<br>');
        html += `<tr>
            <td>${new Date(v.fecha).toLocaleDateString()}</td>
            <td>${prods}</td>
            <td>$${v.total}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    contenedor.innerHTML = html;
    document.getElementById('modalDetalle').style.display = 'flex';
}