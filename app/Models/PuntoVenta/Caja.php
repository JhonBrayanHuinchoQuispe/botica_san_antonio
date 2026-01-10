<?php

namespace App\Models\PuntoVenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'caja';

    protected $fillable = [
        'usuario_id',
        'monto_inicial',
        'monto_actual',
        'total_ventas',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'monto_actual' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_apertura', today());
    }

    // Accessors
    public function getEstadoFormateadoAttribute()
    {
        return $this->estado === 'abierta' ? 'Abierta' : 'Cerrada';
    }

    public function getDiferenciaAttribute()
    {
        return $this->monto_actual - ($this->monto_inicial + $this->total_ventas);
    }

    // Métodos
    public function registrarVenta($monto)
    {
        $this->increment('total_ventas', $monto);
        $this->increment('monto_actual', $monto);
        
        return $this;
    }

    public function cerrar($montoFinal = null, $observaciones = null)
    {
        $this->update([
            'estado' => 'cerrada',
            'fecha_cierre' => now(),
            'monto_actual' => $montoFinal ?? $this->monto_actual,
            'observaciones' => $observaciones
        ]);

        return $this;
    }

    public static function abrirCaja($usuarioId, $montoInicial, $observaciones = null)
    {
        // Cerrar cualquier caja abierta del usuario
        self::where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->update([
                'estado' => 'cerrada',
                'fecha_cierre' => now()
            ]);

        return self::create([
            'usuario_id' => $usuarioId,
            'monto_inicial' => $montoInicial,
            'monto_actual' => $montoInicial,
            'fecha_apertura' => now(),
            'estado' => 'abierta',
            'observaciones' => $observaciones
        ]);
    }

    public static function cajaAbiertaDelUsuario($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)
                   ->where('estado', 'abierta')
                   ->first();
    }
} 