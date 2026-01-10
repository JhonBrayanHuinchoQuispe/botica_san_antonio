<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTestInventoryProducts extends Command
{
    protected $signature = 'inventory:create-test-products {--reset}';
    protected $description = 'Crea productos de prueba para notificaciones: bajo stock, por vencer, vencido y agotado';

    public function handle(): int
    {
        $this->info('Creando productos de prueba para inventario...');

        $items = [
            [
                'nombre' => 'TEST Bajo stock',
                'estado' => 'Bajo stock',
                'stock_actual' => 5,
                'stock_minimo' => 20,
                'fecha_vencimiento' => null,
            ],
            [
                'nombre' => 'TEST Por vencer',
                'estado' => 'Por vencer',
                'stock_actual' => 15,
                'stock_minimo' => 5,
                'fecha_vencimiento' => now()->addDays(10)->toDateString(),
            ],
            [
                'nombre' => 'TEST Vencido',
                'estado' => 'Vencido',
                'stock_actual' => 10,
                'stock_minimo' => 5,
                'fecha_vencimiento' => now()->subDay()->toDateString(),
            ],
            [
                'nombre' => 'TEST Agotado',
                'estado' => 'Normal', // derivado por stock 0
                'stock_actual' => 0,
                'stock_minimo' => 5,
                'fecha_vencimiento' => null,
            ],
        ];

        foreach ($items as $item) {
            $barcode = 'TEST-'.Str::slug($item['nombre'], '-') . '-' . now()->format('YmdHis');

            if ($this->option('reset')) {
                // Borrar previos con el mismo prefijo
                Producto::where('codigo_barras', 'like', 'TEST-%')->delete();
            }

            $producto = Producto::updateOrCreate(
                ['codigo_barras' => $barcode],
                [
                    'nombre' => $item['nombre'],
                    'imagen' => null,
                    'lote' => 'TEST',
                    'categoria' => 'Pruebas',
                    'marca' => 'Pruebas',
                    'presentacion' => 'Caja',
                    'concentracion' => null,
                    'stock_actual' => $item['stock_actual'],
                    'stock_minimo' => $item['stock_minimo'],
                    'ubicacion' => 'Almacén Principal',
                    'ubicacion_almacen' => 'A1',
                    'fecha_fabricacion' => null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'],
                    'precio_compra' => 1.00,
                    'precio_venta' => 2.00,
                    'estado' => $item['estado'],
                ]
            );

            $this->line("✓ {$producto->nombre} creado con código: {$producto->codigo_barras}");
        }

        $this->info('Productos de prueba creados.');
        return self::SUCCESS;
    }
}