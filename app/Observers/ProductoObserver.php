<?php

namespace App\Observers;

use App\Models\Producto;
use App\Models\Notification;
use Carbon\Carbon;

class ProductoObserver
{
    /**
     * Handle the Producto "created" event.
     */
    public function created(Producto $producto): void
    {
        $this->checkStockCritico($producto);
        $this->checkStockAgotado($producto);
        $this->checkProximoVencer($producto);
    }

    /**
     * Handle the Producto "updated" event.
     */
    public function updated(Producto $producto): void
    {
        // Comentado para evitar doble llamada - se llama desde CompraController
        // if ($producto->wasChanged(['stock_actual', 'fecha_vencimiento'])) {
        //     $producto->recalcularEstado();
        // }
        
        // Solo verificar si cambió el stock
        if ($producto->wasChanged('stock_actual')) {
            $this->checkStockCritico($producto);
            $this->checkStockAgotado($producto);
        }
        
        // Solo verificar si cambió la fecha de vencimiento
        if ($producto->wasChanged('fecha_vencimiento')) {
            $this->checkProximoVencer($producto);
        }
    }

    /**
     * Handle the Producto "deleted" event.
     */
    public function deleted(Producto $producto): void
    {
        // Eliminar notificaciones relacionadas con este producto
        Notification::where('data->producto_id', $producto->id)->delete();
    }

    /**
     * Verificar si el stock está crítico
     */
    private function checkStockCritico(Producto $producto): void
    {
        if ($producto->stock_actual < $producto->stock_minimo && $producto->stock_actual > 0) {
            // Verificar si ya existe una notificación similar reciente (últimas 24 horas)
            $existeNotificacion = Notification::where('type', Notification::TYPE_STOCK_CRITICO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existeNotificacion) {
                Notification::createStockCritico(1, $producto); // userId = 1 por defecto
            }
        } else {
            // Si el stock ya no está crítico, eliminar notificaciones de stock crítico
            Notification::where('type', Notification::TYPE_STOCK_CRITICO)
                ->where('data->producto_id', $producto->id)
                ->delete();
        }
    }

    /**
     * Verificar si el producto está agotado
     */
    private function checkStockAgotado(Producto $producto): void
    {
        if ($producto->stock_actual <= 0) {
            // Verificar si ya existe una notificación similar reciente (últimas 24 horas)
            $existeNotificacion = Notification::where('type', Notification::TYPE_STOCK_AGOTADO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existeNotificacion) {
                Notification::createStockAgotado(1, $producto); // userId = 1 por defecto
            }
        } else {
            // Si el stock ya no está agotado, eliminar notificaciones de stock agotado
            Notification::where('type', Notification::TYPE_STOCK_AGOTADO)
                ->where('data->producto_id', $producto->id)
                ->delete();
        }
    }

    /**
     * Verificar si el producto está próximo a vencer
     */
    private function checkProximoVencer(Producto $producto): void
    {
        if ($producto->fecha_vencimiento) {
            $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento);
            $diasRestantes = now()->diffInDays($fechaVencimiento, false);

            // Notificar si está próximo a vencer (entre 0 y 30 días) - incluye el día de vencimiento
            if ($diasRestantes >= 0 && $diasRestantes <= 30) {
                // Verificar si ya existe una notificación similar reciente
                $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                    ->where('data->producto_id', $producto->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->exists();

                if (!$existeNotificacion) {
                    Notification::createProximoVencer(1, $producto, $diasRestantes);
                }
            }
            // Eliminar notificaciones de próximo a vencer si ya no aplica
            elseif ($diasRestantes > 30) {
                Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                    ->where('data->producto_id', $producto->id)
                    ->delete();
            }
            
            // Notificar si ya está vencido (días negativos)
            if ($diasRestantes < 0) {
                $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIDO)
                    ->where('data->producto_id', $producto->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->exists();

                if (!$existeNotificacion) {
                    Notification::createProductoVencido(1, $producto);
                }
                
                // Eliminar notificaciones de próximo a vencer si ya está vencido
                Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                    ->where('data->producto_id', $producto->id)
                    ->delete();
            }
            // Eliminar notificaciones de vencido si ya no está vencido
            else {
                Notification::where('type', Notification::TYPE_PRODUCTO_VENCIDO)
                    ->where('data->producto_id', $producto->id)
                    ->delete();
            }
        }
    }

    /**
     * Handle the Producto "restored" event.
     */
    public function restored(Producto $producto): void
    {
        //
    }

    /**
     * Handle the Producto "force deleted" event.
     */
    public function forceDeleted(Producto $producto): void
    {
        //
    }
}
