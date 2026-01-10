<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MinifyAssets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'assets:minify 
                            {--css : Solo minificar CSS}
                            {--js : Solo minificar JavaScript}
                            {--force : Forzar minificación aunque ya existan archivos}';

    /**
     * The console command description.
     */
    protected $description = 'Minificar archivos CSS y JavaScript para producción';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando minificación de assets...');

        $cssOnly = $this->option('css');
        $jsOnly = $this->option('js');
        $force = $this->option('force');

        $cssProcessed = 0;
        $jsProcessed = 0;

        if (!$jsOnly) {
            $cssProcessed = $this->minifyCssFiles($force);
        }

        if (!$cssOnly) {
            $jsProcessed = $this->minifyJsFiles($force);
        }

        $this->showSummary($cssProcessed, $jsProcessed);

        $this->info('✅ Minificación completada');
        return 0;
    }

    /**
     * Minificar archivos CSS
     */
    private function minifyCssFiles(bool $force): int
    {
        $this->info('🎨 Minificando archivos CSS...');
        
        $cssPath = public_path('css');
        $processed = 0;

        if (!File::exists($cssPath)) {
            $this->warn('Directorio CSS no encontrado');
            return 0;
        }

        $cssFiles = File::glob($cssPath . '/*.css');

        foreach ($cssFiles as $file) {
            $filename = basename($file);
            
            // Saltar archivos ya minificados
            if (str_contains($filename, '.min.')) {
                continue;
            }

            $minifiedFile = str_replace('.css', '.min.css', $file);
            
            // Verificar si ya existe y no forzar
            if (File::exists($minifiedFile) && !$force) {
                $this->line("  - Saltando {$filename} (ya existe minificado)");
                continue;
            }

            $originalContent = File::get($file);
            $minifiedContent = $this->minifyCss($originalContent);
            
            File::put($minifiedFile, $minifiedContent);
            
            $originalSize = strlen($originalContent);
            $minifiedSize = strlen($minifiedContent);
            $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
            
            $this->line("  ✓ {$filename} → {$this->formatBytes($originalSize)} → {$this->formatBytes($minifiedSize)} ({$savings}% reducción)");
            $processed++;
        }

        return $processed;
    }

    /**
     * Minificar archivos JavaScript
     */
    private function minifyJsFiles(bool $force): int
    {
        $this->info('⚡ Minificando archivos JavaScript...');
        
        $jsPath = public_path('js');
        $processed = 0;

        if (!File::exists($jsPath)) {
            $this->warn('Directorio JS no encontrado');
            return 0;
        }

        $jsFiles = File::glob($jsPath . '/*.js');

        foreach ($jsFiles as $file) {
            $filename = basename($file);
            
            // Saltar archivos ya minificados
            if (str_contains($filename, '.min.')) {
                continue;
            }

            $minifiedFile = str_replace('.js', '.min.js', $file);
            
            // Verificar si ya existe y no forzar
            if (File::exists($minifiedFile) && !$force) {
                $this->line("  - Saltando {$filename} (ya existe minificado)");
                continue;
            }

            $originalContent = File::get($file);
            $minifiedContent = $this->minifyJs($originalContent);
            
            File::put($minifiedFile, $minifiedContent);
            
            $originalSize = strlen($originalContent);
            $minifiedSize = strlen($minifiedContent);
            $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
            
            $this->line("  ✓ {$filename} → {$this->formatBytes($originalSize)} → {$this->formatBytes($minifiedSize)} ({$savings}% reducción)");
            $processed++;
        }

        return $processed;
    }

    /**
     * Minificar CSS
     */
    private function minifyCss(string $css): string
    {
        // Remover comentarios
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espacios en blanco innecesarios
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espacios alrededor de caracteres especiales
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Remover punto y coma antes de }
        $css = str_replace(';}', '}', $css);
        
        // Remover espacios al inicio y final
        $css = trim($css);
        
        // Optimizar colores hex
        $css = preg_replace('/#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3/i', '#$1$2$3', $css);
        
        // Remover unidades innecesarias en 0
        $css = preg_replace('/\b0+(px|em|%|in|cm|mm|pc|pt|ex)/i', '0', $css);
        
        return $css;
    }

    /**
     * Minificar JavaScript (básico)
     */
    private function minifyJs(string $js): string
    {
        // Remover comentarios de línea
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remover comentarios de bloque
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remover espacios en blanco excesivos
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remover espacios alrededor de operadores
        $js = preg_replace('/\s*([=+\-*\/{}();,:])\s*/', '$1', $js);
        
        // Remover saltos de línea innecesarios
        $js = preg_replace('/;\s*\n\s*/', ';', $js);
        
        return trim($js);
    }

    /**
     * Mostrar resumen de la minificación
     */
    private function showSummary(int $cssProcessed, int $jsProcessed): void
    {
        $this->info('📊 Resumen de minificación:');
        
        $this->table(
            ['Tipo', 'Archivos procesados'],
            [
                ['CSS', $cssProcessed],
                ['JavaScript', $jsProcessed],
                ['Total', $cssProcessed + $jsProcessed]
            ]
        );

        // Calcular ahorros totales
        $this->calculateTotalSavings();
    }

    /**
     * Calcular ahorros totales
     */
    private function calculateTotalSavings(): void
    {
        $cssPath = public_path('css');
        $jsPath = public_path('js');
        
        $originalSize = 0;
        $minifiedSize = 0;

        // Calcular CSS
        if (File::exists($cssPath)) {
            $cssFiles = File::glob($cssPath . '/*.css');
            foreach ($cssFiles as $file) {
                if (!str_contains(basename($file), '.min.')) {
                    $originalSize += File::size($file);
                    
                    $minFile = str_replace('.css', '.min.css', $file);
                    if (File::exists($minFile)) {
                        $minifiedSize += File::size($minFile);
                    }
                }
            }
        }

        // Calcular JS
        if (File::exists($jsPath)) {
            $jsFiles = File::glob($jsPath . '/*.js');
            foreach ($jsFiles as $file) {
                if (!str_contains(basename($file), '.min.')) {
                    $originalSize += File::size($file);
                    
                    $minFile = str_replace('.js', '.min.js', $file);
                    if (File::exists($minFile)) {
                        $minifiedSize += File::size($minFile);
                    }
                }
            }
        }

        if ($originalSize > 0) {
            $totalSavings = $originalSize - $minifiedSize;
            $percentSavings = round(($totalSavings / $originalSize) * 100, 1);
            
            $this->info("💾 Ahorro total: {$this->formatBytes($totalSavings)} ({$percentSavings}%)");
            $this->info("📏 Tamaño original: {$this->formatBytes($originalSize)}");
            $this->info("📦 Tamaño minificado: {$this->formatBytes($minifiedSize)}");
        }
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}