<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LimpiarImagenesFaltantes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productos:limpiar-imagenes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las referencias a imágenes de productos que no existen físicamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de imágenes faltantes...');
        
        $productosConImagen = Producto::whereNotNull('imagen')->where('imagen', '!=', '')->get();
        $imagenesLimpiadas = 0;
        $errores = 0;

        $this->info("📊 Encontrados {$productosConImagen->count()} productos con imágenes referenciadas");

        foreach ($productosConImagen as $producto) {
            $rutaCompleta = storage_path('app/public/' . $producto->imagen);
            
            if (!file_exists($rutaCompleta)) {
                try {
                    $this->warn("❌ Imagen faltante: {$producto->imagen} (Producto: {$producto->nombre})");
                    
                    // Limpiar la referencia en la base de datos
                    $producto->imagen = null;
                    $producto->save();
                    
                    $imagenesLimpiadas++;
                    
                } catch (\Exception $e) {
                    $this->error("❌ Error limpiando producto {$producto->id}: " . $e->getMessage());
                    Log::error("Error limpiando imagen de producto {$producto->id}: " . $e->getMessage());
                    $errores++;
                }
            } else {
                $this->line("✅ Imagen válida: {$producto->imagen}");
            }
        }

        $this->newLine();
        $this->info("🎉 Limpieza completada:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Productos verificados', $productosConImagen->count()],
                ['Referencias limpiadas', $imagenesLimpiadas],
                ['Errores', $errores],
            ]
        );

        if ($imagenesLimpiadas > 0) {
            $this->info("✨ Se limpiaron {$imagenesLimpiadas} referencias a imágenes faltantes");
        } else {
            $this->info("🎯 No se encontraron referencias a imágenes faltantes");
        }

        return Command::SUCCESS;
    }
}
