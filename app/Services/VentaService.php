<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\PuntoVenta\Venta;
use App\Models\PuntoVenta\VentaDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaService
{
    /**
     * Procesar una nueva venta
     */
    public function procesarVenta(array $datosVenta): array
    {
        try {
            DB::beginTransaction();

            // Validar stock disponible
            $this->validarStockDisponible($datosVenta['productos']);

            // Crear la venta
            $venta = $this->crearVenta($datosVenta);

            // Procesar productos
            $this->procesarProductosVenta($venta, $datosVenta['productos']);

            // Actualizar stock
            $this->actualizarStock($datosVenta['productos']);

            DB::commit();

            Log::info('Venta procesada exitosamente', ['venta_id' => $venta->id]);

            return [
                'success' => true,
                'venta' => $venta,
                'message' => 'Venta procesada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar venta: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ];
        }
    }

    private function validarStockDisponible(array $productos): void
    {
        foreach ($productos as $item) {
            $producto = Producto::find($item['id']);
            
            if (!$producto) {
                throw new \Exception("Producto no encontrado: {$item['id']}");
            }

            if ($producto->stock_actual < $item['cantidad']) {
                throw new \Exception("Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock_actual}");
            }
        }
    }

    private function crearVenta(array $datos): Venta
    {
        return Venta::create([
            'usuario_id' => auth()->id(),
            'cliente_dni' => $datos['cliente_dni'] ?? null,
            'subtotal' => $datos['subtotal'],
            'descuento' => $datos['descuento'] ?? 0,
            'igv' => $datos['igv'],
            'total' => $datos['total'],
            'metodo_pago' => $datos['metodo_pago'],
            'efectivo_recibido' => $datos['efectivo_recibido'] ?? null,
            'vuelto' => $datos['vuelto'] ?? 0,
            'con_comprobante' => $datos['con_comprobante'] ?? false,
            'fecha_venta' => now(),
            'estado' => 'completada'
        ]);
    }

    private function procesarProductosVenta(Venta $venta, array $productos): void
    {
        foreach ($productos as $item) {
            VentaDetalle::create([
                'venta_id' => $venta->id,
                'producto_id' => $item['id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'subtotal' => $item['cantidad'] * $item['precio']
            ]);
        }
    }

    private function actualizarStock(array $productos): void
    {
        foreach ($productos as $item) {
            $producto = Producto::find($item['id']);
            $producto->decrement('stock_actual', $item['cantidad']);
        }
    }
}