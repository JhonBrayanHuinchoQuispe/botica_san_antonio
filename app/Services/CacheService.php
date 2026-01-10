<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    // Tiempos de cache en minutos
    const CACHE_PRODUCTOS = 30;
    const CACHE_CATEGORIAS = 60;
    const CACHE_UBICACIONES = 60;
    const CACHE_ESTADISTICAS = 15;
    const CACHE_REPORTES = 10;

    /**
     * Obtener datos del cache o ejecutar callback
     */
    public static function remember(string $key, int $minutes, callable $callback)
    {
        try {
            return Cache::remember($key, now()->addMinutes($minutes), $callback);
        } catch (\Exception $e) {
            Log::error("Error en cache: {$e->getMessage()}", [
                'key' => $key,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si falla el cache, ejecutar directamente
            return $callback();
        }
    }

    /**
     * Búsqueda instantánea de productos con cache
     */
    public static function buscarProductos(string $termino, int $limit = 20)
    {
        if (empty(trim($termino))) {
            return collect([]);
        }

        $cacheKey = 'busqueda.productos.' . md5(strtolower($termino)) . ".{$limit}";
        
        return self::remember($cacheKey, 5, function () use ($termino, $limit) {
            return \App\Models\Producto::where(function($query) use ($termino) {
                $query->where('nombre', 'LIKE', "%{$termino}%")
                      ->orWhere('codigo', 'LIKE', "%{$termino}%")
                      ->orWhere('codigo_barras', 'LIKE', "%{$termino}%");
            })
            ->with(['categoria_model:id,nombre', 'presentacion_model:id,nombre'])
            ->select(['id', 'nombre', 'codigo', 'codigo_barras', 'precio_venta', 'stock', 'categoria_id', 'presentacion_id'])
            ->limit($limit)
            ->get();
        });
    }

    /**
     * Obtener productos con paginación y cache
     */
    public static function getProductosPaginados(int $page = 1, int $perPage = 50, array $filtros = [])
    {
        $filtrosKey = md5(serialize($filtros));
        $cacheKey = "productos.paginados.{$page}.{$perPage}.{$filtrosKey}";
        
        return self::remember($cacheKey, self::CACHE_PRODUCTOS, function () use ($page, $perPage, $filtros) {
            $query = \App\Models\Producto::with(['categoria_model:id,nombre', 'presentacion_model:id,nombre', 'ubicacion:id,nombre']);
            
            // Aplicar filtros
            if (!empty($filtros['categoria_id'])) {
                $query->where('categoria_id', $filtros['categoria_id']);
            }
            
            if (!empty($filtros['stock_bajo'])) {
                $query->where('stock', '<=', 10);
            }
            
            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * Limpiar cache de productos
     */
    public static function clearProductos(): void
    {
        $keys = [
            'productos.all',
            'productos.activos',
            'productos.bajo_stock',
            'productos.por_vencer',
            'productos.vencidos',
            'productos.estadisticas',
            'productos.mas_vendidos',
            'productos.recientes'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Limpiar cache con patrones
        self::clearByPattern('productos.*');
        self::clearByPattern('inventario.*');
    }

    /**
     * Limpiar cache de categorías
     */
    public static function clearCategorias(): void
    {
        $keys = [
            'categorias.all',
            'categorias.activas',
            'categorias.con_productos'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Limpiar cache de ubicaciones
     */
    public static function clearUbicaciones(): void
    {
        $keys = [
            'ubicaciones.all',
            'ubicaciones.activas',
            'ubicaciones.con_productos'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Limpiar cache de estadísticas
     */
    public static function clearEstadisticas(): void
    {
        self::clearByPattern('estadisticas.*');
        self::clearByPattern('reportes.*');
        self::clearByPattern('dashboard.*');
    }

    /**
     * Limpiar cache por patrón (simulado)
     */
    private static function clearByPattern(string $pattern): void
    {
        // Laravel no tiene flush por patrón nativo, 
        // pero podemos simular con tags si usamos Redis
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys(str_replace('*', '*', $pattern));
                
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo limpiar cache por patrón: {$pattern}");
        }
    }

    /**
     * Generar key de cache para productos con filtros
     */
    public static function getProductosKey(array $filters = []): string
    {
        $baseKey = 'productos';
        
        if (!empty($filters)) {
            ksort($filters);
            $filterString = md5(serialize($filters));
            return "{$baseKey}.filtered.{$filterString}";
        }
        
        return "{$baseKey}.all";
    }

    /**
     * Generar key de cache para búsquedas
     */
    public static function getBusquedaKey(string $term, array $filters = []): string
    {
        $baseKey = 'busqueda';
        $termHash = md5($term);
        
        if (!empty($filters)) {
            ksort($filters);
            $filterString = md5(serialize($filters));
            return "{$baseKey}.{$termHash}.{$filterString}";
        }
        
        return "{$baseKey}.{$termHash}";
    }

    /**
     * Limpiar todo el cache
     */
    public static function clearAll(): void
    {
        try {
            Cache::flush();
            Log::info('Cache completamente limpiado');
        } catch (\Exception $e) {
            Log::error("Error limpiando cache: {$e->getMessage()}");
        }
    }

    /**
     * Obtener información del cache
     */
    public static function getInfo(): array
    {
        try {
            $driver = config('cache.default');
            $store = Cache::getStore();
            
            return [
                'driver' => $driver,
                'store' => get_class($store),
                'status' => 'activo'
            ];
        } catch (\Exception $e) {
            return [
                'driver' => 'unknown',
                'store' => 'unknown',
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}