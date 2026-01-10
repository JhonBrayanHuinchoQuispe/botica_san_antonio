<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConfiguracionAlmacen extends Model
{
    use HasFactory;

    protected $table = 'configuracion_almacen';

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen_fondo',
        'configuraciones',
        'activo'
    ];

    protected $casts = [
        'configuraciones' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Obtener la configuración activa del almacén
     */
    public static function obtenerConfiguracion()
    {
        return self::where('activo', true)->first() ?? self::first();
    }

    /**
     * Obtener la URL completa de la imagen de fondo
     */
    public function getImagenFondoUrlAttribute()
    {
        if (!$this->imagen_fondo) {
            return null;
        }

        // Si ya es una URL completa, retornarla tal como está
        if (str_starts_with($this->imagen_fondo, 'http')) {
            return $this->imagen_fondo;
        }

        // Si es una ruta relativa, construir la URL completa
        return Storage::disk('public')->url($this->imagen_fondo);
    }

    /**
     * Actualizar la imagen de fondo
     */
    public function actualizarImagenFondo($archivo)
    {
        // Eliminar imagen anterior si existe
        if ($this->imagen_fondo && Storage::disk('public')->exists($this->imagen_fondo)) {
            Storage::disk('public')->delete($this->imagen_fondo);
        }

        // Guardar nueva imagen
        $ruta = $archivo->store('almacen', 'public');
        
        // Actualizar el modelo
        $this->update(['imagen_fondo' => $ruta]);

        return $this;
    }
}
