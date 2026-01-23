<div class="row">
    <div class="col-md-6">
        <h5>Información de la Venta</h5>
        <table class="table table-sm">
            <tr>
                <td><strong>Número de Venta:</strong></td>
                <td>{{ $venta->numero_venta }}</td>
            </tr>
            <tr>
                <td><strong>Fecha:</strong></td>
                <td>{{ $venta->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Tipo Comprobante:</strong></td>
                <td>{{ ucfirst($venta->tipo_comprobante) }}</td>
            </tr>
            <tr>
                <td><strong>Cliente:</strong></td>
                <td>{{ $venta->cliente ? $venta->cliente->nombre : 'Cliente General' }}</td>
            </tr>
            @if($venta->cliente && $venta->cliente->documento)
            <tr>
                <td><strong>Documento:</strong></td>
                <td>{{ $venta->cliente->documento }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td>S/ {{ number_format($venta->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td><strong>IGV:</strong></td>
                <td>S/ {{ number_format($venta->igv, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total:</strong></td>
                <td><strong>S/ {{ number_format($venta->total, 2) }}</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h5>Comprobante Electrónico</h5>
        @if($venta->comprobante_electronico)
            <table class="table table-sm">
                <tr>
                    <td><strong>Serie-Número:</strong></td>
                    <td>{{ $venta->comprobante_electronico->serie_numero }}</td>
                </tr>
                <tr>
                    <td><strong>Estado SUNAT:</strong></td>
                    <td>
                        @php
                            $estado = $venta->comprobante_electronico->estado_sunat;
                            $class = match($estado) {
                                'aceptado' => 'badge-success',
                                'rechazado' => 'badge-danger',
                                'enviado' => 'badge-info',
                                'pendiente' => 'badge-warning',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $class }}">{{ ucfirst($estado) }}</span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Hash:</strong></td>
                    <td><code style="font-size: 0.8em;">{{ $venta->comprobante_electronico->hash }}</code></td>
                </tr>
                <tr>
                    <td><strong>Fecha Envío:</strong></td>
                    <td>{{ $venta->comprobante_electronico->fecha_envio ? $venta->comprobante_electronico->fecha_envio->format('d/m/Y H:i:s') : '-' }}</td>
                </tr>
                @if($venta->comprobante_electronico->codigo_error)
                <tr>
                    <td><strong>Código Error:</strong></td>
                    <td><span class="text-danger">{{ $venta->comprobante_electronico->codigo_error }}</span></td>
                </tr>
                @endif
                @if($venta->comprobante_electronico->mensaje_error)
                <tr>
                    <td><strong>Mensaje Error:</strong></td>
                    <td><span class="text-danger">{{ $venta->comprobante_electronico->mensaje_error }}</span></td>
                </tr>
                @endif
                @if($venta->comprobante_electronico->observaciones)
                <tr>
                    <td><strong>Observaciones:</strong></td>
                    <td>{{ $venta->comprobante_electronico->observaciones }}</td>
                </tr>
                @endif
            </table>
            
            @if($venta->comprobante_electronico->qr_code)
            <div class="text-center mt-3">
                <h6>Código QR</h6>
                <img src="data:image/png;base64,{{ $venta->comprobante_electronico->qr_code }}" 
                     alt="QR Code" class="img-fluid" style="max-width: 150px;">
            </div>
            @endif
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                No se ha generado comprobante electrónico para esta venta.
            </div>
        @endif
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h5>Detalle de Productos</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>
                            <strong>{{ $detalle->producto->nombre }}</strong><br>
                            <small class="text-muted">{{ $detalle->producto->codigo_barras }}</small>
                        </td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td>S/ {{ number_format($detalle->descuento, 2) }}</td>
                        <td>S/ {{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Subtotal:</th>
                        <th>S/ {{ number_format($venta->subtotal, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">IGV ({{ config('sistema.igv.porcentaje', 18) }}%):</th>
                        <th>S/ {{ number_format($venta->igv, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">Total:</th>
                        <th>S/ {{ number_format($venta->total, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($venta->comprobante_electronico && $venta->comprobante_electronico->xml_content)
<div class="row mt-4">
    <div class="col-12">
        <h5>Contenido XML</h5>
        <div class="card">
            <div class="card-body">
                <pre style="max-height: 300px; overflow-y: auto; font-size: 0.8em;"><code>{{ $venta->comprobante_electronico->xml_content }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endif

<script>

$('#descargarXML').off('click').on('click', function() {
    @if($venta->comprobante_electronico && $venta->comprobante_electronico->xml_path)
    window.open('{{ route("admin.sunat.descargar-xml", $venta->id) }}', '_blank');
    @else
    toastr.warning('No hay archivo XML disponible');
    @endif
});

$('#descargarPDF').off('click').on('click', function() {
    @if($venta->comprobante_electronico && $venta->comprobante_electronico->pdf_path)
    window.open('{{ route("admin.sunat.descargar-pdf", $venta->id) }}', '_blank');
    @else
    toastr.warning('No hay archivo PDF disponible');
    @endif
});
</script>