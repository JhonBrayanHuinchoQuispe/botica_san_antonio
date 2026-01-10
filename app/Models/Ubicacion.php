<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'estante_id',
        'nivel',
        'posicion',
        'codigo',
        'capacidad_maxima',
        'activo',
        'es_fusionado',
        'tipo_fusion',
        'slots_ocupados',
        'fusion_principal_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'nivel' => 'integer',
        'posicion' => 'integer',
        'capacidad_maxima' => 'integer',
        'es_fusionado' => 'boolean',
        'slots_ocupados' => 'integer',
        'fusion_principal_id' => 'integer'
    ];

    // Relaciones
    public function estante()
    {
        return $this->belongsTo(Estante::class);
    }

    public function productos()
    {
        return $this->hasMany(ProductoUbicacion::class);
    }

    public function movimientosOrigen()
    {
        return $this->hasMany(MovimientoStock::class, 'ubicacion_origen_id');
    }

    public function movimientosDestino()
    {
        return $this->hasMany(MovimientoStock::class, 'ubicacion_destino_id');
    }

    // Métodos útiles
    public function getCodigoCompletoAttribute()
    {
        return $this->estante->nombre . ' - ' . $this->codigo;
    }

    public function getEstaOcupadaAttribute()
    {
        return $this->productos()->sum('cantidad') > 0;
    }

    public function getCantidadTotalAttribute()
    {
        return $this->productos()->sum('cantidad');
    }

    public function getEspacioDisponibleAttribute()
    {
        return $this->capacidad_maxima - $this->cantidad_total;
    }

    public function getProductoPrincipalAttribute()
    {
        return $this->productos()->with('producto')->orderBy('cantidad', 'desc')->first();
    }

    // Scopes
    public function scopeOcupadas($query)
    {
        return $query->whereHas('productos');
    }

    public function scopeLibres($query)
    {
        return $query->whereDoesntHave('productos');
    }

    public function scopeDelEstante($query, $estanteId)
    {
        return $query->where('estante_id', $estanteId);
    }

    public function scopeDelNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }
}
