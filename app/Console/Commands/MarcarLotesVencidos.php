<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LoteService;
use Illuminate\Support\Facades\Log;

class MarcarLotesVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lotes:marcar-vencidos {--dry-run : Solo mostrar qué lotes se marcarían como vencidos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca automáticamente los lotes vencidos y actualiza el stock de productos';

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
        $this->info('🔍 Iniciando proceso de marcado de lotes vencidos...');
        
        try {
            $dryRun = $this->option('dry-run');
            
            if ($dryRun) {
                $this->warn('⚠️  MODO DRY-RUN: Solo se mostrarán los cambios, no se aplicarán');
            }
            
            // Obtener lotes vencidos antes del proceso
            $lotesVencidos = $this->loteService->marcarLotesVencidos($dryRun);
            
            if (empty($lotesVencidos)) {
                $this->info('✅ No se encontraron lotes vencidos para procesar');
                return 0;
            }
            
            $this->info("📦 Se encontraron " . count($lotesVencidos) . " lotes vencidos:");
            
            // Mostrar tabla con los lotes vencidos
            $headers = ['ID', 'Producto', 'Lote', 'Fecha Vencimiento', 'Cantidad', 'Estado Anterior'];
            $rows = [];
            
            foreach ($lotesVencidos as $lote) {
                $rows[] = [
                    $lote['id'],
                    $lote['producto_nombre'],
                    $lote['lote'],
                    $lote['fecha_vencimiento'],
                    $lote['cantidad'],
                    $lote['estado_anterior']
                ];
            }
            
            $this->table($headers, $rows);
            
            if (!$dryRun) {
                $this->info('✅ Lotes marcados como vencidos exitosamente');
                $this->info('📊 Stock de productos actualizado automáticamente');
                
                // Log del proceso
                Log::info('Comando MarcarLotesVencidos ejecutado', [
                    'lotes_procesados' => count($lotesVencidos),
                    'lotes_ids' => collect($lotesVencidos)->pluck('id')->toArray()
                ]);
            } else {
                $this->info('💡 Para aplicar estos cambios, ejecute el comando sin --dry-run');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error al procesar lotes vencidos: ' . $e->getMessage());
            
            Log::error('Error en comando MarcarLotesVencidos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return 1;
        }
    }
}