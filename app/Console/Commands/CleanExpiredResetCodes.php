<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PasswordResetCode;
use Illuminate\Support\Facades\Log;

class CleanExpiredResetCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:clean-expired-codes {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar códigos de recuperación de contraseña expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de códigos expirados...');

        try {
            // Contar códigos expirados antes de la limpieza
            $expiredCount = PasswordResetCode::where('expires_at', '<', now())
                ->orWhere('used', true)
                ->count();

            if ($expiredCount === 0) {
                $this->info('✅ No hay códigos expirados para limpiar.');
                return Command::SUCCESS;
            }

            $this->info("📊 Se encontraron {$expiredCount} códigos para limpiar.");

            // Confirmar si no se usa --force
            if (!$this->option('force')) {
                if (!$this->confirm('¿Deseas continuar con la limpieza?')) {
                    $this->info('❌ Operación cancelada.');
                    return Command::FAILURE;
                }
            }

            // Realizar la limpieza
            $deletedCount = PasswordResetCode::cleanExpired();

            $this->info("✅ Limpieza completada. Se eliminaron {$deletedCount} códigos expirados.");

            // Log de la operación
            Log::info('Códigos de recuperación limpiados', [
                'deleted_count' => $deletedCount,
                'command' => 'auth:clean-expired-codes',
                'executed_at' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            
            Log::error('Error en limpieza de códigos expirados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}