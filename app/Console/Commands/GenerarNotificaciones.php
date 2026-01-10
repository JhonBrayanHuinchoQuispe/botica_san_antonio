<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\ProductoUbicacion;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class GenerarNotificaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notificaciones:generar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar notificaciones para productos con stock bajo, agotados, próximos a vencer y vencidos basándose en lotes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Generando notificaciones basadas en lotes...');
        
        $notificacionesCreadas = 0;

        $userIds = User::query()->pluck('id');
        if ($userIds->isEmpty()) {
            $this->warn('⚠️ No se encontraron usuarios. No se pueden generar notificaciones.');
            return 0;
        }
        
        // 1. PRODUCTOS AGOTADOS (stock_actual <= 0)
        $this->info("\n📦 Verificando productos agotados...");
        $productosAgotados = Producto::where('stock_actual', '<=', 0)->get();
        
        foreach ($productosAgotados as $producto) {
            foreach ($userIds as $userId) {
                $existeNotificacion = Notification::where('type', Notification::TYPE_STOCK_AGOTADO)
                    ->where('user_id', $userId)
                    ->where('data->producto_id', $producto->id)
                    ->whereNull('read_at')
                    ->exists();
                    
                if (!$existeNotificacion) {
                    Notification::createStockAgotado($userId, $producto);
                    $this->info("  ✓ {$producto->nombre} - AGOTADO (user_id={$userId})");
                    $notificacionesCreadas++;
                }
            }
        }
        $this->info("  Total agotados: {$productosAgotados->count()}");
        
        // 2. PRODUCTOS CON STOCK BAJO (stock_actual <= stock_minimo pero > 0)
        $this->info("\n⚠️  Verificando productos con stock bajo...");
        $productosStockBajo = Producto::whereRaw('stock_actual <= stock_minimo')
            ->where('stock_actual', '>', 0)
            ->get();
        
        foreach ($productosStockBajo as $producto) {
            foreach ($userIds as $userId) {
                $existeNotificacion = Notification::where('type', Notification::TYPE_STOCK_CRITICO)
                    ->where('user_id', $userId)
                    ->where('data->producto_id', $producto->id)
                    ->whereNull('read_at')
                    ->exists();
                    
                if (!$existeNotificacion) {
                    Notification::createStockCritico($userId, $producto);
                    $this->info("  ✓ {$producto->nombre} - Stock: {$producto->stock_actual}/{$producto->stock_minimo} (user_id={$userId})");
                    $notificacionesCreadas++;
                }
            }
        }
        $this->info("  Total con stock bajo: {$productosStockBajo->count()}");
        
        // 3. LOTES VENCIDOS (fecha_vencimiento < hoy y cantidad > 0)
        $this->info("\n🔴 Verificando lotes vencidos...");
        $lotesVencidos = ProductoUbicacion::with('producto')
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', now())
            ->where('cantidad', '>', 0)
            ->get();
        
        $productosVencidosIds = [];
        foreach ($lotesVencidos as $lote) {
            if (!$lote->producto) continue;
            
            $productoId = $lote->producto->id;
            if (in_array($productoId, $productosVencidosIds)) continue;
            
            $productosVencidosIds[] = $productoId;

            foreach ($userIds as $userId) {
                $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIDO)
                    ->where('user_id', $userId)
                    ->where('data->producto_id', $productoId)
                    ->whereNull('read_at')
                    ->exists();
                    
                if (!$existeNotificacion) {
                    Notification::createProductoVencido($userId, $lote->producto);
                    $diasVencido = abs(now()->diffInDays($lote->fecha_vencimiento, false));
                    $this->info("  ✓ {$lote->producto->nombre} - Lote: {$lote->lote} - Venció hace {$diasVencido} días (user_id={$userId})");
                    $notificacionesCreadas++;
                }
            }
        }
        $this->info("  Total productos con lotes vencidos: " . count($productosVencidosIds));
        
        // 4. LOTES PRÓXIMOS A VENCER (fecha_vencimiento entre hoy y 90 días, cantidad > 0)
        $this->info("\n🟡 Verificando lotes próximos a vencer (90 días)...");
        $lotesProximosVencer = ProductoUbicacion::with('producto')
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>', now())
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(90))
            ->where('cantidad', '>', 0)
            ->get();
        
        $productosProximosIds = [];
        foreach ($lotesProximosVencer as $lote) {
            if (!$lote->producto) continue;
            
            $productoId = $lote->producto->id;
            if (in_array($productoId, $productosProximosIds)) continue;
            
            $productosProximosIds[] = $productoId;

            foreach ($userIds as $userId) {
                $existeNotificacion = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                    ->where('user_id', $userId)
                    ->where('data->producto_id', $productoId)
                    ->whereNull('read_at')
                    ->exists();
                    
                if (!$existeNotificacion) {
                    $diasRestantes = now()->diffInDays($lote->fecha_vencimiento, false);
                    Notification::createProximoVencer($userId, $lote->producto, $diasRestantes);
                    $this->info("  ✓ {$lote->producto->nombre} - Lote: {$lote->lote} - Vence en {$diasRestantes} días (user_id={$userId})");
                    $notificacionesCreadas++;
                }
            }
        }
        $this->info("  Total productos con lotes próximos a vencer: " . count($productosProximosIds));
        
        // RESUMEN
        $totalNotificaciones = Notification::whereNull('read_at')->count();
        $this->info("\n" . str_repeat('=', 60));
        $this->info("✅ Proceso completado");
        $this->info("📊 Notificaciones creadas en esta ejecución: {$notificacionesCreadas}");
        $this->info("📬 Total de notificaciones activas (no leídas): {$totalNotificaciones}");
        $this->info(str_repeat('=', 60));
        
        return 0;
    }
}