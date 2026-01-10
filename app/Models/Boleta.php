<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boleta extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'correlative_id',
        'serie',
        'numero',
        'fecha_emision',
        'hora_emision',
        'tipo_operacion',
        'tipo_documento',
        'tipo_moneda',
        'cliente_tipo_documento',
        'cliente_numero_documento',
        'cliente_razon_social',
        'cliente_direccion',
        'cliente_email',
        'cliente_telefono',
        'total_operaciones_gravadas',
        'total_operaciones_inafectas',
        'total_operaciones_exoneradas',
        'total_operaciones_gratuitas',
        'total_igv',
        'total_impuestos',
        'valor_total',
        'precio_total',
        'total_descuentos',
        'total_cargos',
        'total_valor_venta',
        'total_precio_venta',
        'observaciones',
        'estado',
        'estado_sunat',
        'hash',
        'qr',
        'xml_unsigned',
        'xml_signed',
        'pdf',
        'cdr',
        'codigo_error',
        'mensaje_error',
        'notas',
        'enviado_sunat',
        'fecha_envio_sunat',
        'anulado',
        'fecha_anulacion',
        'motivo_anulacion',
        'resumen_diario_id'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_envio_sunat' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'total_operaciones_gravadas' => 'decimal:2',
        'total_operaciones_inafectas' => 'decimal:2',
        'total_operaciones_exoneradas' => 'decimal:2',
        'total_operaciones_gratuitas' => 'decimal:2',
        'total_igv' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total_cargos' => 'decimal:2',
        'total_valor_venta' => 'decimal:2',
        'total_precio_venta' => 'decimal:2',
        'enviado_sunat' => 'boolean',
        'anulado' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function correlative()
    {
        return $this->belongsTo(Correlative::class);
    }

    public function details()
    {
        return $this->hasMany(BoletaDetail::class);
    }

    public function legends()
    {
        return $this->hasMany(BoletaLegend::class);
    }

    public function resumenDiario()
    {
        return $this->belongsTo(ResumenDiario::class);
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('enviado_sunat', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('enviado_sunat', false);
    }

    public function scopeAnuladas($query)
    {
        return $query->where('anulado', true);
    }

    public function scopeByFecha($query, $fecha)
    {
        return $query->whereDate('fecha_emision', $fecha);
    }

    public function getNumeroCompletoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getEstadoTextoAttribute()
    {
        if ($this->anulado) {
            return 'Anulado';
        }

        if ($this->enviado_sunat) {
            return $this->estado_sunat ?: 'Enviado';
        }

        return 'Pendiente';
    }

    public function getTipoComprobanteAttribute()
    {
        return '03'; // Código SUNAT para boleta
    }
}