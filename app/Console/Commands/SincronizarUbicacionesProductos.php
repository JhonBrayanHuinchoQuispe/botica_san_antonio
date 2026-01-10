<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class SincronizarUbicacionesProductos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productos:sincronizar-ubicaciones {--dry-run : Mostrar cambios sin aplicarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el campo ubicacion_almacen con las ubicaciones reales de los productos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de ubicaciones de productos...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Los cambios se mostrarán pero no se aplicarán');
            $this->newLine();
        }

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            // Paso 1: Productos sin ubicacion_almacen pero con ubicaciones reales
            $this->info('📍 Paso 1: Actualizando productos sin ubicacion_almacen...');
            $actualizados = $this->actualizarProductosSinUbicacion($dryRun);

            // Paso 2: Verificar productos con ubicacion_almacen existente
            $this->info('🔍 Paso 2: Verificando productos con ubicacion_almacen existente...');
            $corregidos = $this->verificarProductosConUbicacion($dryRun);

            if (!$dryRun) {
                DB::commit();
            }

            $this->newLine();
            $this->info('✅ Sincronización completada exitosamente');
            $this->table(
                ['Tipo', 'Cantidad'],
                [
                    ['Productos nuevos actualizados', $actualizados],
                    ['Productos existentes corregidos', $corregidos],
                    ['Total de cambios', $actualizados + $corregidos]
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function actualizarProductosSinUbicacion($dryRun = false)
    {
        $productos = Producto::whereNull('ubicacion_almacen')
            ->whereHas('ubicaciones', function($query) {
                $query->where('cantidad', '>', 0);
            })
            ->with(['ubicaciones.ubicacion.estante'])
            ->get();

        $this->info("   Productos encontrados: {$productos->count()}");
        
        if ($productos->count() === 0) {
            return 0;
        }

        $actualizados = 0;
        $progressBar = $this->output->createProgressBar($productos->count());
        $progressBar->start();

        foreach ($productos as $producto) {
            $ubicacionesConStock = $producto->ubicaciones->where('cantidad', '>', 0);
            
            if ($ubicacionesConStock->count() > 0) {
                // Agrupar por ubicación física real
                $ubicacionesAgrupadas = $ubicacionesConStock->groupBy(function ($ubicacion) {
                    $estante = $ubicacion->ubicacion?->estante;
                    return ($estante?->nombre ?? 'Sin asignar') . ' - ' . ($ubicacion->ubicacion?->codigo ?? 'N/A');
                });
                
                $totalUbicaciones = $ubicacionesAgrupadas->count();
                
                if ($totalUbicaciones == 1) {
                    $ubicacionCompleta = $ubicacionesAgrupadas->keys()->first();
                } else {
                    $ubicacionCompleta = "Múltiples ubicaciones ({$totalUbicaciones})";
                }
                
                if (!$dryRun) {
                    $producto->update(['ubicacion_almacen' => $ubicacionCompleta]);
                }
                
                $actualizados++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        return $actualizados;
    }

    private function verificarProductosConUbicacion($dryRun = false)
    {
        $productos = Producto::whereNotNull('ubicacion_almacen')
            ->with(['ubicaciones.ubicacion.estante'])
            ->get();

        $this->info("   Productos a verificar: {$productos->count()}");
        
        if ($productos->count() === 0) {
            return 0;
        }

        $corregidos = 0;
        $progressBar = $this->output->createProgressBar($productos->count());
        $progressBar->start();

        foreach ($productos as $producto) {
            $ubicacionesConStock = $producto->ubicaciones->where('cantidad', '>', 0);
            
            if ($ubicacionesConStock->count() == 0) {
                // No tiene ubicaciones con stock, limpiar
                if (!$dryRun) {
                    $producto->update(['ubicacion_almacen' => null]);
                }
                $corregidos++;
            } else {
                // Verificar si la ubicación actual es correcta
                $ubicacionesAgrupadas = $ubicacionesConStock->groupBy(function ($ubicacion) {
                    $estante = $ubicacion->ubicacion?->estante;
                    return ($estante?->nombre ?? 'Sin asignar') . ' - ' . ($ubicacion->ubicacion?->codigo ?? 'N/A');
                });
                
                $totalUbicaciones = $ubicacionesAgrupadas->count();
                
                if ($totalUbicaciones == 1) {
                    $ubicacionCorrecta = $ubicacionesAgrupadas->keys()->first();
                } else {
                    $ubicacionCorrecta = "Múltiples ubicaciones ({$totalUbicaciones})";
                }
                
                if ($producto->ubicacion_almacen !== $ubicacionCorrecta) {
                    if (!$dryRun) {
                        $producto->update(['ubicacion_almacen' => $ubicacionCorrecta]);
                    }
                    $corregidos++;
                }
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        return $corregidos;
    }
}
