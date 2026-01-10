<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'modulo',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relación con roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Scope para permisos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para permisos por módulo
     */
    public function scopeByModule($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    /**
     * Obtener la cantidad de roles con este permiso
     */
    public function getRolesCountAttribute()
    {
        return $this->roles()->count();
    }

    /**
     * Obtener todos los módulos únicos
     */
    public static function getModules()
    {
        return self::select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');
    }
}
