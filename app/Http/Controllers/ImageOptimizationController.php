<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageOptimizationController extends Controller
{
    private $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Optimizar todas las imágenes de productos existentes
     */
    public function optimizarTodasLasImagenes(): JsonResponse
    {
        try {
            $productos = DB::table('productos')
                ->whereNotNull('imagen')
                ->where('imagen', '!=', '')
                ->select('id', 'nombre', 'imagen')
                ->get();

            $resultados = [
                'total' => $productos->count(),
                'optimizadas' => 0,
                'errores' => 0,
                'ahorro_espacio' => 0
            ];

            foreach ($productos as $producto) {
                try {
                    $imagePath = $producto->imagen;
                    $fullPath = storage_path('app/public/' . $imagePath);
                    
                    if (!file_exists($fullPath)) {
                        $resultados['errores']++;
                        continue;
                    }

                    $tamanoOriginal = filesize($fullPath);
                    
                    // Optimizar imagen existente
                    if ($this->imageService->optimizeExistingImage($imagePath)) {
                        $tamanoOptimizado = filesize($fullPath);
                        $ahorro = $tamanoOriginal - $tamanoOptimizado;
                        
                        $resultados['optimizadas']++;
                        $resultados['ahorro_espacio'] += $ahorro;
                        
                        // Crear thumbnails si no existen
                        $this->imageService->createThumbnails($imagePath);
                        
                        Log::info("Imagen optimizada: {$producto->nombre}", [
                            'original_size' => $this->formatBytes($tamanoOriginal),
                            'optimized_size' => $this->formatBytes($tamanoOptimizado),
                            'savings' => $this->formatBytes($ahorro)
                        ]);
                    } else {
                        $resultados['errores']++;
                    }

                } catch (\Exception $e) {
                    Log::error("Error optimizando imagen del producto {$producto->id}: " . $e->getMessage());
                    $resultados['errores']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Optimización completada',
                'data' => [
                    'total_productos' => $resultados['total'],
                    'imagenes_optimizadas' => $resultados['optimizadas'],
                    'errores' => $resultados['errores'],
                    'ahorro_espacio' => $this->formatBytes($resultados['ahorro_espacio']),
                    'porcentaje_exito' => $resultados['total'] > 0 ? 
                        round(($resultados['optimizadas'] / $resultados['total']) * 100, 2) : 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en optimización masiva de imágenes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error durante la optimización masiva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de imágenes
     */
    public function obtenerEstadisticas(): JsonResponse
    {
        try {
            $totalProductos = DB::table('productos')->count();
            $productosConImagen = DB::table('productos')
                ->whereNotNull('imagen')
                ->where('imagen', '!=', '')
                ->count();
            
            $productosSinImagen = $totalProductos - $productosConImagen;
            
            // Calcular espacio total usado por imágenes
            $espacioTotal = 0;
            $archivosImagenes = 0;
            
            if (Storage::disk('public')->exists('productos')) {
                $imagenes = Storage::disk('public')->files('productos');
                $archivosImagenes = count($imagenes);
                
                foreach ($imagenes as $imagen) {
                    $espacioTotal += Storage::disk('public')->size($imagen);
                }
            }

            // Obtener productos con imágenes más pesadas
            $imagenesPesadas = [];
            $productos = DB::table('productos')
                ->whereNotNull('imagen')
                ->where('imagen', '!=', '')
                ->select('id', 'nombre', 'imagen')
                ->limit(100)
                ->get();

            foreach ($productos as $producto) {
                $fullPath = storage_path('app/public/' . $producto->imagen);
                if (file_exists($fullPath)) {
                    $tamano = filesize($fullPath);
                    if ($tamano > 500000) { // Más de 500KB
                        $imagenesPesadas[] = [
                            'id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'imagen' => $producto->imagen,
                            'tamano' => $this->formatBytes($tamano),
                            'tamano_bytes' => $tamano
                        ];
                    }
                }
            }

            // Ordenar por tamaño descendente
            usort($imagenesPesadas, function($a, $b) {
                return $b['tamano_bytes'] - $a['tamano_bytes'];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen' => [
                        'total_productos' => $totalProductos,
                        'productos_con_imagen' => $productosConImagen,
                        'productos_sin_imagen' => $productosSinImagen,
                        'porcentaje_con_imagen' => $totalProductos > 0 ? 
                            round(($productosConImagen / $totalProductos) * 100, 2) : 0,
                        'espacio_total' => $this->formatBytes($espacioTotal),
                        'total_archivos' => $archivosImagenes,
                        'espacio_promedio_por_imagen' => $archivosImagenes > 0 ? 
                            $this->formatBytes($espacioTotal / $archivosImagenes) : '0 B'
                    ],
                    'imagenes_pesadas' => array_slice($imagenesPesadas, 0, 10), // Top 10
                    'recomendaciones' => $this->generarRecomendaciones($espacioTotal, $archivosImagenes, count($imagenesPesadas))
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de imágenes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar imágenes no utilizadas
     */
    public function limpiarImagenesNoUtilizadas(): JsonResponse
    {
        try {
            $imagenesEnUso = DB::table('productos')
                ->whereNotNull('imagen')
                ->where('imagen', '!=', '')
                ->pluck('imagen')
                ->toArray();

            $todasLasImagenes = Storage::disk('public')->files('productos');
            $imagenesAEliminar = array_diff($todasLasImagenes, $imagenesEnUso);

            $eliminadas = 0;
            $espacioLiberado = 0;

            foreach ($imagenesAEliminar as $imagen) {
                $tamano = Storage::disk('public')->size($imagen);
                
                if (Storage::disk('public')->delete($imagen)) {
                    $eliminadas++;
                    $espacioLiberado += $tamano;
                }
            }

            Log::info("Limpieza de imágenes completada", [
                'total_imagenes' => count($todasLasImagenes),
                'en_uso' => count($imagenesEnUso),
                'eliminadas' => $eliminadas,
                'espacio_liberado' => $this->formatBytes($espacioLiberado)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Limpieza completada exitosamente',
                'data' => [
                    'imagenes_eliminadas' => $eliminadas,
                    'espacio_liberado' => $this->formatBytes($espacioLiberado),
                    'imagenes_restantes' => count($todasLasImagenes) - $eliminadas
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando imágenes no utilizadas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error durante la limpieza',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar placeholders para productos sin imagen
     */
    public function generarPlaceholders(): JsonResponse
    {
        try {
            $productosSinImagen = DB::table('productos')
                ->where(function($query) {
                    $query->whereNull('imagen')
                          ->orWhere('imagen', '');
                })
                ->select('id', 'nombre', 'categoria')
                ->get();

            $generados = 0;

            foreach ($productosSinImagen as $producto) {
                $placeholder = $this->crearPlaceholderSVG($producto->nombre, $producto->categoria);
                
                if ($placeholder) {
                    DB::table('productos')
                        ->where('id', $producto->id)
                        ->update([
                            'imagen' => $placeholder,
                            'updated_at' => now()
                        ]);
                    
                    $generados++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Placeholders generados exitosamente',
                'data' => [
                    'productos_sin_imagen' => $productosSinImagen->count(),
                    'placeholders_generados' => $generados
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando placeholders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generando placeholders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear placeholder SVG para producto
     */
    private function crearPlaceholderSVG($nombreProducto, $categoria = null)
    {
        try {
            $iniciales = $this->obtenerIniciales($nombreProducto);
            $color = $this->generarColorPorCategoria($categoria);
            
            $svg = '<?xml version="1.0" encoding="UTF-8"?>
            <svg width="400" height="400" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                <rect width="400" height="400" fill="' . $color . '"/>
                <text x="200" y="220" font-family="Arial, sans-serif" font-size="80" font-weight="bold" 
                      text-anchor="middle" fill="white" opacity="0.8">' . $iniciales . '</text>
                <text x="200" y="280" font-family="Arial, sans-serif" font-size="16" 
                      text-anchor="middle" fill="white" opacity="0.6">PRODUCTO</text>
            </svg>';
            
            $filename = 'placeholder_' . md5($nombreProducto) . '.svg';
            $path = 'productos/placeholders/' . $filename;
            
            Storage::disk('public')->put($path, $svg);
            
            return $path;

        } catch (\Exception $e) {
            Log::error("Error creando placeholder para {$nombreProducto}: " . $e->getMessage());
            return null;
        }
    }

    private function obtenerIniciales($texto)
    {
        $palabras = explode(' ', strtoupper($texto));
        $iniciales = '';
        
        foreach (array_slice($palabras, 0, 2) as $palabra) {
            if (!empty($palabra)) {
                $iniciales .= substr($palabra, 0, 1);
            }
        }
        
        return $iniciales ?: 'PR';
    }

    private function generarColorPorCategoria($categoria)
    {
        $colores = [
            'MEDICAMENTOS' => '#3498db',
            'COSMETICOS' => '#e91e63',
            'VITAMINAS' => '#4caf50',
            'CUIDADO PERSONAL' => '#ff9800',
            'BEBES' => '#9c27b0',
            'PRIMEROS AUXILIOS' => '#f44336',
            'SUPLEMENTOS' => '#2196f3',
            'HIGIENE' => '#00bcd4'
        ];
        
        if ($categoria && isset($colores[strtoupper($categoria)])) {
            return $colores[strtoupper($categoria)];
        }
        
        // Color por defecto basado en hash
        $hash = md5($categoria ?: 'default');
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function generarRecomendaciones($espacioTotal, $totalArchivos, $imagenesPesadas)
    {
        $recomendaciones = [];
        
        if ($imagenesPesadas > 0) {
            $recomendaciones[] = "Tienes {$imagenesPesadas} imágenes que superan los 500KB. Considera optimizarlas.";
        }
        
        if ($espacioTotal > 100 * 1024 * 1024) { // Más de 100MB
            $recomendaciones[] = "El espacio total de imágenes es alto. Ejecuta la optimización masiva.";
        }
        
        if ($totalArchivos > 1000) {
            $recomendaciones[] = "Tienes muchas imágenes. Considera limpiar las no utilizadas.";
        }
        
        if (empty($recomendaciones)) {
            $recomendaciones[] = "Las imágenes están bien optimizadas. ¡Buen trabajo!";
        }
        
        return $recomendaciones;
    }
}