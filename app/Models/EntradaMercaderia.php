<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EntradaMercaderia extends Model
{
    use HasFactory;

    protected $table = 'entradas_mercaderia';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'proveedor_id',
        'cantidad',
        'precio_compra_anterior',
        'precio_compra_nuevo',
        'precio_venta_anterior',
        'precio_venta_nuevo',
        'lote',
        'fecha_vencimiento',
        'observaciones',
        'stock_anterior',
        'stock_nuevo',
        'fecha_entrada'
    ];

    protected $casts = [
        'precio_compra_anterior' => 'decimal:2',
        'precio_compra_nuevo' => 'decimal:2',
        'precio_venta_anterior' => 'decimal:2',
        'precio_venta_nuevo' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_entrada' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relación con producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Scope para filtrar por fecha
    public function scopeFechaBetween($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_entrada', [$fechaInicio, $fechaFin]);
    }

    // Scope para filtrar por producto
    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    // Scope para hoy
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_entrada', today());
    }

    // Scope para este mes
    public function scopeEsteMes($query)
    {
        return $query->whereMonth('fecha_entrada', now()->month)
                    ->whereYear('fecha_entrada', now()->year);
    }

    // Accessor para mostrar si hubo cambio de precio
    public function getHuboCambioPrecioCompraAttribute()
    {
        return $this->precio_compra_anterior !== $this->precio_compra_nuevo;
    }

    public function getHuboCambioPrecioVentaAttribute()
    {
        return $this->precio_venta_anterior !== $this->precio_venta_nuevo;
    }

    // Accessor para valor total de la entrada
    public function getValorTotalAttribute()
    {
        if ($this->precio_compra_nuevo) {
            return $this->cantidad * $this->precio_compra_nuevo;
        }
        return $this->cantidad * ($this->precio_compra_anterior ?? 0);
    }

    // Método estático para obtener estadísticas
    public static function obtenerEstadisticas()
    {
        return [
            'entradas_hoy' => self::hoy()->count(),
            'entradas_mes' => self::esteMes()->count(),
            'productos_ingresados_mes' => self::esteMes()->sum('cantidad'),
            'valor_total_mes' => self::esteMes()->get()->sum('valor_total')
        ];
    }
}
