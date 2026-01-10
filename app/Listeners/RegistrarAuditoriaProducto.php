<?php

namespace App\Listeners;

use App\Events\ProductoActualizado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarAuditoriaProducto
{
    /**
     * Handle the event.
     */
    public function handle(ProductoActualizado $event): void
    {
        try {
            // Registrar en tabla de auditoría
            DB::table('auditoria_productos')->insert([
                'producto_id' => $event->producto->id,
                'usuario_id' => $event->usuario->id,
                'accion' => $event->accion,
                'datos_anteriores' => json_encode($event->cambios['anterior'] ?? []),
                'datos_nuevos' => json_encode($event->cambios['nuevo'] ?? []),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Log para seguimiento
            Log::info("Producto {$event->accion}", [
                'producto_id' => $event->producto->id,
                'producto_nombre' => $event->producto->nombre,
                'usuario_id' => $event->usuario->id,
                'usuario_nombre' => $event->usuario->name,
                'cambios' => $event->cambios,
                'ip' => request()->ip()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error registrando auditoría de producto: " . $e->getMessage(), [
                'producto_id' => $event->producto->id,
                'usuario_id' => $event->usuario->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}