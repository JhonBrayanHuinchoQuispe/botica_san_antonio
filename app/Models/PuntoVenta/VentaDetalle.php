<?php

namespace App\Models\PuntoVenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Producto;

class VentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'lotes_info'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'lotes_info' => 'array',
    ];

    // Relaciones
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Accessors
    public function getTotalAttribute()
    {
        return $this->cantidad * $this->precio_unitario;
    }

    // Métodos
    public function calcularSubtotal()
    {
        $this->subtotal = $this->cantidad * $this->precio_unitario;
        $this->save();
        
        return $this;
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detalle) {
            $producto = $detalle->producto;
            if ($detalle->tipo_cantidad === 'presentacion') {
                $detalle->cantidad_unidades = $producto->convertirAUnidades($detalle->cantidad);
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario; // Precio unitario sería por presentación
            } else {
                $detalle->cantidad_unidades = $detalle->cantidad;
                $detalle->subtotal = $detalle->cantidad * ($detalle->precio_unitario / $producto->unidades_por_presentacion); // Ajustar precio por unidad
            }
        });

        static::saved(function ($detalle) {
            // Recalcular totales de la venta padre
            $detalle->venta->calcularTotales();
            // Actualizar stock del producto
            $detalle->producto->actualizarStockVenta($detalle->cantidad_unidades, 'unidad');
        });

        static::deleted(function ($detalle) {
            // Recalcular totales de la venta padre
            if ($detalle->venta) {
                $detalle->venta->calcularTotales();
            }
            // Revertir stock si es necesario
            $detalle->producto->agregarStock($detalle->cantidad_unidades, 'unidad');
        });
    }
}