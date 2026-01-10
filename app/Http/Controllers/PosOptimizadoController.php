<?php

namespace App\Http\Controllers;

use App\Services\PosOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PosOptimizadoController extends Controller
{
    private $posService;

    public function __construct(PosOptimizationService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Búsqueda ultra-rápida de productos
     * Objetivo: < 50ms
     */
    public function buscarProductos(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $termino = $request->get('q', '');
            $limite = min($request->get('limite', 20), 50); // Máximo 50 resultados
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'El término de búsqueda debe tener al menos 2 caracteres',
                    'data' => []
                ], 400);
            }

            $productos = $this->posService->buscarProductosUltraRapido($termino, $limite);
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            return response()->json([
                'success' => true,
                'data' => $productos,
                'meta' => [
                    'total' => $productos->count(),
                    'tiempo_ms' => round($executionTime, 2),
                    'termino' => $termino,
                    'limite' => $limite
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda POS optimizada', [
                'error' => $e->getMessage(),
                'termino' => $request->get('q', ''),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ], 500);
        }
    }

    /**
     * Obtener productos más vendidos
     */
    public function productosPopulares(Request $request): JsonResponse
    {
        try {
            $limite = min($request->get('limite', 50), 100);
            $productos = $this->posService->obtenerProductosMasVendidos($limite);

            return response()->json([
                'success' => true,
                'data' => $productos,
                'meta' => [
                    'total' => $productos->count(),
                    'tipo' => 'populares'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo productos populares', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo productos populares',
                'data' => []
            ], 500);
        }
    }

    /**
     * Obtener productos por categoría con paginación
     */
    public function productosPorCategoria(Request $request): JsonResponse
    {
        try {
            $categoria = $request->get('categoria');
            $pagina = max($request->get('pagina', 1), 1);
            $porPagina = min($request->get('por_pagina', 20), 50);

            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'La categoría es requerida',
                    'data' => []
                ], 400);
            }

            $productos = $this->posService->obtenerProductosPorCategoria($categoria, $pagina, $porPagina);

            return response()->json([
                'success' => true,
                'data' => $productos,
                'meta' => [
                    'categoria' => $categoria,
                    'pagina' => $pagina,
                    'por_pagina' => $porPagina,
                    'total' => $productos->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo productos por categoría', [
                'error' => $e->getMessage(),
                'categoria' => $request->get('categoria')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo productos por categoría',
                'data' => []
            ], 500);
        }
    }

    /**
     * Scroll infinito para catálogo grande
     */
    public function scrollInfinito(Request $request): JsonResponse
    {
        try {
            $ultimoId = $request->get('ultimo_id', 0);
            $limite = min($request->get('limite', 20), 50);
            
            $filtros = [
                'categoria' => $request->get('categoria'),
                'marca' => $request->get('marca'),
                'busqueda' => $request->get('busqueda')
            ];

            $productos = $this->posService->obtenerProductosScrollInfinito($ultimoId, $limite, $filtros);

            $tieneMore = $productos->count() === $limite;
            $nuevoUltimoId = $productos->isNotEmpty() ? $productos->last()['id'] : $ultimoId;

            return response()->json([
                'success' => true,
                'data' => $productos,
                'meta' => [
                    'ultimo_id' => $nuevoUltimoId,
                    'tiene_mas' => $tieneMore,
                    'total_cargados' => $productos->count(),
                    'filtros' => array_filter($filtros)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en scroll infinito', [
                'error' => $e->getMessage(),
                'ultimo_id' => $request->get('ultimo_id', 0)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error cargando más productos',
                'data' => []
            ], 500);
        }
    }

    /**
     * Estadísticas rápidas del POS
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->posService->obtenerEstadisticasRapidas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas POS', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas',
                'data' => []
            ], 500);
        }
    }

    /**
     * Limpiar caché del POS
     */
    public function limpiarCache(): JsonResponse
    {
        try {
            $this->posService->limpiarCache();

            return response()->json([
                'success' => true,
                'message' => 'Caché limpiado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando caché POS', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error limpiando caché'
            ], 500);
        }
    }

    /**
     * Precargar productos populares en caché
     */
    public function precargarCache(): JsonResponse
    {
        try {
            $this->posService->precargarProductosPopulares();

            return response()->json([
                'success' => true,
                'message' => 'Caché precargado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error precargando caché POS', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error precargando caché'
            ], 500);
        }
    }

    /**
     * Obtener producto por código de barras (ultra-rápido)
     */
    public function obtenerPorCodigoBarras(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $codigoBarras = $request->get('codigo_barras');
            
            if (!$codigoBarras) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de barras es requerido',
                    'data' => null
                ], 400);
            }

            // Búsqueda exacta por código de barras (debería ser instantánea con índice)
            $productos = $this->posService->buscarProductosUltraRapido($codigoBarras, 1);
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            if ($productos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'data' => null,
                    'meta' => [
                        'tiempo_ms' => round($executionTime, 2),
                        'codigo_barras' => $codigoBarras
                    ]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $productos->first(),
                'meta' => [
                    'tiempo_ms' => round($executionTime, 2),
                    'codigo_barras' => $codigoBarras
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo producto por código de barras', [
                'error' => $e->getMessage(),
                'codigo_barras' => $request->get('codigo_barras')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function obtenerCategorias(): JsonResponse
    {
        try {
            $categorias = \DB::table('productos')
                ->select('categoria')
                ->where('stock_actual', '>', 0)
                ->whereNotNull('categoria')
                ->groupBy('categoria')
                ->orderBy('categoria')
                ->pluck('categoria');

            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo categorías', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo categorías',
                'data' => []
            ], 500);
        }
    }

    /**
     * Obtener marcas disponibles
     */
    public function obtenerMarcas(): JsonResponse
    {
        try {
            $marcas = \DB::table('productos')
                ->select('marca')
                ->where('stock_actual', '>', 0)
                ->whereNotNull('marca')
                ->groupBy('marca')
                ->orderBy('marca')
                ->pluck('marca');

            return response()->json([
                'success' => true,
                'data' => $marcas
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo marcas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo marcas',
                'data' => []
            ], 500);
        }
    }
}