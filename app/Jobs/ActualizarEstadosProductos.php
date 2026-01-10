<?php

namespace App\Jobs;

use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ActualizarEstadosProductos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Iniciando actualización masiva de estados de productos');
            
            $ahora = Carbon::now();
            $productosActualizados = 0;
            
            // Procesar en lotes para evitar problemas de memoria
            Producto::chunk(100, function ($productos) use ($ahora, &$productosActualizados) {
                foreach ($productos as $producto) {
                    $estadoAnterior = $producto->estado;
                    $nuevoEstado = $this->calcularEstado($producto, $ahora);
                    
                    if ($estadoAnterior !== $nuevoEstado) {
                        $producto->update(['estado' => $nuevoEstado]);
                        $productosActualizados++;
                        
                        Log::debug("Producto {$producto->id} actualizado de '{$estadoAnterior}' a '{$nuevoEstado}'");
                    }
                }
            });
            
            // Limpiar cache relacionado
            $this->limpiarCache();
            
            Log::info("Actualización de estados completada. {$productosActualizados} productos actualizados.");
            
        } catch (\Exception $e) {
            Log::error('Error en actualización de estados de productos: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Calcular el estado de un producto
     */
    private function calcularEstado(Producto $producto, Carbon $ahora): string
    {
        $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
        
        // Producto vencido
        if ($fechaVencimiento->isPast()) {
            return 'Vencido';
        }
        
        // Producto por vencer (30 días)
        if ($fechaVencimiento->diffInDays($ahora) <= 30) {
            return 'Por vencer';
        }
        
        // Producto con bajo stock
        if ($producto->stock_actual <= $producto->stock_minimo) {
            return 'Bajo stock';
        }
        
        // Producto normal
        return 'Normal';
    }
    
    /**
     * Limpiar cache relacionado con productos
     */
    private function limpiarCache(): void
    {
        $cacheKeys = [
            'productos_bajo_stock',
            'productos_vencer_30',
            'productos_vencer_15',
            'productos_vencer_7',
            'estadisticas_productos'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Limpiar cache de categorías
        $categorias = Producto::distinct('categoria')->pluck('categoria');
        foreach ($categorias as $categoria) {
            Cache::forget('productos_categoria_' . $categoria);
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job ActualizarEstadosProductos falló: ' . $exception->getMessage(), [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}