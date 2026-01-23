<table class="historial-table w-full" id="tablaHistorial">
    <thead>
        <tr>
            <th>Fecha y Hora</th>
            <th>Producto</th>
            <th>Proveedor</th>
            <th>Cantidad</th>
            <th>Lote y Vencimiento</th>
            <th>Precio</th>
            <th>Usuario</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($entradas as $entrada)
        <tr class="historial-row">
            <td>
                <div class="flex items-center gap-3">
                    <iconify-icon icon="solar:calendar-bold-duotone" class="text-slate-400 text-lg"></iconify-icon>
                    <div>
                        <div class="font-semibold text-slate-600">{{ $entrada->fecha_entrada->format('d/m/Y') }}</div>
                        <div class="text-[0.7rem] text-slate-400">{{ $entrada->fecha_entrada->format('g:i A') }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="text-left">
                    <div class="font-bold text-slate-700 text-[0.9rem]">{{ $entrada->producto->nombre ?? 'N/A' }}</div>
                    <div class="text-[0.7rem] text-slate-400">{{ $entrada->producto->codigo_barras ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="text-left">
                    <div class="font-semibold text-slate-500">{{ $entrada->proveedor->razon_social ?? '-' }}</div>
                    <div class="text-[0.7rem] text-slate-400">RUC: {{ $entrada->proveedor->ruc ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="flex flex-col items-center gap-1">
                    <span class="historial-badge-soft badge-green-soft" style="font-size: 0.9rem; padding: 0.3rem 0.8rem;">
                        {{ $entrada->cantidad }}
                    </span>
                    <div class="text-[0.75rem] font-bold text-amber-500">
                        {{ $entrada->stock_anterior }} → {{ $entrada->stock_nuevo }}
                    </div>
                </div>
            </td>
            <td>
                <div class="flex flex-col items-center gap-1">
                    <span class="historial-badge-soft badge-blue-soft" style="font-size: 0.85rem;">{{ $entrada->lote }}</span>
                    <div class="text-[0.7rem] font-semibold {{ $entrada->fecha_vencimiento && $entrada->fecha_vencimiento->isPast() ? 'text-red-400' : 'text-slate-400' }}">
                        Vence: {{ $entrada->fecha_vencimiento ? $entrada->fecha_vencimiento->format('d/m/Y') : '-' }}
                    </div>
                </div>
            </td>
            <td>
                <div class="flex flex-col items-center">
                    <div class="flex items-center gap-2">
                        @if($entrada->hubo_cambio_precio_venta)
                            <span class="total-tachado">S/ {{ number_format($entrada->precio_venta_anterior, 2) }}</span>
                        @endif
                        <span class="text-emerald-500 font-bold text-[0.95rem]">S/ {{ number_format($entrada->precio_venta_nuevo, 2) }}</span>
                    </div>
                    <div class="text-[0.75rem] font-semibold mt-1">
                        @if($entrada->hubo_cambio_precio_compra)
                            <span class="total-tachado">S/ {{ number_format($entrada->precio_compra_anterior, 2) }}</span>
                        @endif
                        <span class="text-slate-500">S/ {{ number_format($entrada->precio_compra_nuevo, 2) }}</span>
                    </div>
                </div>
            </td>
            <td><div class="text-[0.8rem] font-semibold text-slate-500">{{ $entrada->usuario->name ?? 'Sistema' }}</div></td>
        </tr>
        @empty
        <tr>
            <td colspan="7">
                <div class="py-20 text-center">
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:#f3f4f6; color:#94a3b8; margin-bottom:10px;">
                        <iconify-icon icon="solar:magnifer-zoom-out-bold-duotone" style="font-size:28px"></iconify-icon>
                    </div>
                    <div style="font-size:18px; font-weight:700; color:#1e293b; margin-bottom:5px;">No se encontraron resultados</div>
                    <div style="font-size:14px; color:#64748b;">No hay entradas registradas que coincidan con los filtros aplicados.</div>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($entradas->hasPages())
<div class="px-6 py-4 border-t border-slate-100 bg-white flex justify-between items-center">
    <div class="text-[0.8rem] font-semibold text-slate-400">
        Mostrando <span class="text-slate-500">{{ $entradas->firstItem() }}</span> a <span class="text-slate-500">{{ $entradas->lastItem() }}</span> de <span class="text-slate-500">{{ $entradas->total() }}</span> registros
    </div>
    <div class="flex items-center gap-1">
        @if ($entradas->onFirstPage()) 
            <span class="pagination-btn pagination-btn-disabled">‹ Anterior</span> 
        @else 
            <a href="{{ $entradas->previousPageUrl() }}" class="pagination-btn">‹ Anterior</a> 
        @endif

        @foreach ($entradas->getUrlRange(max(1, $entradas->currentPage() - 2), min($entradas->lastPage(), $entradas->currentPage() + 2)) as $page => $url)
            @if ($page == $entradas->currentPage()) 
                <span class="pagination-btn pagination-btn-current">{{ $page }}</span> 
            @else 
                <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a> 
            @endif
        @endforeach

        @if ($entradas->hasMorePages()) 
            <a href="{{ $entradas->nextPageUrl() }}" class="pagination-btn">Siguiente ›</a> 
        @else 
            <span class="pagination-btn pagination-btn-disabled">Siguiente ›</span> 
        @endif
    </div>
</div>
@endif
