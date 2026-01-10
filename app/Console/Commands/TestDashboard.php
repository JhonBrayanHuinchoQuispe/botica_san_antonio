<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\PuntoVenta\Venta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestDashboard extends Command
{
    protected $signature = 'test:dashboard';
    protected $description = 'Prueba los datos del dashboard de análisis';

    public function handle()
    {
        $this->info('🚀 Probando datos del Dashboard de Análisis...');
        $this->newLine();
        
        try {
            // Prueba 1: Inventario
            $totalProductos = Producto::count();
            $this->line("✅ Total productos: " . number_format($totalProductos));
            
            $productosStockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count();
            $this->line("⚠️  Productos con stock bajo: " . $productosStockBajo);
            
            // Prueba 2: Ventas
            $ventasHoy = Venta::whereDate('fecha_venta', today())->where('estado', 'completada')->count();
            $this->line("📊 Ventas hoy: " . $ventasHoy);
            
            $ventasMes = Venta::whereMonth('fecha_venta', now()->month)
                             ->whereYear('fecha_venta', now()->year)
                             ->where('estado', 'completada')
                             ->count();
            $this->line("📈 Ventas del mes: " . $ventasMes);
            
            $ingresosMes = Venta::whereMonth('fecha_venta', now()->month)
                               ->whereYear('fecha_venta', now()->year)
                               ->where('estado', 'completada')
                               ->sum('total');
            $this->line("💰 Ingresos del mes: S/. " . number_format($ingresosMes, 2));
            
            // Prueba 3: Datos para gráfico
            $this->newLine();
            $this->info('📊 Datos de los últimos 7 días:');
            
            for ($i = 6; $i >= 0; $i--) {
                $fecha = now()->subDays($i);
                $total = Venta::whereDate('fecha_venta', $fecha->toDateString())
                             ->where('estado', 'completada')
                             ->sum('total');
                $ventasCount = Venta::whereDate('fecha_venta', $fecha->toDateString())
                                   ->where('estado', 'completada')
                                   ->count();
                
                $this->line("  " . $fecha->format('d/m/Y') . ": " . $ventasCount . " ventas - S/. " . number_format($total, 2));
            }
            
            // Prueba 4: Productos más vendidos
            $this->newLine();
            if (DB::getSchemaBuilder()->hasTable('venta_detalles')) {
                $productosMasVendidos = DB::table('venta_detalles')
                    ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                    ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                    ->where('ventas.estado', 'completada')
                    ->whereMonth('ventas.fecha_venta', now()->month)
                    ->select(
                        'productos.nombre',
                        DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                        DB::raw('SUM(venta_detalles.subtotal) as ingresos_producto')
                    )
                    ->groupBy('productos.id', 'productos.nombre')
                    ->orderBy('total_vendido', 'desc')
                    ->limit(5)
                    ->get();
                
                $this->info('🏆 Top 5 productos más vendidos este mes:');
                if ($productosMasVendidos->count() > 0) {
                    foreach ($productosMasVendidos as $index => $producto) {
                        $this->line("  " . ($index + 1) . ". " . $producto->nombre . " (" . $producto->total_vendido . " vendidos - S/. " . number_format($producto->ingresos_producto, 2) . ")");
                    }
                } else {
                    $this->line("  No hay ventas registradas este mes.");
                }
            } else {
                $this->warn('⚠️  Tabla venta_detalles no existe todavía.');
            }
            
            // Prueba 5: Productos con stock crítico
            $this->newLine();
            $productosStockCritico = Producto::where(function($query) {
                    $query->where('stock_actual', '<=', 5)
                          ->orWhereColumn('stock_actual', '<=', 'stock_minimo');
                })
                ->orderBy('stock_actual', 'asc')
                ->limit(5)
                ->get();
            
            $this->error('🚨 Productos con stock crítico:');
            if ($productosStockCritico->count() > 0) {
                foreach ($productosStockCritico as $producto) {
                    $this->line("  • " . $producto->nombre . " (Stock: " . $producto->stock_actual . " - Mín: " . $producto->stock_minimo . ")");
                }
            } else {
                $this->line("  ✅ No hay productos con stock crítico.");
            }
            
            $this->newLine();
            $this->info('🎉 ¡Prueba completada exitosamente! El dashboard debería funcionar correctamente.');
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la prueba: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
} 