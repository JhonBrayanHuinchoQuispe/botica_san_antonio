<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Producto;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiringProducts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando verificación de productos próximos a vencer');
        
        $this->checkExpiringProducts();
        $this->checkExpiredProducts();
        $this->checkCriticalStock();
        
        Log::info('Verificación de productos completada');
    }
    
    /**
     * Verificar productos próximos a vencer (1-30 días)
     */
    private function checkExpiringProducts(): void
    {
        $fechaLimite = now()->addDays(30);
        $fechaMinima = now()->addDay();
        
        $productos = Producto::whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [$fechaMinima, $fechaLimite])
            ->where('stock_actual', '>', 0)
            ->get();
            
        foreach ($productos as $producto) {
            $diasRestantes = now()->diffInDays(Carbon::parse($producto->fecha_vencimiento), false);
            
            // Verificar si ya existe una notificación reciente
            $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();
                
            if (!$existeNotificacion) {
                Notification::createProximoVencer(1, $producto, $diasRestantes);
                Log::info("Notificación creada: {$producto->nombre} vence en {$diasRestantes} días");
            }
        }
    }
    
    /**
     * Verificar productos ya vencidos
     */
    private function checkExpiredProducts(): void
    {
        $productos = Producto::whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now())
            ->where('stock_actual', '>', 0)
            ->get();
            
        foreach ($productos as $producto) {
            // Verificar si ya existe una notificación reciente
            $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIDO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();
                
            if (!$existeNotificacion) {
                Notification::createProductoVencido(1, $producto);
                Log::info("Notificación creada: {$producto->nombre} está vencido");
            }
        }
    }
    
    /**
     * Verificar productos con stock crítico
     */
    private function checkCriticalStock(): void
    {
        $productos = Producto::whereColumn('stock_actual', '<', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->get();
            
        foreach ($productos as $producto) {
            // Verificar si ya existe una notificación reciente
            $existeNotificacion = Notification::where('type', Notification::TYPE_STOCK_CRITICO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();
                
            if (!$existeNotificacion) {
                Notification::createStockCritico(1, $producto);
                Log::info("Notificación creada: {$producto->nombre} tiene stock crítico");
            }
        }
    }
}
