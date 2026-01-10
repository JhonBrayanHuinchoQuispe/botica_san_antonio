<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;

class OptimizeProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize-products {--force : Forzar optimización de todas las imágenes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiza todas las imágenes de productos existentes';

    protected $imageOptimizer;

    public function __construct()
    {
        parent::__construct();
        $this->imageOptimizer = new ImageOptimizationService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando optimización de imágenes de productos...');
        
        $productos = Producto::whereNotNull('imagen')->get();
        $totalProductos = $productos->count();
        
        if ($totalProductos === 0) {
            $this->warn('No se encontraron productos con imágenes.');
            return 0;
        }
        
        $this->info("📊 Se encontraron {$totalProductos} productos con imágenes.");
        
        $bar = $this->output->createProgressBar($totalProductos);
        $bar->start();
        
        $optimizadas = 0;
        $errores = 0;
        $ahorroTotal = 0;
        
        foreach ($productos as $producto) {
            try {
                $imagePath = $producto->imagen;
                $fullPath = storage_path('app/public/' . $imagePath);
                
                if (!file_exists($fullPath)) {
                    $this->newLine();
                    $this->warn("⚠️  Imagen no encontrada: {$imagePath}");
                    $errores++;
                    $bar->advance();
                    continue;
                }
                
                // Obtener tamaño antes de optimizar
                $tamañoAntes = filesize($fullPath);
                
                // Optimizar imagen
                if ($this->imageOptimizer->optimizeExistingImage($imagePath)) {
                    $tamañoDespues = filesize($fullPath);
                    $ahorro = $tamañoAntes - $tamañoDespues;
                    $ahorroTotal += $ahorro;
                    
                    // Crear thumbnails si no existen
                    $this->imageOptimizer->createThumbnails($imagePath);
                    
                    $optimizadas++;
                } else {
                    $errores++;
                }
                
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error optimizando {$producto->nombre}: " . $e->getMessage());
                $errores++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Mostrar resumen
        $this->info('✅ Optimización completada!');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Imágenes optimizadas', $optimizadas],
                ['Errores', $errores],
                ['Ahorro total', $this->formatBytes($ahorroTotal)],
                ['Ahorro promedio', $optimizadas > 0 ? $this->formatBytes($ahorroTotal / $optimizadas) : '0 B']
            ]
        );
        
        return 0;
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
