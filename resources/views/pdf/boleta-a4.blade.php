<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Venta Electrónica</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .company-info {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .document-info {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: center;
            border: 2px solid #333;
            padding: 10px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            line-height: 1.3;
        }
        
        .document-type {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .document-number {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .client-info {
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
        }
        
        .client-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 13px;
        }
        
        .client-details {
            display: table;
            width: 100%;
        }
        
        .client-left, .client-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .detail-row {
            margin-bottom: 5px;
        }
        
        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        
        .totals .label {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 14px;
            background-color: #e8e8e8;
        }
        
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .qr-section {
            display: table;
            width: 100%;
        }
        
        .qr-code {
            display: table-cell;
            width: 150px;
            vertical-align: top;
        }
        
        .hash-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 20px;
            font-size: 10px;
        }
        
        .observations {
            margin-top: 20px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $company->razon_social }}</div>
                <div class="company-details">
                    <strong>RUC:</strong> {{ $company->ruc }}<br>
                    <strong>Dirección:</strong> {{ $company->direccion }}<br>
                    {{ $company->distrito }}, {{ $company->provincia }}, {{ $company->departamento }}<br>
                    @if($company->telefono)
                        <strong>Teléfono:</strong> {{ $company->telefono }}<br>
                    @endif
                    @if($company->email)
                        <strong>Email:</strong> {{ $company->email }}
                    @endif
                </div>
            </div>
            <div class="document-info">
                <div class="document-type">BOLETA DE VENTA ELECTRÓNICA</div>
                <div class="document-number">{{ $boleta->serie }}-{{ $boleta->numero }}</div>
                <div><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($boleta->fecha_emision)->format('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Client Information (Optional for Boletas) -->
        @if($boleta->cliente_razon_social)
        <div class="client-info">
            <div class="client-title">DATOS DEL CLIENTE</div>
            <div class="client-details">
                <div class="client-left">
                    <div class="detail-row">
                        <span class="detail-label">Cliente:</span>
                        {{ $boleta->cliente_razon_social }}
                    </div>
                    @if($boleta->cliente_numero_documento)
                    <div class="detail-row">
                        <span class="detail-label">{{ $boleta->cliente_tipo_documento == '6' ? 'RUC' : 'DNI' }}:</span>
                        {{ $boleta->cliente_numero_documento }}
                    </div>
                    @endif
                </div>
                <div class="client-right">
                    @if($boleta->cliente_direccion)
                        <div class="detail-row">
                            <span class="detail-label">Dirección:</span>
                            {{ $boleta->cliente_direccion }}
                        </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Moneda:</span>
                        {{ $boleta->tipo_moneda == 'PEN' ? 'Soles' : 'Dólares' }}
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%">Item</th>
                    <th style="width: 15%">Código</th>
                    <th style="width: 35%">Descripción</th>
                    <th style="width: 8%">Und</th>
                    <th style="width: 8%">Cant.</th>
                    <th style="width: 13%">V. Unit.</th>
                    <th style="width: 13%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($boleta->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $detail->codigo_interno ?? '-' }}</td>
                    <td>{{ $detail->descripcion }}</td>
                    <td class="text-center">{{ $detail->unidad_medida }}</td>
                    <td class="text-center">{{ number_format($detail->cantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($detail->valor_unitario, 2) }}</td>
                    <td class="text-right">{{ number_format($detail->valor_venta_con_descuento, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                @if($boleta->total_operaciones_gravadas > 0)
                <tr>
                    <td class="label">Op. Gravadas:</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->total_operaciones_gravadas, 2) }}</td>
                </tr>
                @endif
                @if($boleta->total_operaciones_inafectas > 0)
                <tr>
                    <td class="label">Op. Inafectas:</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->total_operaciones_inafectas, 2) }}</td>
                </tr>
                @endif
                @if($boleta->total_operaciones_exoneradas > 0)
                <tr>
                    <td class="label">Op. Exoneradas:</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->total_operaciones_exoneradas, 2) }}</td>
                </tr>
                @endif
                @if($boleta->total_descuentos > 0)
                <tr>
                    <td class="label">Descuentos:</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->total_descuentos, 2) }}</td>
                </tr>
                @endif
                @if($boleta->total_igv > 0)
                <tr>
                    <td class="label">IGV (18%):</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->total_igv, 2) }}</td>
                </tr>
                @endif
                <tr class="total-final">
                    <td class="label">TOTAL:</td>
                    <td class="text-right">{{ $boleta->tipo_moneda }} {{ number_format($boleta->precio_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="qr-section">
                @if($boleta->qr)
                <div class="qr-code">
                    <img src="data:image/png;base64,{{ $boleta->qr }}" alt="Código QR" style="width: 120px; height: 120px;">
                </div>
                @endif
                <div class="hash-info">
                    @if($boleta->hash)
                        <strong>Hash:</strong> {{ $boleta->hash }}<br><br>
                    @endif
                    <strong>Representación impresa de la Boleta de Venta Electrónica</strong><br>
                    Consulte su documento en: www.sunat.gob.pe<br><br>
                    <strong>Estado SUNAT:</strong> {{ $boleta->estado_sunat }}<br>
                    @if($boleta->fecha_envio_sunat)
                        <strong>Fecha de envío:</strong> {{ \Carbon\Carbon::parse($boleta->fecha_envio_sunat)->format('d/m/Y H:i:s') }}
                    @endif
                </div>
            </div>
            
            @if($boleta->observaciones)
            <div class="observations">
                <strong>Observaciones:</strong><br>
                {{ $boleta->observaciones }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>