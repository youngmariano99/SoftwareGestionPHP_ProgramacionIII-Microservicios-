<div class="form-container">
    <h2>📊 Estadísticas de Negocio</h2>

    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
    
        <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
            <h3 style="text-align: center;">🏆 Top 5 Productos</h3>
            <canvas id="chartTopProductos"></canvas>
        </div>

        <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
            <h3 style="text-align: center;">🏢 Ventas por Sucursal</h3>
            
            <div style="margin-bottom: 10px; text-align: center;">
                <select id="select-sucursal-reporte">
                    <option value="">-- Selecciona una Sucursal --</option>
                </select>
            </div>
            
            <div id="infoSucursal" style="text-align: center; display: none;">
                <h1 style="color: green; margin: 10px 0;" id="totalVentasSucursal">$0.00</h1>
                <p>Total recaudado histórico</p>
                <button id="btnVerDetalle" style="margin-top: 5px; font-size: 0.8em;">Ver listado detallado</button>
            </div>
        </div>

        <div style="flex: 1; min-width: 300px; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
            <h3 style="text-align: center;">🍕 Ventas por Categoría</h3>
            <div style="height: 250px; display: flex; justify-content: center;">
                <canvas id="chartVentasCategoria"></canvas>
            </div>
        </div>
    </div>
</div>

<div id="modalDetalle" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
    <div style="background: white; padding: 20px; border-radius: 8px; width: 80%; max-width: 800px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 id="tituloModal" style="margin: 0;">Detalle</h2>
            <button id="btnCerrarModal" style="background: none; border: none; font-size: 1.5em; cursor: pointer;">&times;</button>
        </div>
        <div id="contenidoModal"></div>
    </div>
</div>