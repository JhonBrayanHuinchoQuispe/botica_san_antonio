<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:assets {--production : Build for production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize and minify CSS/JS assets for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Iniciando optimización de assets...');
        $this->newLine();

        $this->createDirectories();
        $this->createBasicAssets();
        $this->buildAssets();
        $this->generateManifest();
        $this->displayResults();

        return 0;
    }

    private function createDirectories()
    {
        $this->info('📁 Creando directorios necesarios...');
        
        $directories = [
            'public/dist',
            'public/dist/css',
            'public/dist/js',
            'public/dist/images',
            'public/dist/fonts'
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("  ✅ Creado: {$dir}");
            }
        }

        $this->newLine();
    }

    private function createBasicAssets()
    {
        $this->info('📝 Creando assets básicos si no existen...');

        // CSS básico
        if (!File::exists('public/css/style.css')) {
            $css = "/* Estilos principales de la aplicación */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
}

.navbar-brand {
    font-weight: bold;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.btn {
    border-radius: 0.375rem;
}

.table {
    margin-bottom: 0;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}";
            File::put('public/css/style.css', $css);
            $this->line('  ✅ Creado: public/css/style.css');
        }

        // CSS personalizado
        if (!File::exists('public/css/custom.css')) {
            $customCss = "/* Estilos personalizados */
.dashboard-card {
    transition: transform 0.2s;
}

.dashboard-card:hover {
    transform: translateY(-2px);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}";
            File::put('public/css/custom.css', $customCss);
            $this->line('  ✅ Creado: public/css/custom.css');
        }

        // JavaScript básico
        if (!File::exists('public/js/app.js')) {
            $js = "// JavaScript principal de la aplicación
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmar eliminaciones
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });

    // Loading states
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class=\"spinner-border spinner-border-sm\" role=\"status\"></span> Procesando...';
            }
        });
    });
});";
            File::put('public/js/app.js', $js);
            $this->line('  ✅ Creado: public/js/app.js');
        }

        $this->newLine();
    }

    private function buildAssets()
    {
        $this->info('🔨 Compilando assets...');

        $isProduction = $this->option('production');
        $command = $isProduction ? 'npm run production' : 'npm run development';

        $this->line("  - Ejecutando: {$command}");
        
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $this->line('  ✅ Assets compilados exitosamente');
        } else {
            $this->error('  ❌ Error al compilar assets:');
            foreach ($output as $line) {
                $this->line("    {$line}");
            }
        }

        $this->newLine();
    }

    private function generateManifest()
    {
        $this->info('📋 Generando manifiesto de assets...');

        $manifest = [];
        $distPath = 'public/dist';

        // Buscar archivos CSS
        if (File::exists("{$distPath}/css")) {
            $cssFiles = File::files("{$distPath}/css");
            foreach ($cssFiles as $file) {
                $name = $file->getFilename();
                $manifest["css/{$name}"] = "/dist/css/{$name}";
            }
        }

        // Buscar archivos JS
        if (File::exists("{$distPath}/js")) {
            $jsFiles = File::files("{$distPath}/js");
            foreach ($jsFiles as $file) {
                $name = $file->getFilename();
                $manifest["js/{$name}"] = "/dist/js/{$name}";
            }
        }

        File::put('public/dist/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $this->line('  ✅ Manifiesto generado');

        $this->newLine();
    }

    private function displayResults()
    {
        $this->info('📊 Resultados de la optimización:');
        $this->newLine();

        $distPath = 'public/dist';
        
        if (File::exists($distPath)) {
            $this->displayFileStats("{$distPath}/css", 'CSS');
            $this->displayFileStats("{$distPath}/js", 'JavaScript');
        }

        $this->newLine();
        $this->info('💡 Consejos para usar los assets optimizados:');
        $this->line('  - Incluye los archivos desde /dist/ en tus vistas');
        $this->line('  - Usa el manifiesto para cache busting');
        $this->line('  - Ejecuta con --production para máxima optimización');
        $this->newLine();
        $this->info('✅ Optimización de assets completada');
    }

    private function displayFileStats($path, $type)
    {
        if (!File::exists($path)) {
            return;
        }

        $files = File::files($path);
        $totalSize = 0;

        $this->line("  📁 {$type}:");
        
        foreach ($files as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $sizeFormatted = $this->formatBytes($size);
            $this->line("    - {$file->getFilename()}: {$sizeFormatted}");
        }

        $totalFormatted = $this->formatBytes($totalSize);
        $this->line("    📊 Total {$type}: {$totalFormatted}");
        $this->newLine();
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}