<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Models\Producto;
use Carbon\Carbon;

class NormalizeNotificationMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normaliza los mensajes de notificaciones para evitar días decimales y corregir singular/plural';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Normalizando mensajes de notificaciones...');

        $updated = 0;

        $notifications = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($notifications as $notification) {
            $data = is_array($notification->data) ? $notification->data : [];

            $productoNombre = null;
            if (isset($data['producto_id'])) {
                $producto = Producto::find($data['producto_id']);
                if ($producto) {
                    $productoNombre = $producto->nombre;
                }
            }

            // Intentar calcular días restantes de forma segura
            $diasRestantes = null;
            if (isset($data['dias_restantes']) && is_numeric($data['dias_restantes'])) {
                $diasRestantes = (int) round($data['dias_restantes']);
            } elseif (isset($data['fecha_vencimiento'])) {
                try {
                    $diasRestantes = now()->diffInDays(Carbon::parse($data['fecha_vencimiento']), false);
                } catch (\Throwable $e) {
                    $diasRestantes = null;
                }
            }

            if ($diasRestantes === null) {
                // Si no podemos calcular, saltar esta notificación
                continue;
            }

            if ($diasRestantes < 0) {
                // Si ya venció, opcionalmente podríamos cambiar el tipo; aquí solo omitimos
                continue;
            }

            $mensajeDias = $diasRestantes === 0
                ? 'vence hoy'
                : ($diasRestantes === 1 ? 'vence en 1 día' : "vence en {$diasRestantes} días");

            $nuevoMensaje = trim(($productoNombre ? $productoNombre : '') . ' ' . $mensajeDias);

            // Actualizar si cambió
            if ($notification->message !== $nuevoMensaje) {
                $notification->update([
                    'message' => $nuevoMensaje,
                    'data' => array_merge($data, ['dias_restantes' => $diasRestantes]),
                ]);
                $updated++;
            }
        }

        $this->info("✅ Normalización completada. Mensajes actualizados: {$updated}");
        return 0;
    }
}