<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoUbicacion;
use App\Models\PuntoVenta\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\InventarioSaludExport;
use Maatwebsite\Excel\Facades\Excel;

class InventarioReporteController extends Controller
{
    public function index()
    {
        try {
            // 1. Valorización del Inventario
            $valorizacion = ProductoUbicacion::where('cantidad', '>', 0)
                ->sum(DB::raw('cantidad * precio_compra_lote'));

            // 2. Mapa de Riesgo de Vencimiento
            $vencimientoRiesgo = [
                'vencidos' => ProductoUbicacion::where('cantidad', '>', 0)->whereDate('fecha_vencimiento', '<', now())->count(),
                'proximos_3' => ProductoUbicacion::where('cantidad', '>', 0)
                    ->whereDate('fecha_vencimiento', '>=', now())
                    ->whereDate('fecha_vencimiento', '<=', now()->addMonths(3))
                    ->count(),
                'proximos_6' => ProductoUbicacion::where('cantidad', '>', 0)
                    ->whereDate('fecha_vencimiento', '>', now()->addMonths(3))
                    ->whereDate('fecha_vencimiento', '<=', now()->addMonths(6))
                    ->count(),
                'proximos_12' => ProductoUbicacion::where('cantidad', '>', 0)
                    ->whereDate('fecha_vencimiento', '>', now()->addMonths(6))
                    ->whereDate('fecha_vencimiento', '<=', now()->addMonths(12))
                    ->count(),
            ];

            // Detalle para Alertas Críticas
            $lotesVencidosDetalle = ProductoUbicacion::with('producto')
                ->where('cantidad', '>', 0)
                ->whereDate('fecha_vencimiento', '<', now())
                ->limit(5)->get();

            $lotesProximosDetalle = ProductoUbicacion::with('producto')
                ->where('cantidad', '>', 0)
                ->whereDate('fecha_vencimiento', '>=', now())
                ->whereDate('fecha_vencimiento', '<=', now()->addMonths(3))
                ->limit(5)->get();

            // 3. Productos "Muertos" (Sin ventas en 90 días)
            $productosVendidos90 = VentaDetalle::whereHas('venta', function($q) {
                    $q->where('fecha_venta', '>=', now()->subDays(90));
                })
                ->distinct()
                ->pluck('producto_id');

            $productosMuertosLista = Producto::with('categoria_model')
                ->whereNotIn('id', $productosVendidos90)
                ->where('stock_actual', '>', 0)
                ->select('*', DB::raw('DATEDIFF(NOW(), (SELECT MAX(v.fecha_venta) FROM ventas v JOIN venta_detalles vd ON v.id = vd.venta_id WHERE vd.producto_id = productos.id)) as dias_sin_venta'))
                ->paginate(10, ['*'], 'muertos_page');
            
            $productosMuertosCount = Producto::whereNotIn('id', $productosVendidos90)
                ->where('stock_actual', '>', 0)
                ->count();

            // 4. Quiebres de Stock
            $quiebresStockLista = Producto::where('stock_actual', '<=', 0)
                ->limit(5)->get();
            $quiebresStockCount = Producto::where('stock_actual', '<=', 0)->count();

            // 5. Top 5 Productos con más Valorización
            $topValorizacion = ProductoUbicacion::where('cantidad', '>', 0)
                ->select('producto_id', DB::raw('SUM(cantidad * precio_compra_lote) as total_valor'))
                ->groupBy('producto_id')
                ->orderBy('total_valor', 'desc')
                ->limit(5)
                ->get();
            
            $topValorizacion->load('producto');

            return view('admin.reportes.inventario-salud', compact(
                'valorizacion',
                'vencimientoRiesgo',
                'productosMuertosCount',
                'productosMuertosLista',
                'quiebresStockCount',
                'quiebresStockLista',
                'lotesVencidosDetalle',
                'lotesProximosDetalle',
                'topValorizacion'
            ));
        } catch (\Exception $e) {
            return "Error en reporte: " . $e->getMessage() . " en linea " . $e->getLine() . " de " . $e->getFile();
        }
    }

    public function exportarExcel()
    {
        try {
            // Intentar usar Maatwebsite Excel (V2 o V3)
            $data = ProductoUbicacion::with(['producto', 'ubicacion'])
                ->where('cantidad', '>', 0)
                ->get();

            if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
                // Verificar si es versión antigua (V2)
                if (method_exists('Maatwebsite\Excel\Facades\Excel', 'create')) {
                    $exportData = $data->map(function($lote) {
                        $dias = $lote->dias_para_vencer;
                        $estado = 'Saludable';
                        if ($dias !== null && $dias < 0) $estado = 'VENCIDO';
                        elseif ($dias !== null && $dias <= 90) $estado = 'RIESGO ALTO';
                        elseif ($dias !== null && $dias <= 180) $estado = 'RIESGO MEDIO';

                        return [
                            'ID Producto' => $lote->producto_id,
                            'Producto' => $lote->producto?->nombre ?? 'N/A',
                            'Lote' => $lote->lote,
                            'Ubicación' => $lote->ubicacion?->codigo ?? 'Sin Ubicar',
                            'Stock' => $lote->cantidad,
                            'P. Compra' => $lote->precio_compra_lote,
                            'P. Venta' => $lote->precio_venta_lote,
                            'Valorización' => $lote->cantidad * $lote->precio_compra_lote,
                            'Vencimiento' => $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('d/m/Y') : 'N/A',
                            'Estado Salud' => $estado,
                        ];
                    })->toArray();

                    return Excel::create('Salud_Inventario_' . date('Ymd_His'), function($excel) use ($exportData) {
                        $excel->sheet('Reporte', function($sheet) use ($exportData) {
                            $sheet->fromArray($exportData);
                        });
                    })->download('xlsx');
                } else {
                    // Versión nueva (V3)
                    return Excel::download(new InventarioSaludExport, 'Salud_Inventario_' . now()->format('Ymd_His') . '.xlsx');
                }
            }
            
            throw new \Exception("Librería Excel no encontrada.");

        } catch (\Exception $e) {
            // FALLBACK SEGURO: Generar HTML que Excel puede abrir (Sin dependencias)
            $data = ProductoUbicacion::with(['producto', 'ubicacion'])
                ->where('cantidad', '>', 0)
                ->get();
            
            $html = view('admin.reportes.excel-template', compact('data'))->render();
            
            // Añadir BOM para que Excel reconozca UTF-8 correctamente
            $bom = chr(239) . chr(187) . chr(191);
            
            return response($bom . $html)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="Salud_Inventario_' . date('Ymd') . '.xls"');
        }
    }
}
