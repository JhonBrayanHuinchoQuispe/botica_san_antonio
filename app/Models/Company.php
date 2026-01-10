<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'departamento',
        'provincia',
        'distrito',
        'urbanizacion',
        'codigo_pais',
        'telefono',
        'email',
        'web',
        'logo',
        'certificado_path',
        'certificado_password',
        'usuario_sol',
        'clave_sol',
        'modo_prueba',
        'activo'
    ];

    protected $casts = [
        'modo_prueba' => 'boolean',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    public function correlatives()
    {
        return $this->hasMany(Correlative::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByRuc($query, $ruc)
    {
        return $query->where('ruc', $ruc);
    }

    // Accessors
    public function getDireccionCompletaAttribute()
    {
        $direccion = $this->direccion;
        
        if ($this->urbanizacion && $this->urbanizacion !== '-') {
            $direccion .= ', ' . $this->urbanizacion;
        }
        
        $direccion .= ', ' . $this->distrito . ', ' . $this->provincia . ', ' . $this->departamento;
        
        return $direccion;
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        
        return null;
    }
}