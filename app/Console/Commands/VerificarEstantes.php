<?php

namespace App\Console\Commands;

use App\Models\Estante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarEstantes extends Command
{
    protected $signature = 'estantes:verificar';
    protected $description = 'Verificar estantes en la base de datos';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE ESTANTES ===');
        
        try {
            // Verificar si la tabla existe
            $tablaExiste = DB::getSchemaBuilder()->hasTable('estantes');
            $this->info("¿Tabla 'estantes' existe? " . ($tablaExiste ? 'SÍ' : 'NO'));
            
            if ($tablaExiste) {
                // Obtener todos los estantes
                $estantes = DB::table('estantes')->get();
                $this->info("Total de estantes: " . $estantes->count());
                
                foreach ($estantes as $estante) {
                    $this->line("- ID: {$estante->id} | Nombre: {$estante->nombre} | Niveles: {$estante->numero_niveles} | Posiciones: {$estante->numero_posiciones}");
                }
                
                // Verificar si hay ubicaciones creadas
                $ubicacionesExisten = DB::getSchemaBuilder()->hasTable('ubicaciones');
                $this->info("¿Tabla 'ubicaciones' existe? " . ($ubicacionesExisten ? 'SÍ' : 'NO'));
                
                if ($ubicacionesExisten) {
                    $totalUbicaciones = DB::table('ubicaciones')->count();
                    $this->info("Total de ubicaciones: " . $totalUbicaciones);
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Línea: ' . $e->getLine());
            $this->error('Archivo: ' . $e->getFile());
        }
    }
} 