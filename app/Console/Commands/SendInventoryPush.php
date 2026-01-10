<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Console\Command;

class SendInventoryPush extends Command
{
    protected $signature = 'inventory:push-alerts {--dry-run}';
    protected $description = 'Envía notificaciones push por categoría: bajo stock, por vencer, vencido y agotado';

    public function handle(WebPushService $push): int
    {
        $this->info('Analizando inventario para alertas por categoría...');

        // Ajusta estos umbrales según tu negocio
        $stockThreshold = config('sistema.alertas.stock_minimo_global_default', 10);
        $daysToExpire = config('sistema.alertas.dias_anticipacion_vencimiento_default', 30);

        $lowStock = Producto::query()
            ->where(function($q) use ($stockThreshold){
                $q->whereColumn('stock_actual', '<=', 'stock_minimo')
                  ->orWhere('stock_actual', '<=', $stockThreshold);
            })
            ->orderBy('stock_actual')
            ->limit(50)
            ->get(['id','nombre','stock_actual','stock_minimo']);

        $expiredSoon = Producto::query()
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', now()->addDays($daysToExpire))
            ->whereDate('fecha_vencimiento', '>', now())
            ->orderBy('fecha_vencimiento')
            ->limit(50)
            ->get(['id','nombre','fecha_vencimiento']);

        $expired = Producto::query()
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', now())
            ->orderBy('fecha_vencimiento')
            ->limit(50)
            ->get(['id','nombre','fecha_vencimiento']);

        $outOfStock = Producto::query()
            ->where(function($q){
                $q->where('stock_actual', '<=', 0);
            })
            ->orderBy('stock_actual')
            ->limit(50)
            ->get(['id','nombre','stock_actual']);

        $summary = [
            'low_stock_count' => $lowStock->count(),
            'expiring_count' => $expiredSoon->count(),
            'expired_count' => $expired->count(),
            'out_of_stock_count' => $outOfStock->count(),
        ];

        $this->line('Bajo stock: '.$summary['low_stock_count'].' | Por vencer: '.$summary['expiring_count'].' | Vencidos: '.$summary['expired_count'].' | Agotados: '.$summary['out_of_stock_count']);

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se envían notificaciones.');
            return self::SUCCESS;
        }

        // Helper para formatear listado breve de productos
        $formatList = function($collection, $field = 'nombre', $max = 5) {
            $names = $collection->pluck($field)->take($max)->all();
            $extra = max($collection->count() - count($names), 0);
            $suffix = $extra > 0 ? "… y {$extra} más" : '';
            return (count($names) ? implode(', ', $names) : 'Sin productos') . ($suffix ? ' ' . $suffix : '');
        };

        // Envía a todos los usuarios con suscripción registrada
        $users = User::has('pushSubscriptions')->get(['id']);
        foreach ($users as $user) {
            // Bajo stock
            if ($summary['low_stock_count'] > 0) {
                $push->sendToUser($user->id, 'Bajo stock', $formatList($lowStock), [
                    'type' => 'low_stock',
                    'url' => url('/inventario/productos?estado=bajo_stock'),
                ]);
            }

            // Por vencer
            if ($summary['expiring_count'] > 0) {
                $push->sendToUser($user->id, 'Productos por vencer', $formatList($expiredSoon), [
                    'type' => 'expiring_soon',
                    'url' => url('/inventario/productos?estado=por_vencer'),
                ]);
            }

            // Vencidos
            if ($summary['expired_count'] > 0) {
                $push->sendToUser($user->id, 'Productos vencidos', $formatList($expired), [
                    'type' => 'expired',
                    'url' => url('/inventario/productos?estado=vencido'),
                ]);
            }

            // Agotados
            if ($summary['out_of_stock_count'] > 0) {
                $push->sendToUser($user->id, 'Productos agotados', $formatList($outOfStock), [
                    'type' => 'out_of_stock',
                    'url' => url('/inventario/productos?estado=agotado'),
                ]);
            }
        }

        $this->info('Notificaciones enviadas.');
        return self::SUCCESS;
    }
}