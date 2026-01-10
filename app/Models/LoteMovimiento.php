<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoteMovimiento extends Model
{
    use HasFactory;

    protected $table = 'lote_movimientos';

    protected $fillable = [
        'producto_ubicacion_id',
        'tipo_movimiento',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'motivo',
        'usuario_id',
        'datos_adicionales'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_anterior' => 'integer',
        'cantidad_nueva' => 'integer',
        'datos_adicionales' => 'array'
    ];

    // Relaciones
    public function productoUbicacion()
    {
        return $this->belongsTo(ProductoUbicacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeDelProducto($query, $productoId)
    {
        return $query->whereHas('productoUbicacion', function($q) use ($productoId) {
            $q->where('producto_id', $productoId);
        });
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    // Atributos
    public function getTipoMovimientoTextoAttribute()
    {
        $tipos = [
            'entrada' => 'Entrada',
            'venta' => 'Venta',
            'ajuste' => 'Ajuste',
            'vencimiento' => 'Vencimiento',
            'transferencia' => 'Transferencia'
        ];

        return $tipos[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }
}