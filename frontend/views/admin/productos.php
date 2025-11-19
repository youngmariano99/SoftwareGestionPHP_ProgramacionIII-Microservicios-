<div class="form-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>📦 Gestión de Inventario</h2>
        <button id="btnStockBajo" style="background-color: #ff9800; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">
            ⚠️ Ver Alertas de Stock Bajo
        </button>
    </div>

    <input type="text" id="buscador-productos" placeholder="🔍 Filtrar productos por nombre..." style="margin-bottom: 10px; padding: 10px; width: 100%; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">

    <div style="max-height: 400px; overflow-y: auto;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th> 
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="tablaGestionProductosBody">
                </tbody>
        </table>
    </div>
</div>

<div id="modalEditarProducto" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1001;">
    <div style="background: white; padding: 20px; border-radius: 8px; width: 350px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
        <h3>✏️ Editar Producto</h3>
        <input type="hidden" id="edit-id">
        
        <label style="display:block; margin-top:10px">Nombre:</label>
        <input type="text" id="edit-nombre" style="width: 100%; padding: 8px;">

        <label style="display:block; margin-top:10px">Categoría:</label>
        <input type="text" id="edit-categoria" list="lista-categorias-edit" style="width: 100%; padding: 8px;">
        <datalist id="lista-categorias-edit"></datalist>
        
        <label style="display:block; margin-top:10px">Precio:</label>
        <input type="number" id="edit-precio" step="0.01" style="width: 100%; padding: 8px;">
        
        <label style="display:block; margin-top:10px">Stock:</label>
        <input type="number" id="edit-stock" style="width: 100%; padding: 8px; margin-bottom: 20px;">
        
        <div style="display: flex; justify-content: space-between;">
            <button id="btnGuardarEdicion" style="background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Guardar</button>
            <button id="btnCancelarEdicion" style="background: #ccc; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Cancelar</button>
        </div>
    </div>
</div>