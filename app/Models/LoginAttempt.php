<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at'
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime'
    ];

    /**
     * Registrar un intento de login
     */
    public static function recordAttempt($email, $ipAddress, $userAgent, $successful = false)
    {
        return self::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => now()
        ]);
    }

    /**
     * Obtener intentos fallidos recientes por email
     */
    public static function recentFailedAttemptsByEmail($email, $minutes = 15)
    {
        return self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Obtener intentos fallidos recientes por IP
     */
    public static function recentFailedAttemptsByIp($ipAddress, $minutes = 15)
    {
        return self::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Limpiar intentos antiguos (ejecutar en cron)
     */
    public static function cleanOldAttempts($days = 30)
    {
        return self::where('attempted_at', '<', Carbon::now()->subDays($days))->delete();
    }
}
