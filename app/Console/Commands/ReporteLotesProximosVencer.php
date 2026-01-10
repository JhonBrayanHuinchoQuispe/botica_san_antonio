<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LoteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReporteLotesProximosVencer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lotes:reporte-proximos-vencer {--dias=30 : Días de anticipación para considerar próximo a vencer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un reporte de lotes próximos a vencer';

    /**
     * @var LoteService
     */
    protected $loteService;

    /**
     * Create a new command instance.
     */
    public function __construct(LoteService $loteService)
    {
        parent::__construct();
        $this->loteService = $loteService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = (int) $this->option('dias');
        
        $this->info("🔍 Generando reporte de lotes próximos a vencer en {$dias} días...");
        
        try {
            $lotesProximos = $this->loteService->obtenerProximosAVencer($dias);
            
            if (empty($lotesProximos)) {
                $this->info("✅ No se encontraron lotes próximos a vencer en los próximos {$dias} días");
                return 0;
            }
            
            $this->info("⚠️  Se encontraron " . count($lotesProximos) . " lotes próximos a vencer:");
            
            // Agrupar por urgencia
            $urgente = collect($lotesProximos)->filter(function($lote) {
                return $lote['dias_para_vencer'] <= 7;
            });
            
            $moderado = collect($lotesProximos)->filter(function($lote) {
                return $lote['dias_para_vencer'] > 7 && $lote['dias_para_vencer'] <= 15;
            });
            
            $normal = collect($lotesProximos)->filter(function($lote) {
                return $lote['dias_para_vencer'] > 15;
            });
            
            // Mostrar lotes urgentes (≤ 7 días)
            if ($urgente->count() > 0) {
                $this->error("🚨 URGENTE - Vencen en 7 días o menos ({$urgente->count()} lotes):");
                $this->mostrarTablaLotes($urgente->toArray());
                $this->newLine();
            }
            
            // Mostrar lotes moderados (8-15 días)
            if ($moderado->count() > 0) {
                $this->warn("⚠️  MODERADO - Vencen en 8-15 días ({$moderado->count()} lotes):");
                $this->mostrarTablaLotes($moderado->toArray());
                $this->newLine();
            }
            
            // Mostrar lotes normales (>15 días)
            if ($normal->count() > 0) {
                $this->info("ℹ️  NORMAL - Vencen en más de 15 días ({$normal->count()} lotes):");
                $this->mostrarTablaLotes($normal->toArray());
                $this->newLine();
            }
            
            // Resumen
            $valorTotal = collect($lotesProximos)->sum(function($lote) {
                return $lote['cantidad'] * $lote['precio_venta_lote'];
            });
            
            $cantidadTotal = collect($lotesProximos)->sum('cantidad');
            
            $this->info("📊 RESUMEN:");
            $this->info("   • Total de lotes: " . count($lotesProximos));
            $this->info("   • Cantidad total: {$cantidadTotal} unidades");
            $this->info("   • Valor total estimado: S/. " . number_format($valorTotal, 2));
            $this->info("   • Urgentes (≤7 días): {$urgente->count()} lotes");
            $this->info("   • Moderados (8-15 días): {$moderado->count()} lotes");
            $this->info("   • Normales (>15 días): {$normal->count()} lotes");
            
            // Log del reporte
            Log::info('Reporte de lotes próximos a vencer generado', [
                'dias_anticipacion' => $dias,
                'total_lotes' => count($lotesProximos),
                'urgentes' => $urgente->count(),
                'moderados' => $moderado->count(),
                'normales' => $normal->count(),
                'valor_total' => $valorTotal
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error al generar reporte: ' . $e->getMessage());
            
            Log::error('Error en comando ReporteLotesProximosVencer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Mostrar tabla de lotes
     */
    private function mostrarTablaLotes(array $lotes)
    {
        $headers = ['Producto', 'Lote', 'Cantidad', 'Vence', 'Días', 'Precio Unit.', 'Valor Total'];
        $rows = [];
        
        foreach ($lotes as $lote) {
            $valorTotal = $lote['cantidad'] * $lote['precio_venta_lote'];
            
            $rows[] = [
                $lote['producto_nombre'],
                $lote['lote'],
                $lote['cantidad'],
                Carbon::parse($lote['fecha_vencimiento'])->format('d/m/Y'),
                $lote['dias_para_vencer'],
                'S/. ' . number_format($lote['precio_venta_lote'], 2),
                'S/. ' . number_format($valorTotal, 2)
            ];
        }
        
        $this->table($headers, $rows);
    }
}