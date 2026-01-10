<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Actualizar estados de productos cada hora
        $schedule->command('productos:actualizar-estados --force')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Alertas de inventario por push cada hora
        $schedule->command('inventory:push-alerts')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Limpiar logs cada día a las 2 AM
        $schedule->command('logs:optimize --days=30')
                 ->dailyAt('02:00')
                 ->withoutOverlapping();

        // Limpiar cache cada 6 horas
        $schedule->command('cache:clear')
                 ->everySixHours();

        // Optimizar base de datos semanalmente
        $schedule->command('optimize:clear')
                 ->weekly()
                 ->sundays()
                 ->at('03:00');

        // Backup de base de datos diario
        $schedule->command('backup:run')
                 ->dailyAt('01:00')
                 ->withoutOverlapping();

        // Entrenamiento IA diario
        $schedule->command('ia:entrenar')
                 ->dailyAt('02:20')
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
