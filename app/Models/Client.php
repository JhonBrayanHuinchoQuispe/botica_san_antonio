<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombre_comercial',
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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByTipoDocumento($query, $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    public function scopeByNumeroDocumento($query, $numero)
    {
        return $query->where('numero_documento', $numero);
    }

    // Accessors
    public function getTipoDocumentoTextoAttribute()
    {
        $tipos = [
            '1' => 'DNI',
            '4' => 'Carnet de Extranjería',
            '6' => 'RUC',
            '7' => 'Pasaporte',
            '0' => 'Sin Documento'
        ];

        return $tipos[$this->tipo_documento] ?? 'Desconocido';
    }

    public function getDireccionCompletaAttribute()
    {
        if (!$this->direccion) {
            return '-';
        }

        $direccion = $this->direccion;
        
        if ($this->urbanizacion && $this->urbanizacion !== '-') {
            $direccion .= ', ' . $this->urbanizacion;
        }
        
        if ($this->distrito) {
            $direccion .= ', ' . $this->distrito;
        }
        
        if ($this->provincia) {
            $direccion .= ', ' . $this->provincia;
        }
        
        if ($this->departamento) {
            $direccion .= ', ' . $this->departamento;
        }
        
        return $direccion;
    }

    public function getEsEmpresaAttribute()
    {
        return $this->tipo_documento === '6'; // RUC
    }
}