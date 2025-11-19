document.addEventListener('DOMContentLoaded', () => {
    cargarVentasAdmin();
});

function cargarVentasAdmin() {
    const token = localStorage.getItem('jwt_token');
    
    fetch('http://localhost:8000/ventas', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(ventas => {
        const tbody = document.getElementById('tablaVentasAdminBody');
        tbody.innerHTML = '';

        ventas.forEach(venta => {
            // venta.productos es un array de items
            venta.productos.forEach(prod => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${venta._id}</td>
                    <td>ID: ${venta.usuario_id}</td>
                    <td>${prod.nombre}</td>
                    <td>${prod.cantidad}</td>
                    <td>$${prod.precio}</td>
                    <td>$${prod.subtotal}</td>
                    <td>${new Date(venta.fecha).toLocaleDateString()}</td>
                `;
                tbody.appendChild(tr);
            });
        });
    })
    .catch(console.error);
}