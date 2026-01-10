<?php

namespace App\Console\Commands;

use App\Http\Controllers\Ubicacion\UbicacionController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class PruebaApiEstantes extends Command
{
    protected $signature = 'api:probar-estantes';
    protected $description = 'Probar la API de estantes directamente';

    public function handle()
    {
        $this->info('=== PRUEBA API DE ESTANTES ===');
        
        try {
            // Crear una instancia del controlador
            $controller = new UbicacionController();
            
            // Crear un request mock
            $request = new Request();
            
            // Llamar al método obtenerEstantes
            $response = $controller->obtenerEstantes();
            
            // Obtener el contenido de la respuesta
            $data = json_decode($response->getContent(), true);
            
            $this->info('Respuesta de la API:');
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            
            if ($data['success']) {
                $this->info('✅ API funcionando correctamente');
                $this->info('Total de estantes devueltos: ' . count($data['data']));
            } else {
                $this->error('❌ Error en la API: ' . $data['message']);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error ejecutando API: ' . $e->getMessage());
            $this->error('Línea: ' . $e->getLine());
        }
    }
} 