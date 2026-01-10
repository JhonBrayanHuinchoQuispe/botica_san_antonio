<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'used',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Generar un código de 6 dígitos único
     */
    public static function generateCode()
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->where('used', false)->where('expires_at', '>', now())->exists());

        return $code;
    }

    /**
     * Crear un nuevo código de reset para un email
     */
    public static function createForEmail($email, $ipAddress = null, $userAgent = null)
    {
        // Invalidar códigos anteriores no usados para este email
        self::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        // Crear nuevo código
        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'expires_at' => now()->addMinutes(5), // Expira en 5 minutos
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * Verificar si un código es válido
     */
    public static function isValidCode($email, $code)
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Marcar un código como usado (método estático)
     */
    public static function markAsUsed($email, $code)
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->update(['used' => true]);
    }

    /**
     * Marcar este código como usado (método de instancia)
     */
    public function markThisAsUsed()
    {
        $this->used = true;
        return $this->save();
    }

    /**
     * Obtener un código válido
     */
    public static function getValidCode($email, $code)
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Validar un código y retornarlo si es válido
     */
    public static function validateCode($email, $code)
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Limpiar códigos expirados (para ejecutar en cron job)
     */
    public static function cleanExpiredCodes()
    {
        return self::where('expires_at', '<', now()->subHours(24))->delete();
    }

    /**
     * Limpiar códigos expirados (alias para compatibilidad)
     */
    public static function cleanExpired()
    {
        return self::cleanExpiredCodes();
    }

    /**
     * Verificar si el código está expirado
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Obtener tiempo restante en minutos
     */
    public function getTimeRemainingMinutes()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(1, $this->expires_at->diffInMinutes(now()));
    }

    /**
     * Scope para códigos válidos
     */
    public function scopeValid($query)
    {
        return $query->where('used', false)->where('expires_at', '>', now());
    }

    /**
     * Scope para códigos de un email específico
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }
}
