<?php

namespace App\Models\PuntoVenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Producto;

class VentaDevolucion extends Model
{
    use HasFactory;

    protected $table = 'venta_devoluciones';

    protected $fillable = [
        'venta_id',
        'venta_detalle_id',
        'producto_id',
        'usuario_id',
        'cantidad_original',
        'cantidad_devuelta',
        'precio_unitario',
        'monto_devolucion',
        'motivo',
        'observaciones',
        'fecha_devolucion'
    ];

    protected $casts = [
        'cantidad_original' => 'integer',
        'cantidad_devuelta' => 'integer',
        'precio_unitario' => 'decimal:2',
        'monto_devolucion' => 'decimal:2',
        'fecha_devolucion' => 'datetime',
    ];

    // Relaciones
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function ventaDetalle()
    {
        return $this->belongsTo(VentaDetalle::class, 'venta_detalle_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_devolucion', $fecha);
    }

    public function scopePorVenta($query, $ventaId)
    {
        return $query->where('venta_id', $ventaId);
    }

    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_devolucion', [$fechaInicio, $fechaFin]);
    }

    // Accessors
    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_devolucion->format('d/m/Y g:i A');
    }

    public function getPorcentajeDevueltoAttribute()
    {
        if ($this->cantidad_original <= 0) {
            return 0;
        }
        return round(($this->cantidad_devuelta / $this->cantidad_original) * 100, 1);
    }

    public function getEsDevolucionCompletaAttribute()
    {
        return $this->cantidad_devuelta >= $this->cantidad_original;
    }

    public function getMotivoFormateadoAttribute()
    {
        $motivos = [
            'defectuoso' => 'Producto Defectuoso',
            'vencido' => 'Producto Vencido',
            'equivocacion' => 'Error en la Venta',
            'cliente_insatisfecho' => 'Cliente Insatisfecho',
            'cambio_opinion' => 'Cambio de Opinión',
            'otro' => 'Otro Motivo'
        ];

        return $motivos[$this->motivo] ?? ucfirst($this->motivo);
    }

    // Métodos estáticos
    public static function calcularEstadisticasPorVenta($ventaId)
    {
        $devoluciones = self::where('venta_id', $ventaId)->get();
        
        return [
            'total_devoluciones' => $devoluciones->count(),
            'monto_total_devuelto' => $devoluciones->sum('monto_devolucion'),
            'productos_afectados' => $devoluciones->unique('producto_id')->count(),
            'fecha_primera_devolucion' => $devoluciones->min('fecha_devolucion'),
            'fecha_ultima_devolucion' => $devoluciones->max('fecha_devolucion')
        ];
    }

    public static function obtenerEstadisticasGenerales($fechaInicio = null, $fechaFin = null)
    {
        $query = self::query();
        
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_devolucion', [$fechaInicio, $fechaFin]);
        }
        
        $devoluciones = $query->get();
        
        return [
            'total_devoluciones' => $devoluciones->count(),
            'monto_total_devuelto' => $devoluciones->sum('monto_devolucion'),
            'productos_mas_devueltos' => $devoluciones->groupBy('producto_id')
                ->map(function ($group) {
                    return [
                        'producto' => $group->first()->producto,
                        'cantidad_devoluciones' => $group->count(),
                        'cantidad_total_devuelta' => $group->sum('cantidad_devuelta'),
                        'monto_total' => $group->sum('monto_devolucion')
                    ];
                })
                ->sortByDesc('cantidad_devoluciones')
                ->take(10),
            'motivos_frecuentes' => $devoluciones->groupBy('motivo')
                ->map(function ($group, $motivo) {
                    return [
                        'motivo' => $motivo,
                        'cantidad' => $group->count(),
                        'porcentaje' => 0 // Se calculará después
                    ];
                })
                ->sortByDesc('cantidad')
        ];
    }
}