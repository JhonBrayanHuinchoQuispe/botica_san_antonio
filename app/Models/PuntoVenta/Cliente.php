<?php

namespace App\Models\PuntoVenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'dni',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'email',
        'direccion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = ['nombre_completo'];

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellido_paterno . ' ' . $this->apellido_materno);
    }

    // Relaciones
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscarPorDni($query, $dni)
    {
        return $query->where('dni', $dni);
    }

    public function scopeBuscarPorNombre($query, $nombre)
    {
        return $query->where(function($q) use ($nombre) {
            $q->where('nombres', 'like', "%{$nombre}%")
              ->orWhere('apellido_paterno', 'like', "%{$nombre}%")
              ->orWhere('apellido_materno', 'like', "%{$nombre}%");
        });
    }

    // Métodos estáticos
    public static function crearDesdeApi($datosApi)
    {
        return self::updateOrCreate(
            ['dni' => $datosApi['dni']],
            [
                'nombres' => $datosApi['nombres'],
                'apellido_paterno' => $datosApi['apellidoPaterno'],
                'apellido_materno' => $datosApi['apellidoMaterno'],
                'activo' => true
            ]
        );
    }
} 