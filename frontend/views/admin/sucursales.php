<div class="form-container">
    <h2>Gestión de Sucursales</h2>
            
    <form id="formCrearSucursal">
        <h3>📍 Nueva Sucursal</h3>
        <p>Utiliza el buscador (🔍) en el mapa para encontrar la dirección:</p>
        
        <div id="mapa" style="height: 300px; border: 1px solid #ccc; margin-bottom: 15px;"></div>

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