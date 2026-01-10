<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'tipo_movimiento',
        'cantidad',
        'motivo',
        'usuario_id',
        'datos_adicionales'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'datos_adicionales' => 'json'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Atributos calculados
    public function getDescripcionMovimientoAttribute()
    {
        switch ($this->tipo_movimiento) {
            case 'entrada':
                return "Entrada de {$this->cantidad} unidades";
            case 'salida':
                return "Salida de {$this->cantidad} unidades";
            case 'transferencia':
                return "Transferencia de {$this->cantidad} unidades";
            case 'ajuste':
                return "Ajuste de {$this->cantidad} unidades";
            case 'venta':
                return "Venta de {$this->cantidad} unidades";
            case 'devolucion':
                return "Devolución de {$this->cantidad} unidades";
            default:
                return "Movimiento de {$this->cantidad} unidades";
        }
    }

    public function getEsEntradaAttribute()
    {
        return in_array($this->tipo_movimiento, ['entrada', 'transferencia', 'devolucion']);
    }

    public function getEsSalidaAttribute()
    {
        return in_array($this->tipo_movimiento, ['salida', 'venta']);
    }

    // Scopes
    public function scopeDelProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeEntreUbicaciones($query, $origenId, $destinoId)
    {
        return $query->where('ubicacion_origen_id', $origenId)
                    ->where('ubicacion_destino_id', $destinoId);
    }

    public function scopeEnFecha($query, $fecha)
    {
        return $query->whereDate('created_at', $fecha);
    }

    public function scopeEnRangoFecha($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo_movimiento', ['entrada', 'transferencia', 'devolucion']);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_movimiento', ['salida', 'venta']);
    }

    // Métodos estáticos para crear movimientos
    private static function canLog(): bool
    {
        if (!Schema::hasTable('movimientos_stock')) {
            Log::warning('Tabla movimientos_stock no existe; se omite registro de movimiento');
            return false;
        }
        return true;
    }

    public static function registrarEntrada($productoId, $ubicacionId, $cantidad, $motivo = null, $datosAdicionales = null)
    {
        if (!static::canLog()) { return null; }
        return static::create([
            'producto_id' => $productoId,
            'ubicacion_destino_id' => $ubicacionId,
            'tipo_movimiento' => 'entrada',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'usuario_id' => auth()->id(),
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    public static function registrarSalida($productoId, $ubicacionId, $cantidad, $motivo = null, $datosAdicionales = null)
    {
        if (!static::canLog()) { return null; }
        return static::create([
            'producto_id' => $productoId,
            'ubicacion_origen_id' => $ubicacionId,
            'tipo_movimiento' => 'salida',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'usuario_id' => auth()->id(),
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    public static function registrarTransferencia($productoId, $ubicacionOrigenId, $ubicacionDestinoId, $cantidad, $motivo = null, $datosAdicionales = null)
    {
        if (!static::canLog()) { return null; }
        return static::create([
            'producto_id' => $productoId,
            'ubicacion_origen_id' => $ubicacionOrigenId,
            'ubicacion_destino_id' => $ubicacionDestinoId,
            'tipo_movimiento' => 'transferencia',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'usuario_id' => auth()->id(),
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    public static function registrarVenta($productoId, $ubicacionId, $cantidad, $motivo = null, $datosAdicionales = null)
    {
        if (!static::canLog()) { return null; }
        return static::create([
            'producto_id' => $productoId,
            'ubicacion_origen_id' => $ubicacionId,
            'tipo_movimiento' => 'venta',
            'cantidad' => $cantidad,
            'motivo' => $motivo ?? 'Venta realizada',
            'usuario_id' => auth()->id(),
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    public static function registrarDevolucion($productoId, $ubicacionId, $cantidad, $motivo = null, $datosAdicionales = null)
    {
        if (!static::canLog()) { return null; }
        return static::create([
            'producto_id' => $productoId,
            'ubicacion_destino_id' => $ubicacionId, // Las devoluciones son entradas, van al destino
            'tipo_movimiento' => 'devolucion',
            'cantidad' => $cantidad,
            'motivo' => $motivo ?? 'Devolución realizada',
            'usuario_id' => auth()->id(),
            'datos_adicionales' => $datosAdicionales
        ]);
    }
}
