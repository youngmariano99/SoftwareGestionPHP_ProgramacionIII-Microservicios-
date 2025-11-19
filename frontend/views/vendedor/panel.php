<div class="form-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>🛒 Punto de Venta (Vendedor)</h2>
        <button id="btnCerrarTurno" style="background-color: #ff4444; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            🛑 Finalizar Turno
        </button>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px;">
        <h3>Registrar Venta</h3>
        <form id="formRegistrarVenta">
            <div style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px;">
                <div style="flex-grow: 1;">
                    <label style="font-weight: bold;">Producto:</label>
                    <select id="select-producto" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Cargando productos... --</option>
                    </select>
                </div>
                
                <div style="width: 120px;">
                    <label style="font-weight: bold;">Cantidad:</label>
                    <input type="number" id="input-cantidad" value="1" min="1" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <button type="button" id="btn-agregar-carrito" style="background-color: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 1.2em;">+</button>
            </div>

            <table style="width: 100%; margin-bottom: 15px; background: white; border-radius: 4px; overflow: hidden;">
                <thead style="background: #2c3e50; color: white;">
                    <tr>
                        <th style="padding: 10px;">Producto</th>
                        <th style="padding: 10px;">Cant</th>
                        <th style="padding: 10px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="carrito-body">
                    </tbody>
            </table>

            <button type="submit" style="width: 100%; padding: 12px; background-color: #2c3e50; color: white; font-weight: bold; font-size: 1.1em; border: none; border-radius: 4px; cursor: pointer;">
                ✅ Confirmar Venta
            </button>
        </form>
        <div id="msgVenta" style="color: green; margin-top: 10px; font-weight: bold; text-align: center;"></div>
    </div>
</div>

<div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
    
    <div class="form-container" style="flex: 1; min-width: 300px;">
        <h3>📦 Alta Rápida de Producto</h3>
        <form id="formCrearProducto">
            <label>Nombre:</label>
            <input type="text" id="prod-nombre" required>
            
            <label>Precio ($):</label>
            <input type="number" step="0.01" id="prod-precio" required>

            <label>Categoría:</label>
            <input type="text" id="prod-categoria" list="lista-categorias" placeholder="Ej: Bebidas" required>
            <datalist id="lista-categorias"></datalist>

            <label>Stock Inicial:</label>
            <input type="number" id="prod-stock" required>

            <label>Imagen del Producto:</label>
            <input type="file" id="prod-imagen" accept="image/*" style="width:100%; margin-bottom:10px;">

            <button type="submit" style="width: 100%; margin-top: 10px;">Crear Producto</button>
            <div id="msgProducto" style="margin-top: 10px; font-weight: bold;"></div>
        </form>
    </div>

    <div class="form-container" style="flex: 1; min-width: 300px;">
        <h3>📋 Mis Ventas Recientes</h3>
        <div style="max-height: 150px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #eee;">
            <table style="margin-top: 0;">
                <thead style="background: #eee;">
                    <tr>
                        <th>ID</th>
                        <th>Detalle</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tablaVentasVendedorBody"></tbody>
            </table>
        </div>

        <h3>📦 Stock Actual</h3>
        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #eee;">
             <table style="margin-top: 0;">
                <thead style="background: #eee;">
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Editar</th>
                    </tr>
                </thead>
                <tbody id="tablaProductosBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalEditarProducto" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1001;">
    <div style="background: white; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3>✏️ Editar Producto</h3>
        <input type="hidden" id="edit-id">
        
        <label>Nombre:</label>
        <input type="text" id="edit-nombre" style="width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box;">

        <label>Categoría:</label>
        <input type="text" id="edit-categoria" list="lista-categorias-edit" style="width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box;">
        <datalist id="lista-categorias-edit"></datalist>
        
        <label>Precio:</label>
        <input type="number" id="edit-precio" step="0.01" style="width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box;">
        
        <label>Stock:</label>
        <input type="number" id="edit-stock" style="width: 100%; margin-bottom: 20px; padding: 8px; box-sizing: border-box;">
        
        <div style="display: flex; justify-content: space-between;">
            <button id="btnGuardarEdicion" style="background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Guardar</button>
            <button id="btnCancelarEdicion" style="background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Cancelar</button>
        </div>
    </div>
</div>