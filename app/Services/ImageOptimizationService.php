<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizationService
{
    protected $optimizerChain;
    
    public function __construct()
    {
        $this->optimizerChain = OptimizerChainFactory::create();
    }
    
    /**
     * Optimiza y guarda una imagen de producto
     */
    public function optimizeProductImage(UploadedFile $file, string $directory = 'productos'): string
    {
        // Generar nombre único
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $directory . '/' . $filename;

        // Asegurar directorio
        $fullDirectory = storage_path('app/public/' . $directory);
        if (!file_exists($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }

        $fullPath = storage_path('app/public/' . $path);

        try {
            // Intentar usar Intervention v3 si hay driver disponible (gd/imagick)
            $driverAvailable = extension_loaded('gd') || extension_loaded('imagick') || class_exists('Imagick');
            if ($driverAvailable && class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
                $image = Image::read($file->getPathname());
                // Si supera 800x600, guardar directamente sin crecer más
                if (method_exists($image, 'width') && method_exists($image, 'height')) {
                    $w = $image->width();
                    $h = $image->height();
                    if ($w > 800 || $h > 600) {
                        // Escalar manteniendo proporción
                        $ratio = $w / ($h ?: 1);
                        if ($ratio >= 1) { // más ancho
                            $newW = 800; $newH = (int) round(800 / $ratio);
                        } else {
                            $newH = 600; $newW = (int) round(600 * $ratio);
                        }
                        if (method_exists($image, 'scale')) {
                            $image = $image->scale($newW, $newH);
                        } elseif (method_exists($image, 'resize')) {
                            $image = $image->resize($newW, $newH);
                        }
                    }
                }
                $image->save($fullPath, quality: 85);
            } else {
                // Fallback: guardar el archivo sin manipular (no requiere GD/Imagick)
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs($directory, $file, $filename);
            }
        } catch (\Throwable $e) {
            // Si falla manipulación, guardar sin cambios
            \Illuminate\Support\Facades\Storage::disk('public')->putFileAs($directory, $file, $filename);
        }

        // Intentar optimizar (no crítico si falla)
        try { $this->optimizerChain->optimize($fullPath); } catch (\Throwable $opt) {}

        return $path;
    }
    
    /**
     * Crea múltiples tamaños de una imagen (thumbnails)
     */
    public function createThumbnails(string $imagePath): array
    {
        $fullPath = storage_path('app/public/' . $imagePath);
        $pathInfo = pathinfo($imagePath);
        
        $thumbnails = [];
        
        // Tamaños para thumbnails
        $sizes = [
            'thumb' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600]
        ];
        
        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailName = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
            $thumbnailPath = storage_path('app/public/' . $thumbnailName);
            
            $image = Image::make($fullPath);
            $image->fit($dimensions[0], $dimensions[1], function ($constraint) {
                $constraint->upsize();
            });
            
            $image->save($thumbnailPath, 80);
            
            // Optimizar thumbnail
            $this->optimizerChain->optimize($thumbnailPath);
            
            $thumbnails[$sizeName] = $thumbnailName;
        }
        
        return $thumbnails;
    }
    
    /**
     * Elimina imagen y sus thumbnails
     */
    public function deleteProductImage(string $imagePath): bool
    {
        $pathInfo = pathinfo($imagePath);
        
        // Eliminar imagen original
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        // Eliminar thumbnails
        $sizes = ['thumb', 'medium', 'large'];
        foreach ($sizes as $size) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }
        
        return true;
    }
    
    /**
     * Optimiza imagen existente
     */
    public function optimizeExistingImage(string $imagePath): bool
    {
        $fullPath = storage_path('app/public/' . $imagePath);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        try {
            $this->optimizerChain->optimize($fullPath);
            return true;
        } catch (\Exception $e) {
            \Log::error('Error optimizando imagen: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el tamaño de archivo en formato legible
     */
    public function getFileSize(string $imagePath): string
    {
        $fullPath = storage_path('app/public/' . $imagePath);
        
        if (!file_exists($fullPath)) {
            return '0 B';
        }
        
        $bytes = filesize($fullPath);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Crear imagen WebP optimizada
     */
    public function createWebPVersion($imagePath)
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }

            $pathInfo = pathinfo($fullPath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            
            // Crear imagen desde el archivo original
            $image = null;
            $mimeType = mime_content_type($fullPath);
            
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($fullPath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($fullPath);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }
            
            // Convertir a WebP con calidad 80
            $success = imagewebp($image, $webpPath, 80);
            imagedestroy($image);
            
            if ($success) {
                Log::info("Versión WebP creada: {$webpPath}");
                return str_replace(storage_path('app/public/'), '', $webpPath);
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error creando versión WebP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimizar imagen con múltiples formatos
     */
    public function optimizeWithMultipleFormats($imagePath)
    {
        try {
            $results = [
                'original' => $this->optimizeExistingImage($imagePath),
                'webp' => $this->createWebPVersion($imagePath),
                'thumbnails' => $this->createThumbnails($imagePath)
            ];
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error("Error en optimización múltiple: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de una imagen
     */
    public function getImageStats($imagePath)
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            $imageInfo = getimagesize($fullPath);
            $fileSize = filesize($fullPath);
            
            return [
                'path' => $imagePath,
                'size' => $this->formatBytes($fileSize),
                'size_bytes' => $fileSize,
                'width' => $imageInfo[0] ?? 0,
                'height' => $imageInfo[1] ?? 0,
                'mime_type' => $imageInfo['mime'] ?? 'unknown',
                'aspect_ratio' => $imageInfo[0] && $imageInfo[1] ? 
                    round($imageInfo[0] / $imageInfo[1], 2) : 0,
                'is_optimized' => $fileSize < 500000, // Menos de 500KB se considera optimizado
                'created_at' => date('Y-m-d H:i:s', filectime($fullPath)),
                'modified_at' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas de imagen: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Validar si una imagen necesita optimización
     */
    public function needsOptimization($imagePath, $maxSizeKB = 500)
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            $fileSize = filesize($fullPath);
            $maxSizeBytes = $maxSizeKB * 1024;
            
            return $fileSize > $maxSizeBytes;
            
        } catch (\Exception $e) {
            Log::error("Error validando necesidad de optimización: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear imagen responsive con múltiples tamaños
     */
    public function createResponsiveImages($imagePath)
    {
        try {
            $sizes = [
                'xs' => 320,   // Mobile
                'sm' => 576,   // Small devices
                'md' => 768,   // Medium devices
                'lg' => 992,   // Large devices
                'xl' => 1200   // Extra large devices
            ];
            
            $responsiveImages = [];
            
            foreach ($sizes as $breakpoint => $width) {
                $responsiveImage = $this->resizeImage($imagePath, $width, null, $breakpoint);
                if ($responsiveImage) {
                    $responsiveImages[$breakpoint] = $responsiveImage;
                }
            }
            
            return $responsiveImages;
            
        } catch (\Exception $e) {
            Log::error("Error creando imágenes responsive: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Redimensionar imagen manteniendo proporción
     */
    private function resizeImage($imagePath, $newWidth, $newHeight = null, $suffix = '')
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            $imageInfo = getimagesize($fullPath);
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            
            // Calcular nueva altura manteniendo proporción
            if ($newHeight === null) {
                $newHeight = ($originalHeight * $newWidth) / $originalWidth;
            }
            
            // Crear imagen desde el archivo original
            $originalImage = null;
            $mimeType = $imageInfo['mime'];
            
            switch ($mimeType) {
                case 'image/jpeg':
                    $originalImage = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $originalImage = imagecreatefrompng($fullPath);
                    break;
                case 'image/gif':
                    $originalImage = imagecreatefromgif($fullPath);
                    break;
                default:
                    return false;
            }
            
            if (!$originalImage) {
                return false;
            }
            
            // Crear nueva imagen redimensionada
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }
            
            // Redimensionar
            imagecopyresampled(
                $resizedImage, $originalImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Generar nombre del archivo redimensionado
            $pathInfo = pathinfo($fullPath);
            $newFileName = $pathInfo['filename'] . ($suffix ? "_{$suffix}" : "_resized") . '.' . $pathInfo['extension'];
            $newPath = $pathInfo['dirname'] . '/' . $newFileName;
            
            // Guardar imagen redimensionada
            $success = false;
            switch ($mimeType) {
                case 'image/jpeg':
                    $success = imagejpeg($resizedImage, $newPath, 85);
                    break;
                case 'image/png':
                    $success = imagepng($resizedImage, $newPath, 6);
                    break;
                case 'image/gif':
                    $success = imagegif($resizedImage, $newPath);
                    break;
            }
            
            // Limpiar memoria
            imagedestroy($originalImage);
            imagedestroy($resizedImage);
            
            if ($success) {
                return str_replace(storage_path('app/public/'), '', $newPath);
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error redimensionando imagen: " . $e->getMessage());
            return false;
        }
    }
}