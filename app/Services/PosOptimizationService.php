<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PosOptimizationService
{
    private const CACHE_TTL = 300; // 5 minutos
    private const CACHE_PREFIX = 'pos_';
    
    /**
     * Búsqueda ultra-rápida de productos para POS
     * Objetivo: < 50ms de carga
     */
    public function buscarProductosUltraRapido($termino, $limite = 20)
    {
        $cacheKey = self::CACHE_PREFIX . 'busqueda_' . md5($termino . $limite);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($termino, $limite) {
            $startTime = microtime(true);
            
            // Consulta optimizada con índices específicos
            $productos = DB::table('productos')
                ->select([
                    'id', 'nombre', 'codigo_barras', 'concentracion', 'presentacion',
                    'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen',
                    'categoria', 'marca', 'fecha_vencimiento', 'estado'
                ])
                ->where('stock_actual', '>', 0)
                ->where(function($query) use ($termino) {
                    $query->where('nombre', 'LIKE', "{$termino}%")
                          ->orWhere('codigo_barras', 'LIKE', "{$termino}%")
                          ->orWhere('nombre', 'LIKE', "%{$termino}%");
                })
                ->orderByRaw("
                    CASE 
                        WHEN nombre LIKE '{$termino}%' THEN 1
                        WHEN codigo_barras LIKE '{$termino}%' THEN 2
                        ELSE 3
                    END,
                    stock_actual DESC
                ")
                ->limit($limite)
                ->get();

            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // en milisegundos

            Log::info("POS Búsqueda ejecutada en {$executionTime}ms", [
                'termino' => $termino,
                'resultados' => $productos->count(),
                'tiempo_ms' => $executionTime
            ]);

            return $productos->map(function ($producto) {
                return $this->formatearProductoParaPOS($producto);
            });
        });
    }

    /**
     * Cargar productos más vendidos con caché
     */
    public function obtenerProductosMasVendidos($limite = 50)
    {
        $cacheKey = self::CACHE_PREFIX . 'mas_vendidos_' . $limite;
        
        return Cache::remember($cacheKey, self::CACHE_TTL * 2, function () use ($limite) {
            return DB::table('productos')
                ->select([
                    'productos.id', 'productos.nombre', 'productos.codigo_barras',
                    'productos.concentracion', 'productos.presentacion', 'productos.precio_venta',
                    'productos.stock_actual', 'productos.imagen', 'productos.ubicacion_almacen',
                    'productos.categoria', 'productos.marca', 'productos.fecha_vencimiento',
                    'productos.estado'
                ])
                ->leftJoin('venta_detalles', 'productos.id', '=', 'venta_detalles.producto_id')
                ->leftJoin('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->where('productos.stock_actual', '>', 0)
                ->where(function($query) {
                    $query->whereNull('ventas.created_at')
                          ->orWhere('ventas.created_at', '>=', now()->subDays(30));
                })
                ->groupBy([
                    'productos.id', 'productos.nombre', 'productos.codigo_barras',
                    'productos.concentracion', 'productos.presentacion', 'productos.precio_venta',
                    'productos.stock_actual', 'productos.imagen', 'productos.ubicacion_almacen',
                    'productos.categoria', 'productos.marca', 'productos.fecha_vencimiento',
                    'productos.estado'
                ])
                ->orderByRaw('COALESCE(SUM(venta_detalles.cantidad), 0) DESC, productos.stock_actual DESC')
                ->limit($limite)
                ->get()
                ->map(function ($producto) {
                    return $this->formatearProductoParaPOS($producto);
                });
        });
    }

    /**
     * Obtener productos por categoría con paginación inteligente
     */
    public function obtenerProductosPorCategoria($categoria, $pagina = 1, $porPagina = 20)
    {
        $cacheKey = self::CACHE_PREFIX . "categoria_{$categoria}_{$pagina}_{$porPagina}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoria, $pagina, $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            
            return DB::table('productos')
                ->select([
                    'id', 'nombre', 'codigo_barras', 'concentracion', 'presentacion',
                    'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen',
                    'categoria', 'marca', 'fecha_vencimiento', 'estado'
                ])
                ->where('categoria', $categoria)
                ->where('stock_actual', '>', 0)
                ->orderBy('stock_actual', 'desc')
                ->offset($offset)
                ->limit($porPagina)
                ->get()
                ->map(function ($producto) {
                    return $this->formatearProductoParaPOS($producto);
                });
        });
    }

    /**
     * Obtener estadísticas rápidas para el dashboard del POS
     */
    public function obtenerEstadisticasRapidas()
    {
        $cacheKey = self::CACHE_PREFIX . 'estadisticas_' . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 3600, function () { // 1 hora de caché
            return [
                'total_productos' => DB::table('productos')->where('stock_actual', '>', 0)->count(),
                'productos_stock_bajo' => DB::table('productos')
                    ->whereColumn('stock_actual', '<=', 'stock_minimo')
                    ->where('stock_actual', '>', 0)
                    ->count(),
                'ventas_hoy' => DB::table('ventas')
                    ->whereDate('created_at', today())
                    ->where('estado', 'completada')
                    ->count(),
                'ingresos_hoy' => DB::table('ventas')
                    ->whereDate('created_at', today())
                    ->where('estado', 'completada')
                    ->sum('total')
            ];
        });
    }

    /**
     * Formatear producto para respuesta del POS
     */
    private function formatearProductoParaPOS($producto)
    {
        $diasParaVencer = null;
        $estadoVencimiento = 'normal';

        if ($producto->fecha_vencimiento) {
            $fechaVencimiento = \Carbon\Carbon::parse($producto->fecha_vencimiento);
            $diasParaVencer = now()->diffInDays($fechaVencimiento, false);
            
            if ($diasParaVencer < 0) {
                $estadoVencimiento = 'vencido';
            } elseif ($diasParaVencer <= 30) {
                $estadoVencimiento = 'por_vencer';
            }
        }

        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo_barras' => $producto->codigo_barras,
            'concentracion' => $producto->concentracion,
            'presentacion' => $producto->presentacion,
            'precio_venta' => (float) $producto->precio_venta,
            'stock_actual' => $producto->stock_actual,
            'imagen' => $this->obtenerUrlImagen($producto->imagen),
            'imagen_url' => $this->obtenerUrlImagen($producto->imagen),
            'ubicacion_almacen' => $producto->ubicacion_almacen,
            'categoria' => $producto->categoria ?? 'Sin categoría',
            'marca' => $producto->marca ?? 'Sin marca',
            'fecha_vencimiento' => $producto->fecha_vencimiento,
            'dias_para_vencer' => $diasParaVencer,
            'estado_vencimiento' => $estadoVencimiento,
            'estado' => $producto->stock_actual > 0 ? 'disponible' : 'sin_stock',
            'estado_producto' => $producto->estado
        ];
    }

    /**
     * Obtener URL de imagen optimizada
     */
    private function obtenerUrlImagen($imagen)
    {
        if (!$imagen) {
            return asset('assets/images/default-product.svg');
        }

        $raw = (string) $imagen;
        if (preg_match('/^https?:\/\//i', $raw)) {
            return $raw;
        }

        $rel = ltrim($raw, '/');

        if (Storage::disk('public')->exists($rel)) {
            return asset('storage/' . $rel);
        }

        $candidates = [$rel];
        if (strpos($rel, '/') === false) {
            $candidates[] = 'productos/' . $rel;
        }
        if (str_starts_with($rel, 'storage/')) {
            $stripped = substr($rel, strlen('storage/'));
            $candidates[] = $stripped;
            $candidates[] = 'storage/' . $stripped;
        } else {
            $candidates[] = 'storage/' . $rel;
        }

        foreach ($candidates as $candidate) {
            $p = public_path($candidate);
            if (file_exists($p)) {
                return asset($candidate);
            }
        }

        return asset('assets/images/default-product.svg');
    }

    /**
     * Limpiar caché del POS
     */
    public function limpiarCache()
    {
        $keys = Cache::getRedis()->keys(self::CACHE_PREFIX . '*');
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }

    /**
     * Precarga de productos populares en caché
     */
    public function precargarProductosPopulares()
    {
        // Precargar los 100 productos más vendidos
        $this->obtenerProductosMasVendidos(100);
        
        // Precargar estadísticas
        $this->obtenerEstadisticasRapidas();
        
        // Precargar categorías principales
        $categoriasPrincipales = DB::table('productos')
            ->select('categoria')
            ->where('stock_actual', '>', 0)
            ->groupBy('categoria')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->pluck('categoria');

        foreach ($categoriasPrincipales as $categoria) {
            $this->obtenerProductosPorCategoria($categoria, 1, 20);
        }
    }

    /**
     * Obtener productos con scroll infinito
     */
    public function obtenerProductosScrollInfinito($ultimoId = 0, $limite = 20, $filtros = [])
    {
        $query = DB::table('productos')
            ->select([
                'id', 'nombre', 'codigo_barras', 'concentracion', 'presentacion',
                'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen',
                'categoria', 'marca', 'fecha_vencimiento', 'estado'
            ])
            ->where('stock_actual', '>', 0)
            ->where('id', '>', $ultimoId);

        // Aplicar filtros
        if (!empty($filtros['categoria'])) {
            $query->where('categoria', $filtros['categoria']);
        }
        
        if (!empty($filtros['marca'])) {
            $query->where('marca', $filtros['marca']);
        }

        if (!empty($filtros['busqueda'])) {
            $termino = $filtros['busqueda'];
            $query->where(function($q) use ($termino) {
                $q->where('nombre', 'LIKE', "%{$termino}%")
                  ->orWhere('codigo_barras', 'LIKE', "%{$termino}%");
            });
        }

        return $query->orderBy('id')
                    ->limit($limite)
                    ->get()
                    ->map(function ($producto) {
                        return $this->formatearProductoParaPOS($producto);
                    });
    }
}
