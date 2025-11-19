let mapa = null;
let marcador = null;
const tokenSucursales = localStorage.getItem('jwt_token'); // Nombre variable único para evitar conflictos

document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar lista inicial
    cargarSucursales();

    // 2. Inicializar Mapa (Leaflet)
    inicializarMapa();

    // 3. Configurar Formulario
    const form = document.getElementById('formCrearSucursal');
    if (form) {
        form.addEventListener('submit', crearSucursal);
    }
});

function inicializarMapa() {
    if (mapa) return; // Evitar doble inicialización
    
    // Pequeño delay para asegurar que el div es visible
    setTimeout(() => {
        const divMapa = document.getElementById('mapa');
        if (!divMapa) return;

        mapa = L.map('mapa').setView([-34.61, -64.38], 4); 

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapa);

        const provider = new GeoSearch.OpenStreetMapProvider();
        const searchControl = new GeoSearch.GeoSearchControl({
            provider: provider,
            style: 'bar',
            autoClose: true,
            keepResult: true,
            searchLabel: 'Buscar dirección...'
        });
        mapa.addControl(searchControl);

        mapa.on('geosearch/showlocation', function(resultado) {
            const loc = resultado.location;
            document.getElementById('suc-lat').value = loc.y.toFixed(6);
            document.getElementById('suc-lng').value = loc.x.toFixed(6);
            
            const dirInput = document.getElementById('suc-direccion');
            if (dirInput.value === '') {
                dirInput.value = loc.label.split(',').slice(0, 3).join(',');
            }
            
            if (marcador) marcador.remove();
            marcador = L.marker([loc.y, loc.x]).addTo(mapa);
        });
    }, 100);
}

function cargarSucursales() {
    fetch('http://localhost:8000/sucursales', {
        headers: { 'Authorization': `Bearer ${tokenSucursales}` }
    })
    .then(res => res.json())
    .then(sucursales => {
        const tbody = document.getElementById('tablaSucursalesBody');
        if(!tbody) return;
        
        tbody.innerHTML = '';
        sucursales.forEach(suc => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${suc.id}</td>
                <td>${suc.nombre}</td>
                <td>${suc.direccion}</td>
                <td>${suc.latitud}, ${suc.longitud}</td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(console.error);
}

function crearSucursal(e) {
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
            'Authorization': `Bearer ${tokenSucursales}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'success') {
            msgDiv.textContent = resp.mensaje;
            msgDiv.style.color = 'green';
            e.target.reset();
            if (marcador) marcador.remove();
            cargarSucursales();
        } else {
            msgDiv.textContent = `Error: ${resp.error}`;
            msgDiv.style.color = 'red';
        }
    })
    .catch(err => msgDiv.textContent = 'Error de red.');
}