<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'codigo',
        'nombre',
        'direccion',
        'ubigeo',
        'departamento',
        'provincia',
        'distrito',
        'urbanizacion',
        'telefono',
        'email',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
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

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
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
}