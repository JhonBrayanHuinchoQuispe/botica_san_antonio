<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductoService
{
    protected $cacheTime = 3600; // 1 hora

    /**
     * Obtener productos con cache
     */
    public function getProductosConCache(array $filtros = [])
    {
        $cacheKey = 'productos_' . md5(serialize($filtros));
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($filtros) {
            $query = Producto::query();
            
            if (!empty($filtros['categoria'])) {
                $query->where('categoria', $filtros['categoria']);
            }
            
            if (!empty($filtros['estado'])) {
                $query->where('estado', $filtros['estado']);
            }
            
            return $query->orderBy('nombre')->get();
        });
    }

    /**
     * Obtener productos con bajo stock
     */
    public function getProductosBajoStock()
    {
        return Cache::remember('productos_bajo_stock', $this->cacheTime, function () {
            return Producto::whereRaw('stock_actual <= stock_minimo')
                          ->orderBy('stock_actual', 'asc')
                          ->get();
        });
    }

    /**
     * Obtener productos próximos a vencer
     */
    public function getProductosProximosVencer($dias = 30)
    {
        $cacheKey = "productos_proximos_vencer_{$dias}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($dias) {
            $fechaLimite = Carbon::now()->addDays($dias);
            
            return Producto::where('fecha_vencimiento', '<=', $fechaLimite)
                          ->where('fecha_vencimiento', '>', Carbon::now())
                          ->orderBy('fecha_vencimiento', 'asc')
                          ->get();
        });
    }

    /**
     * Actualizar stock de producto
     */
    public function actualizarStock($productoId, $cantidad, $tipo, $motivo = null)
    {
        return DB::transaction(function () use ($productoId, $cantidad, $tipo, $motivo) {
            $producto = Producto::findOrFail($productoId);
            
            $stockAnterior = $producto->stock_actual;
            
            if ($tipo === 'entrada') {
                $producto->stock_actual += $cantidad;
            } else {
                if ($producto->stock_actual < $cantidad) {
                    throw new \Exception('Stock insuficiente');
                }
                $producto->stock_actual -= $cantidad;
            }
            
            $producto->save();
            
            // Actualizar estado automáticamente
            $this->actualizarEstadoProducto($producto);
            
            // Registrar movimiento de stock
            MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $producto->stock_actual,
                'motivo' => $motivo,
                'usuario_id' => Auth::check() ? Auth::user()->id : 1,
                'fecha' => Carbon::now()
            ]);
            
            // Limpiar cache relacionado
            $this->limpiarCacheProducto($producto->id);
            
            return $producto;
        });
    }

    /**
     * Limpiar cache relacionado con productos
     */
    public function limpiarCacheProducto($productoId = null)
    {
        $tags = [
            'productos_*',
            'productos_bajo_stock',
            'productos_proximos_vencer_*',
            'estadisticas_productos'
        ];
        
        foreach ($tags as $tag) {
            Cache::forget($tag);
        }
        
        if ($productoId) {
            Cache::forget("producto_{$productoId}");
        }
    }

    /**
     * Buscar productos
     */
    public function buscarProductos($termino, $limite = 20)
    {
        $cacheKey = "busqueda_productos_" . md5($termino . $limite);
        
        return Cache::remember($cacheKey, 300, function () use ($termino, $limite) { // 5 minutos
            return Producto::where('nombre', 'like', "%{$termino}%")
                          ->orWhere('codigo_barras', 'like', "%{$termino}%")
                          ->orWhere('lote', 'like', "%{$termino}%")
                          ->limit($limite)
                          ->get();
        });
    }

    /**
     * Obtener estadísticas de productos
     */
    public function getEstadisticas()
    {
        return Cache::remember('estadisticas_productos', $this->cacheTime, function () {
            return [
                'total' => Producto::count(),
                'bajo_stock' => Producto::whereRaw('stock_actual <= stock_minimo')->count(),
                'vencidos' => Producto::where('fecha_vencimiento', '<', Carbon::now())->count(),
                'por_vencer' => Producto::whereBetween('fecha_vencimiento', [
                    Carbon::now(),
                    Carbon::now()->addDays(30)
                ])->count(),
                'sin_stock' => Producto::where('stock_actual', 0)->count(),
                'valor_inventario' => Producto::sum(DB::raw('stock_actual * precio_compra'))
            ];
        });
    }

    /**
     * Actualizar estado del producto automáticamente
     */
    private function actualizarEstadoProducto(Producto $producto)
    {
        $ahora = Carbon::now();
        $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
        
        if ($fechaVencimiento->isPast()) {
            $producto->estado = 'Vencido';
        } elseif ($fechaVencimiento->diffInDays($ahora) <= 30) {
            $producto->estado = 'Por vencer';
        } elseif ($producto->stock_actual <= $producto->stock_minimo) {
            $producto->estado = 'Bajo stock';
        } else {
            $producto->estado = 'Normal';
        }
        
        $producto->save();
    }
}