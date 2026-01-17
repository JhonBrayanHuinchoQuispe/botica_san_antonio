<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\PuntoVenta\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryOptimizationService
{
    public function getProductosOptimizados(array $filtros = [])
    {
        $search = trim($filtros['search'] ?? '');
        $estado = $filtros['estado'] ?? 'todos';

        $q = Producto::query();

        // Filtros de búsqueda flexibles
        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $like = '%' . $search . '%';
                $qq->where('nombre', 'like', $like)
                   ->orWhere('categoria', 'like', $like)
                   ->orWhere('marca', 'like', $like)
                   ->orWhere('concentracion', 'like', $like)
                   ->orWhere('presentacion', 'like', $like);
                if (Schema::hasColumn('productos', 'codigo_barras')) {
                    $qq->orWhere('codigo_barras', 'like', $like);
                }
            });
        }

        // Filtro por estado lógico
        $hoy = Carbon::now('America/Lima')->startOfDay();
        if ($estado && $estado !== 'todos') {
            // Normalize input to handle variations (Title Case, snake_case, lowercase)
            $estadoNorm = strtolower(str_replace(' ', '_', $estado));

            switch ($estadoNorm) {
                case 'bajo_stock':
                    if (Schema::hasColumn('productos', 'stock_minimo')) {
                        $q->whereColumn('stock_actual', '<=', 'stock_minimo');
                    } else {
                        $q->where('stock_actual', '<=', 5);
                    }
                    break;
                case 'agotado':
                    $q->where('stock_actual', '<=', 0);
                    break;
                case 'por_vencer':
                    $q->where(function($sub) use ($hoy) {
                        // Verificar fecha del producto
                        if (Schema::hasColumn('productos', 'fecha_vencimiento')) {
                            $sub->where(function($p) use ($hoy) {
                                $p->whereDate('fecha_vencimiento', '>', $hoy)
                                  ->whereDate('fecha_vencimiento', '<=', Carbon::now('America/Lima')->addDays(90));
                            });
                        }
                        
                        // O verificar si tiene algún lote por vencer
                        if (Schema::hasTable('producto_ubicaciones')) {
                            $sub->orWhereHas('ubicaciones', function($uq) use ($hoy) {
                                $uq->where('cantidad', '>', 0)
                                   ->whereNotNull('fecha_vencimiento')
                                   ->whereDate('fecha_vencimiento', '>', $hoy)
                                   ->whereDate('fecha_vencimiento', '<=', Carbon::now('America/Lima')->addDays(90));
                            });
                        }
                    });
                    break;
                case 'vencido':
                    $q->where(function($sub) use ($hoy) {
                        // Verificar fecha del producto
                        if (Schema::hasColumn('productos', 'fecha_vencimiento')) {
                            $sub->whereDate('fecha_vencimiento', '<', $hoy);
                        }
                        
                        // O verificar si tiene algún lote vencido
                        if (Schema::hasTable('producto_ubicaciones')) {
                            $sub->orWhereHas('ubicaciones', function($uq) use ($hoy) {
                                $uq->where('cantidad', '>', 0)
                                   ->whereNotNull('fecha_vencimiento')
                                   ->whereDate('fecha_vencimiento', '<', $hoy);
                            });
                        }
                    });
                    break;
                case 'normal':
                    // Excluir estados críticos
                    $q->where('stock_actual', '>', 0);
                    if (Schema::hasColumn('productos', 'stock_minimo')) {
                        $q->whereColumn('stock_actual', '>', 'stock_minimo');
                    }
                    
                    // Excluir productos con fecha de vencimiento crítica
                    if (Schema::hasColumn('productos', 'fecha_vencimiento')) {
                        $q->where(function($qq) use ($hoy) {
                            $qq->whereNull('fecha_vencimiento')
                               ->orWhereDate('fecha_vencimiento', '>=', $hoy->copy()->addDays(91));
                        });
                    }
                    
                    // Y ADEMÁS, Excluir productos que tengan lotes críticos (vencidos o por vencer)
                    if (Schema::hasTable('producto_ubicaciones')) {
                        $q->whereDoesntHave('ubicaciones', function($uq) use ($hoy) {
                            $uq->where('cantidad', '>', 0)
                               ->whereNotNull('fecha_vencimiento')
                               ->whereDate('fecha_vencimiento', '<=', Carbon::now('America/Lima')->addDays(90));
                        });
                    }
                    break;
            }
        }

        // Ordenamiento razonable
        $q->orderBy('nombre');

        return $q;
    }

    public function getDashboardDataOptimizado(): array
    {
        $productosStockCritico = Producto::query()
            ->where(function ($q) {
                if (Schema::hasColumn('productos', 'stock_minimo')) {
                    $q->whereColumn('stock_actual', '<=', 'stock_minimo');
                }
                $q->orWhere('stock_actual', '<=', 5);
            })
            ->orderBy('stock_actual', 'asc')
            ->limit(100)
            ->get();

        $productosProximosVencer = collect();
        if (Schema::hasColumn('productos', 'fecha_vencimiento')) {
            $productosProximosVencer = Producto::whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '>', Carbon::now('America/Lima'))
                ->whereDate('fecha_vencimiento', '<=', Carbon::now('America/Lima')->addDays(30))
                ->orderBy('fecha_vencimiento')
                ->limit(100)
                ->get();
        }

        $ventasHoy = 0;
        $ventasMes = 0;
        if (Schema::hasTable('ventas')) {
            $col = Schema::hasColumn('ventas', 'fecha_venta') ? 'fecha_venta' : 'created_at';
            $zona = 'America/Lima';
            $inicioHoy = Carbon::now($zona)->startOfDay();
            $finHoy = Carbon::now($zona)->endOfDay();
            $inicioMes = Carbon::now($zona)->startOfMonth();
            $finMes = Carbon::now($zona)->endOfMonth();

            $ventasHoy = Venta::whereBetween($col, [$inicioHoy, $finHoy])
                ->where('estado', 'completada')
                ->sum('total');

            $ventasMes = Venta::whereBetween($col, [$inicioMes, $finMes])
                ->where('estado', 'completada')
                ->sum('total');
        }

        return [
            'productos_stock_critico' => $productosStockCritico,
            'productos_proximos_vencer' => $productosProximosVencer,
            'ventas_hoy' => (float) $ventasHoy,
            'ventas_mes' => (float) $ventasMes,
        ];
    }

    public function getProductosMasVendidosOptimizado(int $limite = 10, ?int $dias = null)
    {
        $col = Schema::hasColumn('ventas', 'fecha_venta') ? 'ventas.fecha_venta' : 'ventas.created_at';

        $ventas = DB::table('venta_detalles')
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada');

        if ($dias && $dias > 0) {
            $zona = 'America/Lima';
            $desde = Carbon::now($zona)->subDays($dias);
            $hasta = Carbon::now($zona);
            $ventas->whereBetween($col, [$desde, $hasta]);
        }

        $ventasAgg = $ventas
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as total_vendido'))
            ->groupBy('venta_detalles.producto_id');

        return DB::table('productos')
            ->joinSub($ventasAgg, 'v', function ($join) {
                $join->on('productos.id', '=', 'v.producto_id');
            })
            ->where('productos.stock_actual', '>', 0)
            ->select([
                'productos.id', 'productos.nombre', 'productos.codigo_barras',
                'productos.concentracion', 'productos.presentacion', 'productos.precio_venta',
                'productos.stock_actual', 'productos.imagen', 'productos.ubicacion_almacen',
                'productos.categoria', 'productos.marca', 'productos.fecha_vencimiento',
                'productos.estado', DB::raw('v.total_vendido')
            ])
            ->orderByDesc('v.total_vendido')
            ->limit($limite)
            ->get();
    }
}
