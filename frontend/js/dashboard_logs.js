document.addEventListener('DOMContentLoaded', () => {
    cargarLogs();
});

function cargarLogs() {
    const token = localStorage.getItem('jwt_token');
    
    fetch('http://localhost:8000/logs', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(logs => {
        const tbody = document.getElementById('tablaAuditoriaBody');
        tbody.innerHTML = '';
        
        logs.forEach(log => {
            const tr = document.createElement('tr');
            
            const fecha = new Date(log.creado_en).toLocaleString();
            const usuario = log.usuario_nombre ? `${log.usuario_nombre} ${log.usuario_apellido}` : 'Sistema';
            
            // Resaltar errores
            const esError = log.tipo_accion.includes('FALLO') || log.tipo_accion.includes('ERROR');
            const estilo = esError ? 'color: red; font-weight: bold;' : '';

            tr.innerHTML = `
                <td>${fecha}</td>
                <td>${usuario}</td>
                <td>${log.sucursal_nombre || '-'}</td>
                <td style="${estilo}">${log.tipo_accion}</td>
                <td style="${estilo}">${log.descripcion}</td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(console.error);
}