<div class="form-container">
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
</div>

<div class="form-container">
    <h3>🧑‍💼 Registrar Nuevo Empleado</h3>
    <form id="formRegistrarEmpleado">
        
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
                <select id="emp-rol" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="vendedor">Vendedor</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>
            <div style="flex: 1;">
                <label for="emp-sucursal">Sucursal:</label>
                <select id="emp-sucursal" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">-- Cargando... --</option>
                </select>
            </div>
        </div>

        <label for="emp-tarifa">Tarifa por Hora ($):</label>
        <input type="number" step="0.01" id="emp-tarifa">
        
        <button type="submit">Registrar Empleado</button>
        <div id="msgEmpleado" style="color: green; margin-top: 10px;"></div>
    </form>
</div>