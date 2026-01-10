<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Categoria extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'nombre', 'descripcion', 'estado', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'estado' => 'string',
    ];

    // Incluir 'activo' en conversiones a array/JSON para la UI
    protected $appends = ['activo'];

    // Scope para obtener solo categorías activas
    public function scopeActivas($query)
    {
        // Usar columna booleana 'activo' si existe; compatibilidad con 'estado'
        if (array_key_exists('activo', $this->getAttributes())) {
            return $query->where('activo', true);
        }
        return $query->where('estado', 'activo');
    }

    public function productos()
    {
        // Relación por nombre de categoría (productos.categoria -> categorias.nombre)
        return $this->hasMany(Producto::class, 'categoria', 'nombre');
    }

    // Compatibilidad: prop "activo" derivada del campo "estado" (string)
    public function getActivoAttribute()
    {
        // Si la columna 'activo' existe en atributos, usarla directamente
        if (array_key_exists('activo', $this->attributes)) {
            return (bool) $this->attributes['activo'];
        }
        // Compatibilidad: derivar de 'estado' string
        return ($this->estado === 'activo');
    }

    /**
     * Resolver el usuario para la auditoría (Fix manual)
     */
    protected function resolveUser()
    {
        return \Illuminate\Support\Facades\Auth::user();
    }
}