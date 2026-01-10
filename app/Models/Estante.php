<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Estante extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'capacidad_total',
        'numero_niveles',
        'numero_posiciones',
        'ubicacion_fisica',
        'tipo',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'numero_niveles' => 'integer',
        'numero_posiciones' => 'integer',
        'capacidad_total' => 'integer'
    ];

    // Relaciones
    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class);
    }

    public function productos()
    {
        return $this->hasManyThrough(ProductoUbicacion::class, Ubicacion::class);
    }

    // Métodos útiles
    public function getOcupacionAttribute()
    {
        $totalSlots = $this->ubicaciones()->count();
        $slotsOcupados = $this->ubicaciones()->whereHas('productos')->count();
        
        return $totalSlots > 0 ? ($slotsOcupados / $totalSlots) * 100 : 0;
    }

    public function getSlotsOcupadosAttribute()
    {
        return $this->ubicaciones()->whereHas('productos')->count();
    }

    public function getSlotsDisponiblesAttribute()
    {
        return $this->ubicaciones()->whereDoesntHave('productos')->count();
    }

    public function getTotalProductosAttribute()
    {
        return $this->ubicaciones()->withCount('productos')->get()->sum('productos_count');
    }

    // Crear ubicaciones automáticamente al crear estante
    public static function boot()
    {
        parent::boot();

        static::created(function ($estante) {
            $estante->crearUbicaciones();
        });
    }

    public function crearUbicaciones()
    {
        $niveles = $this->numero_niveles ?: 1;
        $posiciones = $this->numero_posiciones ?: 1;

        for ($nivel = 1; $nivel <= $niveles; $nivel++) {
            for ($posicion = 1; $posicion <= $posiciones; $posicion++) {
                $attrs = [
                    'estante_id' => $this->id,
                    'nivel' => $nivel,
                    'posicion' => $posicion,
                    'codigo' => "{$nivel}-{$posicion}",
                    'activo' => true
                ];
                if (Schema::hasColumn('ubicaciones', 'capacidad_maxima')) {
                    $attrs['capacidad_maxima'] = 1;
                }
                Ubicacion::create($attrs);
            }
        }
    }
}
