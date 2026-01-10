<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Producto</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        input, select, button { margin: 5px 0; padding: 8px; width: 200px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        #result { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Test Guardar Producto</h1>
    <form id="testForm">
        <div>
            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="Producto Test {{ now()->format('His') }}" required>
        </div>
        <div>
            <label>Concentración:</label><br>
            <input type="text" name="concentracion" value="" placeholder="Opcional">
        </div>
        <div>
            <label>Marca:</label><br>
            <input type="text" name="marca" value="Marca Test" required>
        </div>
        <div>
            <label>Lote:</label><br>
            <input type="text" name="lote" value="LOTE{{ now()->format('His') }}" required>
        </div>
        <div>
            <label>Código de barras:</label><br>
            <input type="text" name="codigo_barras" value="{{ now()->timestamp }}" required>
        </div>
        <div>
            <label>Stock actual:</label><br>
            <input type="number" name="stock_actual" value="10" min="0" required>
        </div>
        <div>
            <label>Stock mínimo:</label><br>
            <input type="number" name="stock_minimo" value="5" min="0" required>
        </div>
        <div>
            <label>Precio compra:</label><br>
            <input type="number" name="precio_compra" value="10.50" step="0.01" min="0.01" required>
        </div>
        <div>
            <label>Precio venta:</label><br>
            <input type="number" name="precio_venta" value="15.00" step="0.01" min="0.01" required>
        </div>
        <div>
            <label>Fecha fabricación:</label><br>
            <input type="date" name="fecha_fabricacion" value="2024-01-01" required>
        </div>
        <div>
            <label>Fecha vencimiento:</label><br>
            <input type="date" name="fecha_vencimiento" value="2025-01-01" required>
        </div>
        <div>
            <label>Categoría:</label><br>
            <select name="categoria_id" required>
                <option value="">Seleccionar...</option>
                <option value="1">Categoría 1</option>
                <option value="2">Categoría 2</option>
            </select>
        </div>
        <div>
            <label>Presentación:</label><br>
            <select name="presentacion_id" required>
                <option value="">Seleccionar...</option>
                <option value="1">Presentación 1</option>
                <option value="2">Presentación 2</option>
            </select>
        </div>
        <div>
            <label>Proveedor:</label><br>
            <select name="proveedor_id" required>
                <option value="">Seleccionar...</option>
                <option value="1">Proveedor 1</option>
                <option value="2">Proveedor 2</option>
            </select>
        </div>
        <br>
        <button type="button" onclick="testSave()">Guardar Producto</button>
    </form>

    <div id="result"></div>

    <script>
        async function testSave() {
            const form = document.getElementById('testForm');
            const formData = new FormData(form);
            const resultDiv = document.getElementById('result');
            
            resultDiv.innerHTML = '<p>Enviando...</p>';
            
            try {
                const response = await fetch('/inventario/producto/guardar', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let jsonData = null;
                try {
                    jsonData = JSON.parse(text);
                } catch (e) {
                    console.log('Response is not JSON');
                }
                
                resultDiv.innerHTML = `
                    <h3>Resultado:</h3>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                    ${jsonData ? `
                        <p><strong>JSON Response:</strong></p>
                        <pre class="${jsonData.success ? 'success' : 'error'}">${JSON.stringify(jsonData, null, 2)}</pre>
                    ` : `
                        <p><strong>Raw Response:</strong></p>
                        <pre class="error">${text}</pre>
                    `}
                `;
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <h3 class="error">Error:</h3>
                    <p class="error">${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html>