<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correlative extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'tipo_documento',
        'serie',
        'numero_actual',
        'numero_maximo',
        'activo'
    ];

    protected $casts = [
        'numero_actual' => 'integer',
        'numero_maximo' => 'integer',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByTipoDocumento($query, $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    public function scopeBySerie($query, $serie)
    {
        return $query->where('serie', $serie);
    }

    // Métodos
    public function getSiguienteNumero()
    {
        if ($this->numero_actual >= $this->numero_maximo) {
            throw new \Exception('Se ha alcanzado el número máximo para la serie ' . $this->serie);
        }

        $this->increment('numero_actual');
        
        return $this->numero_actual;
    }

    public function getNumeroActualFormateado()
    {
        return str_pad($this->numero_actual, 8, '0', STR_PAD_LEFT);
    }

    public function getSiguienteNumeroFormateado()
    {
        $siguiente = $this->numero_actual + 1;
        return str_pad($siguiente, 8, '0', STR_PAD_LEFT);
    }

    // Accessors
    public function getTipoDocumentoTextoAttribute()
    {
        $tipos = [
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de Crédito',
            '08' => 'Nota de Débito',
            '09' => 'Guía de Remisión',
            '20' => 'Comprobante de Retención',
            '40' => 'Comprobante de Percepción'
        ];

        return $tipos[$this->tipo_documento] ?? 'Desconocido';
    }

    public function getDisponiblesAttribute()
    {
        return $this->numero_maximo - $this->numero_actual;
    }

    public function getPorcentajeUsoAttribute()
    {
        if ($this->numero_maximo == 0) {
            return 0;
        }

        return round(($this->numero_actual / $this->numero_maximo) * 100, 2);
    }
}