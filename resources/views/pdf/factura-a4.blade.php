<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
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
                <div class="document-type">FACTURA ELECTRÓNICA</div>
                <div class="document-number">{{ $invoice->serie }}-{{ $invoice->numero }}</div>
                <div><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($invoice->fecha_emision)->format('d/m/Y') }}</div>
                @if($invoice->fecha_vencimiento)
                    <div><strong>Vencimiento:</strong> {{ \Carbon\Carbon::parse($invoice->fecha_vencimiento)->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <div class="client-title">DATOS DEL CLIENTE</div>
            <div class="client-details">
                <div class="client-left">
                    <div class="detail-row">
                        <span class="detail-label">Razón Social:</span>
                        {{ $invoice->cliente_razon_social }}
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">{{ $invoice->cliente_tipo_documento == '6' ? 'RUC' : 'DNI' }}:</span>
                        {{ $invoice->cliente_numero_documento }}
                    </div>
                </div>
                <div class="client-right">
                    @if($invoice->cliente_direccion)
                        <div class="detail-row">
                            <span class="detail-label">Dirección:</span>
                            {{ $invoice->cliente_direccion }}
                        </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Moneda:</span>
                        {{ $invoice->tipo_moneda == 'PEN' ? 'Soles' : 'Dólares' }}
                    </div>
                </div>
            </div>
        </div>

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
                @foreach($invoice->details as $index => $detail)
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
                @if($invoice->total_operaciones_gravadas > 0)
                <tr>
                    <td class="label">Op. Gravadas:</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->total_operaciones_gravadas, 2) }}</td>
                </tr>
                @endif
                @if($invoice->total_operaciones_inafectas > 0)
                <tr>
                    <td class="label">Op. Inafectas:</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->total_operaciones_inafectas, 2) }}</td>
                </tr>
                @endif
                @if($invoice->total_operaciones_exoneradas > 0)
                <tr>
                    <td class="label">Op. Exoneradas:</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->total_operaciones_exoneradas, 2) }}</td>
                </tr>
                @endif
                @if($invoice->total_descuentos > 0)
                <tr>
                    <td class="label">Descuentos:</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->total_descuentos, 2) }}</td>
                </tr>
                @endif
                @if($invoice->total_igv > 0)
                <tr>
                    <td class="label">IGV (18%):</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->total_igv, 2) }}</td>
                </tr>
                @endif
                <tr class="total-final">
                    <td class="label">TOTAL:</td>
                    <td class="text-right">{{ $invoice->tipo_moneda }} {{ number_format($invoice->precio_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="qr-section">
                @if($invoice->qr)
                <div class="qr-code">
                    <img src="data:image/png;base64,{{ $invoice->qr }}" alt="Código QR" style="width: 120px; height: 120px;">
                </div>
                @endif
                <div class="hash-info">
                    @if($invoice->hash)
                        <strong>Hash:</strong> {{ $invoice->hash }}<br><br>
                    @endif
                    <strong>Representación impresa de la Factura Electrónica</strong><br>
                    Consulte su documento en: www.sunat.gob.pe<br><br>
                    <strong>Estado SUNAT:</strong> {{ $invoice->estado_sunat }}<br>
                    @if($invoice->fecha_envio_sunat)
                        <strong>Fecha de envío:</strong> {{ \Carbon\Carbon::parse($invoice->fecha_envio_sunat)->format('d/m/Y H:i:s') }}
                    @endif
                </div>
            </div>
            
            @if($invoice->observaciones)
            <div class="observations">
                <strong>Observaciones:</strong><br>
                {{ $invoice->observaciones }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>