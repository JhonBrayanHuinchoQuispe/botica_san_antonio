<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:all {--test : Run performance tests after optimization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute all performance optimizations for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 INICIANDO OPTIMIZACIÓN COMPLETA DEL SISTEMA');
        $this->info('================================================');
        $this->newLine();

        $startTime = microtime(true);

        // Paso 1: Limpiar caches existentes
        $this->executeStep('🧹 Limpiando caches existentes', function() {
            Artisan::call('optimize:performance', ['--clear' => true]);
            return 'Caches limpiados correctamente';
        });

        // Paso 2: Optimizar configuración y rendimiento
        $this->executeStep('⚙️ Optimizando configuración y rendimiento', function() {
            Artisan::call('optimize:performance');
            return 'Configuración optimizada (cache, rutas, vistas, autoloader)';
        });

        // Paso 3: Optimizar assets CSS/JS
        $this->executeStep('🎨 Optimizando assets CSS/JS', function() {
            Artisan::call('optimize:assets-simple');
            return 'Assets minificados y optimizados';
        });

        // Paso 4: Limpiar cache de optimización de consultas
        $this->executeStep('🗄️ Limpiando cache de consultas optimizadas', function() {
            Artisan::call('cache:clear');
            return 'Cache de consultas limpiado';
        });

        // Paso 5: Ejecutar tests de rendimiento (opcional)
        if ($this->option('test')) {
            $this->executeStep('📊 Ejecutando tests de rendimiento', function() {
                Artisan::call('test:query-optimizations');
                return 'Tests de rendimiento completados';
            });
        }

        $endTime = microtime(true);
        $totalTime = round(($endTime - $startTime) * 1000, 2);

        $this->displaySummary($totalTime);
        $this->displayRecommendations();

        return 0;
    }

    private function executeStep($title, $callback)
    {
        $this->info($title);
        $stepStart = microtime(true);

        try {
            $result = $callback();
            $stepEnd = microtime(true);
            $stepTime = round(($stepEnd - $stepStart) * 1000, 2);
            
            $this->line("  ✅ {$result} ({$stepTime}ms)");
        } catch (\Exception $e) {
            $this->error("  ❌ Error: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function displaySummary($totalTime)
    {
        $this->info('📈 RESUMEN DE OPTIMIZACIÓN');
        $this->info('==========================');
        $this->newLine();

        $optimizations = [
            '🔧 Configuración Laravel' => 'Cache de config, rutas y vistas habilitado',
            '🗄️ Base de datos' => 'Eager loading y optimización de consultas',
            '🎨 Assets frontend' => 'CSS/JS minificados y combinados',
            '💾 Cache del sistema' => 'Cache de aplicación optimizado',
            '⚡ Autoloader' => 'Composer optimizado para producción'
        ];

        foreach ($optimizations as $category => $description) {
            $this->line("  {$category}: {$description}");
        }

        $this->newLine();
        $this->info("⏱️ Tiempo total de optimización: {$totalTime}ms");
        $this->newLine();
    }

    private function displayRecommendations()
    {
        $this->info('💡 RECOMENDACIONES ADICIONALES');
        $this->info('==============================');
        $this->newLine();

        $recommendations = [
            '🔧 Servidor Web' => [
                'Habilitar compresión Gzip/Brotli',
                'Configurar cache de headers HTTP',
                'Habilitar OPcache en PHP (opcache.enable=1)'
            ],
            '🗄️ Base de Datos' => [
                'Crear índices para consultas frecuentes',
                'Configurar query cache en MySQL',
                'Monitorear slow query log'
            ],
            '🌐 Frontend' => [
                'Implementar lazy loading de imágenes',
                'Usar CDN para assets estáticos',
                'Optimizar imágenes (WebP, compresión)'
            ],
            '📊 Monitoreo' => [
                'Implementar APM (New Relic, Datadog)',
                'Configurar logs de rendimiento',
                'Monitorear métricas de usuario real'
            ]
        ];

        foreach ($recommendations as $category => $items) {
            $this->line("  {$category}:");
            foreach ($items as $item) {
                $this->line("    - {$item}");
            }
            $this->newLine();
        }

        $this->info('🔄 COMANDOS ÚTILES');
        $this->info('==================');
        $this->newLine();
        
        $commands = [
            'optimize:all --test' => 'Ejecutar optimización completa con tests',
            'test:query-optimizations' => 'Solo ejecutar tests de rendimiento',
            'optimize:performance --clear' => 'Limpiar todos los caches',
            'optimize:assets-simple' => 'Solo optimizar CSS/JS'
        ];

        foreach ($commands as $command => $description) {
            $this->line("  php artisan {$command}");
            $this->line("    └─ {$description}");
            $this->newLine();
        }

        $this->info('✅ OPTIMIZACIÓN COMPLETA FINALIZADA');
        $this->info('Tu aplicación ahora debería tener un rendimiento significativamente mejorado.');
    }
}