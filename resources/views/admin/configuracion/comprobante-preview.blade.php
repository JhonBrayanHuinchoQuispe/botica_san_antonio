@extends('layout.layout')

@php
    $title = 'Vista Previa de Comprobante';
    $subTitle = 'Formato de comprobante electrónico';
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gray-100 p-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Vista Previa de Comprobante</h4>
                    <div>
                        <span class="badge bg-primary">{{ strtoupper($comprobante->tipo) }}</span>
                        <span class="badge bg-success">{{ $comprobante->serie }}-{{ $comprobante->numero }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-semibold mb-2">Datos de la Empresa</h5>
                            <div class="mb-3">
                                <div><strong>{{ config('app.name') }}</strong></div>
                                <div>RUC: {{ $configuracion->ruc ?? '20123456789' }}</div>
                                <div>{{ $configuracion->empresa_direccion ?? 'Dirección de ejemplo' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-semibold mb-2">Datos del Cliente</h5>
                            <div class="mb-3">
                                <div><strong>{{ $comprobante->cliente->nombre }}</strong></div>
                                <div>Documento: {{ $comprobante->cliente->documento }}</div>
                                <div>{{ $comprobante->cliente->direccion }}</div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">Fecha: {{ $comprobante->fecha->format('d/m/Y H:i') }}</div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-end">Cant.</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comprobante->items as $item)
                                <tr>
                                    <td>{{ $item->descripcion }}</td>
                                    <td class="text-end">{{ number_format($item->cantidad, 0) }}</td>
                                    <td class="text-end">S/ {{ number_format($item->precio, 2) }}</td>
                                    <td class="text-end">S/ {{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Subtotal</th>
                                    <th class="text-end">S/ {{ number_format($comprobante->subtotal, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">IGV</th>
                                    <th class="text-end">S/ {{ number_format($comprobante->igv, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">S/ {{ number_format($comprobante->total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between">
                        <small class="text-muted">Esta es una vista previa con datos de ejemplo.</small>
                        <a href="{{ route('admin.configuracion.comprobantes') }}" class="btn btn-outline-secondary">Cerrar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection