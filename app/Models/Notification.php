<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'priority',
        'user_id',
        'read_at',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Tipos de notificación
    const TYPE_STOCK_CRITICO = 'stock_critico';
    const TYPE_STOCK_AGOTADO = 'stock_agotado';
    const TYPE_PRODUCTO_VENCIMIENTO = 'producto_vencimiento';
    const TYPE_PRODUCTO_VENCIDO = 'producto_vencido';

    // Prioridades
    const PRIORITY_URGENTE = 'urgente';
    const PRIORITY_ADVERTENCIA = 'advertencia';
    const PRIORITY_INFO = 'info';

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope por prioridad
     */
    public function scopeOfPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Verificar si está leída
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Verificar si no está leída
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Obtener el tiempo transcurrido desde la creación
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Crear notificación de stock crítico
     */
    public static function createStockCritico($userId, $producto)
    {
        return self::create([
            'type' => self::TYPE_STOCK_CRITICO,
            'title' => 'Stock Crítico',
            'message' => "{$producto->nombre} - Solo quedan {$producto->stock_actual} unidades",
            'priority' => self::PRIORITY_URGENTE,
            'user_id' => $userId,
            'data' => [
                'producto_id' => $producto->id,
                'stock_actual' => $producto->stock_actual,
                'stock_minimo' => $producto->stock_minimo
            ]
        ]);
    }

    /**
     * Crear notificación de stock agotado
     */
    public static function createStockAgotado($userId, $producto)
    {
        return self::create([
            'type' => self::TYPE_STOCK_AGOTADO,
            'title' => 'Producto Agotado',
            'message' => "{$producto->nombre} - Sin stock disponible",
            'priority' => self::PRIORITY_URGENTE,
            'user_id' => $userId,
            'data' => [
                'producto_id' => $producto->id,
                'stock_actual' => $producto->stock_actual,
                'stock_minimo' => $producto->stock_minimo
            ]
        ]);
    }

    /**
     * Crear notificación de producto próximo a vencer
     */
    public static function createProximoVencer($userId, $producto, $diasRestantes, $loteId = null)
    {
        // Asegurar que los días sean un entero y formatear el mensaje correctamente
        $dias = is_numeric($diasRestantes) ? (int) round($diasRestantes) : (int) $diasRestantes;
        if ($dias < 0) {
            // En caso de que llegue negativo por alguna razón externa, delegar al flujo de vencido
            return self::createProductoVencido($userId, $producto, $loteId);
        }

        $mensajeDias = $dias === 0
            ? 'vence hoy'
            : ($dias === 1 ? 'vence en 1 día' : "vence en {$dias} días");

        return self::create([
            'type' => self::TYPE_PRODUCTO_VENCIMIENTO,
            'title' => 'Próximo a Vencer',
            'message' => "{$producto->nombre} {$mensajeDias}",
            'priority' => self::PRIORITY_ADVERTENCIA,
            'user_id' => $userId,
            'data' => [
                'producto_id' => $producto->id,
                'lote_id' => $loteId,
                'fecha_vencimiento' => $producto->fecha_vencimiento,
                'dias_restantes' => $dias
            ]
        ]);
    }

    /**
     * Crear notificación de producto vencido
     */
    public static function createProductoVencido($userId, $producto, $loteId = null)
    {
        return self::create([
            'type' => self::TYPE_PRODUCTO_VENCIDO,
            'title' => 'Producto Vencido',
            'message' => "{$producto->nombre} ya está vencido",
            'priority' => self::PRIORITY_URGENTE,
            'user_id' => $userId,
            'data' => [
                'producto_id' => $producto->id,
                'lote_id' => $loteId,
                'fecha_vencimiento' => $producto->fecha_vencimiento
            ]
        ]);
    }
}
