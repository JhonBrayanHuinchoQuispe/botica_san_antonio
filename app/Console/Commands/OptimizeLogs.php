<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class OptimizeLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:optimize 
                            {--days=7 : Días de logs a mantener}
                            {--compress : Comprimir logs antiguos}
                            {--clean : Limpiar logs completamente}';

    /**
     * The console command description.
     */
    protected $description = 'Optimizar y limpiar archivos de logs del sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Iniciando optimización de logs...');

        $days = (int) $this->option('days');
        $compress = $this->option('compress');
        $clean = $this->option('clean');

        if ($clean) {
            return $this->cleanAllLogs();
        }

        $this->cleanOldLogs($days);
        
        if ($compress) {
            $this->compressLogs();
        }

        $this->optimizeCurrentLogs();
        $this->showLogStatistics();

        $this->info('✅ Optimización de logs completada');
        return 0;
    }

    /**
     * Limpiar logs antiguos
     */
    private function cleanOldLogs(int $days): void
    {
        $this->info("🗑️ Limpiando logs de más de {$days} días...");
        
        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays($days);
        $deletedCount = 0;

        if (!File::exists($logPath)) {
            $this->warn('Directorio de logs no encontrado');
            return;
        }

        $files = File::files($logPath);
        
        foreach ($files as $file) {
            $fileTime = File::lastModified($file->getPathname());
            
            if ($fileTime < $cutoffDate->timestamp) {
                File::delete($file->getPathname());
                $deletedCount++;
                $this->line("  - Eliminado: {$file->getFilename()}");
            }
        }

        $this->info("📊 Eliminados {$deletedCount} archivos de log");
    }

    /**
     * Comprimir logs antiguos
     */
    private function compressLogs(): void
    {
        $this->info('📦 Comprimiendo logs...');
        
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $compressedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            
            // Solo comprimir archivos .log que no estén ya comprimidos
            if (str_ends_with($filename, '.log') && !str_contains($filename, '.gz')) {
                $filePath = $file->getPathname();
                $gzPath = $filePath . '.gz';
                
                // Comprimir archivo
                $data = File::get($filePath);
                $compressed = gzencode($data, 9);
                
                if ($compressed !== false) {
                    File::put($gzPath, $compressed);
                    File::delete($filePath);
                    $compressedCount++;
                    $this->line("  - Comprimido: {$filename}");
                }
            }
        }

        $this->info("📊 Comprimidos {$compressedCount} archivos");
    }

    /**
     * Optimizar logs actuales
     */
    private function optimizeCurrentLogs(): void
    {
        $this->info('⚡ Optimizando logs actuales...');
        
        $logPath = storage_path('logs');
        $currentLog = $logPath . '/laravel.log';
        
        if (!File::exists($currentLog)) {
            $this->warn('Log actual no encontrado');
            return;
        }

        $size = File::size($currentLog);
        $maxSize = 50 * 1024 * 1024; // 50MB

        if ($size > $maxSize) {
            $this->warn("Log actual muy grande: " . $this->formatBytes($size));
            
            // Rotar log actual
            $rotatedName = 'laravel-' . date('Y-m-d-H-i-s') . '.log';
            File::move($currentLog, $logPath . '/' . $rotatedName);
            
            // Crear nuevo log vacío
            File::put($currentLog, '');
            
            $this->info("📋 Log rotado a: {$rotatedName}");
        }
    }

    /**
     * Limpiar todos los logs
     */
    private function cleanAllLogs(): int
    {
        if (!$this->confirm('⚠️ ¿Estás seguro de que quieres eliminar TODOS los logs?')) {
            $this->info('Operación cancelada');
            return 1;
        }

        $this->info('🗑️ Eliminando todos los logs...');
        
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $deletedCount = 0;

        foreach ($files as $file) {
            File::delete($file->getPathname());
            $deletedCount++;
        }

        $this->info("📊 Eliminados {$deletedCount} archivos de log");
        return 0;
    }

    /**
     * Mostrar estadísticas de logs
     */
    private function showLogStatistics(): void
    {
        $this->info('📊 Estadísticas de logs:');
        
        $logPath = storage_path('logs');
        
        if (!File::exists($logPath)) {
            $this->warn('Directorio de logs no encontrado');
            return;
        }

        $files = File::files($logPath);
        $totalSize = 0;
        $fileCount = count($files);

        foreach ($files as $file) {
            $totalSize += File::size($file->getPathname());
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Archivos de log', $fileCount],
                ['Tamaño total', $this->formatBytes($totalSize)],
                ['Directorio', $logPath],
                ['Espacio libre', $this->formatBytes(disk_free_space($logPath))]
            ]
        );

        // Mostrar archivos más grandes
        if ($fileCount > 0) {
            $this->info('📁 Archivos más grandes:');
            
            $fileSizes = [];
            foreach ($files as $file) {
                $fileSizes[] = [
                    'name' => $file->getFilename(),
                    'size' => File::size($file->getPathname()),
                    'modified' => date('Y-m-d H:i:s', File::lastModified($file->getPathname()))
                ];
            }

            // Ordenar por tamaño
            usort($fileSizes, fn($a, $b) => $b['size'] <=> $a['size']);
            
            $topFiles = array_slice($fileSizes, 0, 5);
            
            $this->table(
                ['Archivo', 'Tamaño', 'Modificado'],
                array_map(fn($file) => [
                    $file['name'],
                    $this->formatBytes($file['size']),
                    $file['modified']
                ], $topFiles)
            );
        }
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}