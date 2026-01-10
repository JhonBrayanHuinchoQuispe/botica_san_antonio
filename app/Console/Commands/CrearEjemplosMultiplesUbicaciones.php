<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\Estante;
use App\Models\Ubicacion;
use App\Models\ProductoUbicacion;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;

class CrearEjemplosMultiplesUbicaciones extends Command
{
    protected $signature = 'demo:crear-ubicaciones-multiples';
    protected $description = 'Crear productos de ejemplo distribuidos en múltiples ubicaciones para demostrar la funcionalidad';

    public function handle()
    {
        $this->info('🚀 Creando ejemplos de productos con múltiples ubicaciones...');

        try {
            DB::beginTransaction();

            // Verificar que existan estantes
            $estantes = Estante::with('ubicaciones')->where('activo', true)->get();
            
            if ($estantes->isEmpty()) {
                $this->error('❌ No hay estantes disponibles. Primero crea algunos estantes.');
                return 1;
            }

            $this->info("📋 Estantes disponibles: {$estantes->count()}");

            // Productos de ejemplo para crear/actualizar
            $productosEjemplo = [
                [
                    'nombre' => 'Paracetamol 500mg',
                    'codigo_barras' => '7501234567890',
                    'concentracion' => '500mg',
                    'marca' => 'Genérico',
                    'categoria' => 'Analgésicos',
                    'presentacion' => 'Tableta',
                    'precio_compra' => 0.50,
                    'precio_venta' => 3.00,
                    'stock_minimo' => 20,
                    'distribuciones' => [
                        ['cantidad' => 50, 'lote' => 'PAR001'],
                        ['cantidad' => 30, 'lote' => 'PAR002'],
                        ['cantidad' => 20, 'lote' => 'PAR003']
                    ]
                ],
                [
                    'nombre' => 'Ibuprofeno 400mg',
                    'codigo_barras' => '7501234567891',
                    'concentracion' => '400mg',
                    'marca' => 'Genérico',
                    'categoria' => 'Antiinflamatorios',
                    'presentacion' => 'Tableta',
                    'precio_compra' => 0.75,
                    'precio_venta' => 4.50,
                    'stock_minimo' => 15,
                    'distribuciones' => [
                        ['cantidad' => 40, 'lote' => 'IBU001'],
                        ['cantidad' => 25, 'lote' => 'IBU002']
                    ]
                ],
                [
                    'nombre' => 'Amoxicilina 500mg',
                    'codigo_barras' => '7501234567892',
                    'concentracion' => '500mg',
                    'marca' => 'Farmex',
                    'categoria' => 'Antibióticos',
                    'presentacion' => 'Cápsula',
                    'precio_compra' => 1.20,
                    'precio_venta' => 8.50,
                    'stock_minimo' => 10,
                    'distribuciones' => [
                        ['cantidad' => 35, 'lote' => 'AMX001'],
                        ['cantidad' => 15, 'lote' => 'AMX002'],
                        ['cantidad' => 25, 'lote' => 'AMX003'],
                        ['cantidad' => 10, 'lote' => 'AMX004']
                    ]
                ],
                [
                    'nombre' => 'Loratadina 10mg',
                    'codigo_barras' => '7501234567893',
                    'concentracion' => '10mg',
                    'marca' => 'Allergo',
                    'categoria' => 'Antihistamínicos',
                    'presentacion' => 'Tableta',
                    'precio_compra' => 0.80,
                    'precio_venta' => 5.00,
                    'stock_minimo' => 12,
                    'distribuciones' => [
                        ['cantidad' => 60, 'lote' => 'LOR001'],
                        ['cantidad' => 40, 'lote' => 'LOR002']
                    ]
                ]
            ];

            // Obtener ubicaciones disponibles
            $ubicacionesDisponibles = collect();
            foreach ($estantes as $estante) {
                $ubicacionesDisponibles = $ubicacionesDisponibles->merge($estante->ubicaciones);
            }

            if ($ubicacionesDisponibles->isEmpty()) {
                $this->error('❌ No hay ubicaciones disponibles en los estantes.');
                return 1;
            }

            $this->info("📍 Ubicaciones disponibles: {$ubicacionesDisponibles->count()}");

            foreach ($productosEjemplo as $productoData) {
                $this->info("\n🏥 Procesando: {$productoData['nombre']}");

                // Crear o actualizar producto
                $producto = Producto::updateOrCreate(
                    ['codigo_barras' => $productoData['codigo_barras']],
                    [
                        'nombre' => $productoData['nombre'],
                        'concentracion' => $productoData['concentracion'],
                        'marca' => $productoData['marca'],
                        'categoria' => $productoData['categoria'],
                        'presentacion' => $productoData['presentacion'],
                        'precio_compra' => $productoData['precio_compra'],
                        'precio_venta' => $productoData['precio_venta'],
                        'stock_minimo' => $productoData['stock_minimo'],
                        'lote' => 'LOTE_' . date('YmdHis'),
                        'estado' => 'Normal',
                        'permite_venta_unitaria' => true,
                        'permite_venta_presentacion' => true,
                        'unidades_por_presentacion' => 1,
                        'fecha_fabricacion' => now()->subMonths(6),
                        'fecha_vencimiento' => now()->addYears(2)
                    ]
                );

                $this->info("   ✅ Producto creado/actualizado: ID {$producto->id}");

                // Limpiar ubicaciones anteriores
                ProductoUbicacion::where('producto_id', $producto->id)->delete();
                $this->info("   🧹 Ubicaciones anteriores limpiadas");

                // Distribuir en múltiples ubicaciones
                $distribuciones = $productoData['distribuciones'];
                $totalCantidad = collect($distribuciones)->sum('cantidad');
                $ubicacionesTexto = [];

                foreach ($distribuciones as $index => $distribucion) {
                    // Seleccionar una ubicación aleatoria disponible
                    $ubicacion = $ubicacionesDisponibles->random();
                    
                    // Crear la relación producto-ubicación
                    ProductoUbicacion::create([
                        'producto_id' => $producto->id,
                        'ubicacion_id' => $ubicacion->id,
                        'cantidad' => $distribucion['cantidad'],
                        'lote' => $distribucion['lote'],
                        'fecha_ingreso' => now(),
                        'fecha_vencimiento' => now()->addYears(2),
                        'observaciones' => "Distribución de ejemplo #" . ($index + 1)
                    ]);

                    $estante = $ubicacion->estante;
                    $ubicacionTexto = $estante->nombre . ' - ' . $ubicacion->codigo;
                    $ubicacionesTexto[] = $ubicacionTexto;

                    $this->info("   📍 Ubicado en: {$ubicacionTexto} -> {$distribucion['cantidad']} unidades (Lote: {$distribucion['lote']})");
                }

                // Actualizar producto con información de múltiples ubicaciones
                $ubicacionAlmacen = count($ubicacionesTexto) > 1 
                    ? "Múltiples ubicaciones (" . count($ubicacionesTexto) . ")"
                    : $ubicacionesTexto[0];

                $producto->update([
                    'ubicacion_almacen' => $ubicacionAlmacen,
                    'stock_actual' => $totalCantidad
                ]);

                // Registrar movimiento de stock
                MovimientoStock::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'entrada',
                    'cantidad' => $totalCantidad,
                    'motivo' => "Distribución de ejemplo en " . count($distribuciones) . " ubicaciones",
                    'usuario_id' => 1 // Usuario administrador
                ]);

                $this->info("   💾 Stock actualizado: {$totalCantidad} unidades en {$ubicacionAlmacen}");
            }

            DB::commit();

            $this->newLine();
            $this->info('🎉 ¡Ejemplos creados exitosamente!');
            $this->info('📊 Productos con múltiples ubicaciones:');
            
            // Mostrar resumen
            foreach ($productosEjemplo as $productoData) {
                $producto = Producto::where('codigo_barras', $productoData['codigo_barras'])->first();
                $ubicacionesCount = $producto->ubicaciones->where('cantidad', '>', 0)->count();
                $this->info("   • {$producto->nombre}: {$ubicacionesCount} ubicaciones, {$producto->stock_actual} unidades");
            }

            $this->newLine();
            $this->info('💡 Ahora puedes ver estos productos en el módulo de inventario');
            $this->info('📱 Los productos con múltiples ubicaciones aparecerán con un badge azul y contador');
            $this->info('🖱️  Haz clic en ellos para ver el detalle de todas las ubicaciones');

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error al crear ejemplos: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
} 