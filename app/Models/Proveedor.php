<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'codigo_proveedor',
        'razon_social',
        'nombre_comercial',
        'ruc',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'departamento',
        'contacto_principal',
        'telefono_contacto',
        'email_contacto',
        'estado',
        'observaciones',
        'limite_credito',
        'dias_credito',
        'categoria_proveedor'
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'dias_credito' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relación con compras/entradas de mercadería
    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    // Scope para proveedores activos
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // Scope para búsqueda
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('razon_social', 'LIKE', "%{$termino}%")
              ->orWhere('nombre_comercial', 'LIKE', "%{$termino}%")
              ->orWhere('ruc', 'LIKE', "%{$termino}%")
              ->orWhere('contacto_principal', 'LIKE', "%{$termino}%");
        });
    }

    // Accessor para mostrar información completa
    public function getNombreCompletoAttribute()
    {
        $nombre = $this->razon_social;
        if ($this->ruc) {
            $nombre .= " (RUC: {$this->ruc})";
        }
        return $nombre;
    }

    // Accessor para estado
    public function getEstadoTextoAttribute()
    {
        return $this->estado === 'activo' ? 'Activo' : 'Inactivo';
    }

    // Accessor para nombre (compatibilidad)
    public function getNombreAttribute()
    {
        return $this->razon_social;
    }

    // Accessor para activo (compatibilidad)
    public function getActivoAttribute()
    {
        return $this->estado === 'activo';
    }

    // Método para contar compras
    public function getTotalComprasAttribute()
    {
        return $this->compras()->count();
    }
}
