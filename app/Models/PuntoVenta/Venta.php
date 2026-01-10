<?php

namespace App\Models\PuntoVenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Producto;
use App\Models\PuntoVenta\VentaDevolucion;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'usuario_id',
        'tipo_comprobante',
        'subtotal',
        'igv',
        'total',
        'descuento_porcentaje',
        'descuento_monto',
        'descuento_tipo',
        'descuento_razon',
        'descuento_autorizado',
        'descuento_autorizado_por',
        'igv_incluido',
        'monto_gravado',
        'monto_exonerado',
        'monto_inafecto',
        'monto_gratuito',
        'metodo_pago',
        'efectivo_recibido',
        'vuelto',
        'estado',
        'observaciones',
        'fecha_venta',
        'cliente_razon_social'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_gravado' => 'decimal:2',
        'monto_exonerado' => 'decimal:2',
        'monto_inafecto' => 'decimal:2',
        'monto_gratuito' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'vuelto' => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    protected $attributes = [
        'subtotal' => 0.00,
        'igv' => 0.00,
        'total' => 0.00,
        'monto_gravado' => 0.00,
        'monto_exonerado' => 0.00,
        'monto_inafecto' => 0.00,
        'monto_gratuito' => 0.00,
        'efectivo_recibido' => 0.00,
        'vuelto' => 0.00,
        'estado' => 'pendiente',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function devoluciones()
    {
        return $this->hasMany(VentaDevolucion::class);
    }

    // Scopes
    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['completada', 'parcialmente_devuelta']);
    }

    public function scopeDevueltas($query)
    {
        return $query->where('estado', 'devuelta');
    }

    public function scopeParcialmenteDevueltas($query)
    {
        return $query->where('estado', 'parcialmente_devuelta');
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_venta', $fecha);
    }

    public function scopePorMetodoPago($query, $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    // Accessors
    public function getTipoComprobanteFormateadoAttribute()
    {
        return ucfirst($this->tipo_comprobante);
    }

    public function getMetodoPagoFormateadoAttribute()
    {
        $metodos = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'yape' => 'Yape'
        ];

        return $metodos[$this->metodo_pago] ?? $this->metodo_pago;
    }

    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            'devuelta' => 'Devuelta',
            'parcialmente_devuelta' => 'Parcialmente Devuelta'
        ];

        return $estados[$this->estado] ?? $this->estado;
    }

    // Métodos
    public function calcularTotales()
    {
        $subtotal = $this->detalles->sum('subtotal');
        $igv = $subtotal * 0.18;
        $total = $subtotal + $igv;

        $this->update([
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total
        ]);

        return $this;
    }

    public function calcularVuelto()
    {
        if ($this->metodo_pago === 'efectivo' && $this->efectivo_recibido) {
            $vuelto = $this->efectivo_recibido - $this->total;
            $this->update(['vuelto' => max(0, $vuelto)]);
        }

        return $this;
    }

    public static function generarNumeroVenta()
    {
        $fecha = now()->format('Ymd');
        $ultimo = self::whereDate('created_at', today())
                     ->orderBy('id', 'desc')
                     ->first();

        $secuencial = $ultimo ? intval(substr($ultimo->numero_venta, -4)) + 1 : 1;

        return $fecha . str_pad($secuencial, 4, '0', STR_PAD_LEFT);
    }

    public function completar()
    {
        $this->update([
            'estado' => 'completada',
            'fecha_venta' => now()
        ]);

        return $this;
    }

    public function verificarSiDevueltaCompleta()
    {
        // Obtener todos los detalles actualizados
        $this->refresh();
        $detalles = $this->detalles()->get();
        
        if ($detalles->isEmpty()) {
            return false;
        }
        
        // Obtener todas las devoluciones para esta venta
        $devoluciones = $this->devoluciones()->get();
        
        if ($devoluciones->isEmpty()) {
            // No hay devoluciones, asegurar estado completada
            if ($this->estado !== 'completada') {
                $this->update(['estado' => 'completada']);
            }
            return false;
        }
        
        // Verificar el estado de cada producto
        $todosDevueltos = true;
        $algunoDevuelto = false;
        
        foreach ($detalles as $detalle) {
            // Sumar todas las devoluciones para este detalle específico
            $totalDevuelto = $devoluciones
                ->where('venta_detalle_id', $detalle->id)
                ->sum('cantidad_devuelta');
            
            if ($totalDevuelto > 0) {
                $algunoDevuelto = true;
                
                // Si no se devolvió todo de este producto
                if ($totalDevuelto < $detalle->cantidad) {
                    $todosDevueltos = false;
                }
            } else {
                // Este producto no tiene devoluciones
                $todosDevueltos = false;
            }
        }
        
        $estadoAnterior = $this->estado;
        
        // Determinar el nuevo estado
        if ($todosDevueltos && $algunoDevuelto) {
            // Todos los productos fueron devueltos completamente
            $this->update(['estado' => 'devuelta']);
            \Log::info("Venta {$this->numero_venta} marcada como devuelta completamente");
            return true;
        } elseif ($algunoDevuelto && !$todosDevueltos) {
            // Algunos productos fueron devueltos pero no todos
            $this->update(['estado' => 'parcialmente_devuelta']);
            \Log::info("Venta {$this->numero_venta} marcada como parcialmente devuelta");
            return false;
        } else {
            // No hay devoluciones válidas o algún error
            if ($estadoAnterior !== 'completada') {
                $this->update(['estado' => 'completada']);
                \Log::info("Venta {$this->numero_venta} regresada a estado completada");
            }
            return false;
        }
    }

    public function getFechaFormateada12HAttribute()
    {
        $fecha = $this->fecha_venta ?: $this->created_at;
        return $fecha ? $fecha->format('d/m/Y g:i A') : null;
    }

    public function getTipoComprobanteTextoAttribute()
    {
        $tipos = [
            'boleta' => 'Boleta Electrónica',
            'ticket' => 'Sin Comprobante'
        ];

        return $tipos[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    // Métodos para devoluciones
    public function getTieneDevolucionesAttribute()
    {
        return (bool) $this->devoluciones()->exists();
    }

    public function getMontoTotalDevueltoAttribute()
    {
        $montoDevuelto = $this->devoluciones()->sum('monto_devolucion') ?: 0;
        
        // Si la venta está completamente devuelta, asegurar que el monto devuelto no exceda el total original
        if ($this->estado === 'devuelta' && $montoDevuelto > $this->total) {
            return (float) $this->total;
        }
        
        return (float) $montoDevuelto;
    }

    public function getCantidadProductosDevueltosAttribute()
    {
        return $this->devoluciones()->sum('cantidad_devuelta');
    }

    public function getProductosAfectadosPorDevolucionAttribute()
    {
        return $this->devoluciones()->distinct('producto_id')->count();
    }

    public function getDetallesConDevolucionAttribute()
    {
        return $this->detalles->map(function ($detalle) {
            $devolucionesTotales = $this->devoluciones()
                ->where('venta_detalle_id', $detalle->id)
                ->sum('cantidad_devuelta');
            
            $detalle->cantidad_devuelta = $devolucionesTotales;
            $detalle->cantidad_restante = $detalle->cantidad - $devolucionesTotales;
            $detalle->tiene_devolucion = $devolucionesTotales > 0;
            $detalle->devolucion_completa = $devolucionesTotales >= $detalle->cantidad;
            
            return $detalle;
        });
    }

    public function getResumenDevolucionAttribute()
    {
        $devoluciones = $this->devoluciones;
        $detalles = $this->detalles;
        
        if ($devoluciones->isEmpty()) {
            return [
                'tiene_devoluciones' => false,
                'monto_devuelto' => 0,
                'productos_con_devolucion' => 0,
                'total_productos_originales' => $detalles->count(),
                'detalles_afectados' => []
            ];
        }
        
        $productosAfectados = $devoluciones->groupBy('venta_detalle_id');
        $montoTotalDevuelto = $devoluciones->sum('monto_devolucion');
        
        return [
            'tiene_devoluciones' => true,
            'monto_devuelto' => $montoTotalDevuelto,
            'productos_con_devolucion' => $productosAfectados->count(),
            'total_productos_originales' => $detalles->count(),
            'detalles_afectados' => $productosAfectados->keys()->toArray()
        ];
    }
    
    // Accessor para el total actual después de devoluciones
    public function getTotalActualAttribute()
    {
        // Si la venta está completamente devuelta, el total actual es 0
        if ($this->estado === 'devuelta') {
            return 0.00;
        }
        
        $montoDevuelto = $this->devoluciones()->sum('monto_devolucion') ?: 0;
        $total = $this->total ?: 0;
        $totalActual = $total - $montoDevuelto;
        
        // Asegurar que nunca sea negativo y redondear a 2 decimales
        return round(max(0, $totalActual), 2);
    }
    
    // Método para actualizar los totales de la venta
    public function actualizarTotales()
    {
        // Recalcular el subtotal actual
        $subtotalActual = 0;
        $ivaActual = 0;
        
        foreach ($this->detalles as $detalle) {
            // Calcular cantidad actual (original - devuelta)
            $cantidadDevuelta = $this->devoluciones()
                ->where('venta_detalle_id', $detalle->id)
                ->sum('cantidad_devuelta');
            
            $cantidadActual = $detalle->cantidad - $cantidadDevuelta;
            $subtotalDetalle = $cantidadActual * $detalle->precio_unitario;
            $subtotalActual += $subtotalDetalle;
        }
        
        // Calcular IVA si aplica
        if ($this->iva > 0) {
            $ivaActual = $subtotalActual * ($this->iva / ($this->subtotal ?: 1));
        }
        
        $totalActual = $subtotalActual + $ivaActual;
        
        // No actualizamos los campos originales, mantenemos registro histórico
        // Los totales actuales se obtienen vía el accessor getTotalActualAttribute
        
        \Log::info("Totales actualizados para venta {$this->numero_venta}:", [
            'subtotal_original' => $this->subtotal,
            'subtotal_actual' => $subtotalActual,
            'total_original' => $this->total,
            'total_actual' => $totalActual,
            'monto_devuelto' => $this->devoluciones()->sum('monto_devolucion')
        ]);
        
        return [
            'subtotal_actual' => (float) $subtotalActual,
            'iva_actual' => (float) $ivaActual,
            'total_actual' => (float) $totalActual,
            'monto_devuelto' => (float) ($this->devoluciones()->sum('monto_devolucion') ?: 0)
        ];
    }
}