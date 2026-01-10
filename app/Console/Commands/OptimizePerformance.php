<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:performance {--clear : Clear all caches first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance with caching and configuration optimizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando optimización de rendimiento...');
        $this->newLine();

        if ($this->option('clear')) {
            $this->clearCaches();
        }

        $this->optimizeConfiguration();
        $this->optimizeRoutes();
        $this->optimizeViews();
        $this->optimizeAutoloader();
        $this->optimizeOpcache();

        $this->newLine();
        $this->info('✅ Optimización de rendimiento completada');
        $this->displayOptimizationTips();
    }

    private function clearCaches()
    {
        $this->info('🧹 Limpiando caches...');
        
        $commands = [
            'config:clear' => 'Configuración',
            'route:clear' => 'Rutas',
            'view:clear' => 'Vistas',
            'cache:clear' => 'Cache de aplicación'
        ];

        foreach ($commands as $command => $description) {
            $this->line("  - Limpiando cache de {$description}...");
            Artisan::call($command);
        }

        $this->newLine();
    }

    private function optimizeConfiguration()
    {
        $this->info('⚙️  Optimizando configuración...');
        
        $this->line('  - Cacheando configuración...');
        Artisan::call('config:cache');
        
        $this->line('  - Optimizando autoloader...');
        exec('composer dump-autoload --optimize --no-dev --classmap-authoritative 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->line('  ✅ Autoloader optimizado');
        } else {
            $this->line('  ⚠️  Error al optimizar autoloader');
        }

        $this->newLine();
    }

    private function optimizeRoutes()
    {
        $this->info('🛣️  Optimizando rutas...');
        
        $this->line('  - Cacheando rutas...');
        Artisan::call('route:cache');
        
        $this->line('  ✅ Rutas optimizadas');
        $this->newLine();
    }

    private function optimizeViews()
    {
        $this->info('👁️  Optimizando vistas...');
        
        $this->line('  - Compilando vistas Blade...');
        Artisan::call('view:cache');
        
        $this->line('  ✅ Vistas optimizadas');
        $this->newLine();
    }

    private function optimizeAutoloader()
    {
        $this->info('🔄 Optimizando autoloader de Composer...');
        
        $this->line('  - Generando mapa de clases optimizado...');
        exec('composer dump-autoload --optimize 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->line('  ✅ Autoloader optimizado');
        } else {
            $this->line('  ⚠️  Error: ' . implode("\n", $output));
        }

        $this->newLine();
    }

    private function optimizeOpcache()
    {
        $this->info('💾 Verificando OPcache...');
        
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            if ($status && $status['opcache_enabled']) {
                $this->line('  ✅ OPcache está habilitado');
                $this->line('  📊 Archivos en cache: ' . $status['opcache_statistics']['num_cached_scripts']);
                $this->line('  💾 Memoria usada: ' . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB');
            } else {
                $this->line('  ⚠️  OPcache está deshabilitado');
            }
        } else {
            $this->line('  ⚠️  OPcache no está disponible');
        }

        $this->newLine();
    }

    private function displayOptimizationTips()
    {
        $this->info('💡 Consejos adicionales de optimización:');
        $this->newLine();
        
        $tips = [
            '🔧 Habilitar OPcache en producción (opcache.enable=1)',
            '🗄️  Usar Redis o Memcached para cache de sesiones',
            '📦 Minificar CSS y JS en producción',
            '🖼️  Optimizar imágenes (WebP, compresión)',
            '🌐 Usar CDN para assets estáticos',
            '📊 Monitorear rendimiento con herramientas como New Relic',
            '🔄 Implementar cache de base de datos para consultas frecuentes',
            '⚡ Considerar usar Laravel Octane en servidores compatibles'
        ];

        foreach ($tips as $tip) {
            $this->line("  {$tip}");
        }

        $this->newLine();
        $this->info('📈 Para medir el rendimiento, ejecuta: php artisan test:query-optimizations');
    }
}