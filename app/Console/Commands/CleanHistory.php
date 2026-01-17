<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia el historial de ventas, movimientos y notificaciones para reiniciar el sistema.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('ADVERTENCIA: ¿Estás seguro de que deseas eliminar TODO el historial (ventas, kardex, movimientos)? Esta acción NO se puede deshacer.')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info('Iniciando limpieza de historial...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'venta_detalles',
            'ventas',
            'lote_movimientos',
            'movimientos', // verify if this is the correct name or movimiento_stocks
            'kardex',
            'notifications',
            'venta_devoluciones',
            'caja_movimientos', // optional, keep open registers?
            // 'cajas', // Better not delete boxes if they are configurations
            'boletas', // if separated
            'invoices', // if separated
            'compra_detalles',
            'compras',
            'entrada_mercaderias', // if exists
        ];

        // Add additional tables based on common structures or check existence
        $potentialTables = [
            'movimiento_stocks',
            'stock_movements',
            'audit_logs',
            'activity_log',
            'sessions'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("Tabla '$table' vaciada.");
            } else {
                $this->warn("Tabla '$table' no encontrada, saltando.");
            }
        }

        foreach ($potentialTables as $table) {
             if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("Tabla '$table' vaciada.");
            }
        }

        // Reset auto-increment if needed (truncate usually does this)

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('¡Historial eliminado correctamente! El sistema está limpio para nuevas operaciones.');
        $this->newLine();
        $this->info('Recuerda ejecutar: php artisan cache:clear');
    }
}
