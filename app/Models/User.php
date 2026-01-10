<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasRoles, Notifiable;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'nombres',
        'apellidos',
        'email',
        'password',
        'telefono',
        'direccion',
        'avatar',
        'notif_email',
        'notif_stock',
        'notif_vencimientos',
        'mostrar_actividad',
        'last_login_at',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'last_failed_login',
        'last_login_ip',
        'force_password_change',
        'reset_code',
        'reset_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_failed_login' => 'datetime',
        'reset_code_expires_at' => 'datetime',
        'notif_email' => 'boolean',
        'notif_stock' => 'boolean',
        'notif_vencimientos' => 'boolean',
        'mostrar_actividad' => 'boolean',
        'is_active' => 'boolean',
        'force_password_change' => 'boolean',
    ];

    // Las relaciones de roles y permisos ya están disponibles a través de Spatie\Permission\Traits\HasRoles

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtener el nombre completo del usuario
     */
    public function getFullNameAttribute()
    {
        if ($this->nombres && $this->apellidos) {
            return $this->nombres . ' ' . $this->apellidos;
        }
        
        return $this->name;
    }

    /**
     * Obtener las iniciales del usuario
     */
    public function getInitialsAttribute()
    {
        if ($this->nombres && $this->apellidos) {
            return strtoupper(substr($this->nombres, 0, 1) . substr($this->apellidos, 0, 1));
        }
        
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Enviar notificación de restablecimiento de contraseña personalizada
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Resolver el usuario para la auditoría (Fix manual)
     */
    protected function resolveUser()
    {
        return \Illuminate\Support\Facades\Auth::user();
    }

    /**
     * Verificar si la cuenta está bloqueada
     */
    public function isLocked()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Incrementar intentos fallidos de login
     */
    public function incrementFailedAttempts()
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($this->getTable());
            if (in_array('failed_login_attempts', $columns)) {
                $this->increment('failed_login_attempts');
            }
            if (in_array('last_failed_login', $columns)) {
                $this->update(['last_failed_login' => now()]);
            }
        } catch (\Throwable $e) {
            \Log::warning('No se pudo incrementar intentos fallidos', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }

        // Bloquear cuenta después de 5 intentos fallidos
        if (isset($this->failed_login_attempts) && $this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }

    /**
     * Bloquear cuenta temporalmente
     */
    public function lockAccount($minutes = 30)
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes)
        ]);
    }

    /**
     * Resetear intentos fallidos después de login exitoso
     */
    public function resetFailedAttempts()
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($this->getTable());
            $data = [];
            if (in_array('failed_login_attempts', $columns)) {
                $data['failed_login_attempts'] = 0;
            }
            if (in_array('locked_until', $columns)) {
                $data['locked_until'] = null;
            }
            if (in_array('last_failed_login', $columns)) {
                $data['last_failed_login'] = null;
            }
            if (!empty($data)) {
                $this->update($data);
            }
        } catch (\Throwable $e) {
            \Log::warning('No se pudo resetear intentos fallidos', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar información de último login
     */
    public function updateLastLogin($ipAddress)
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($this->getTable());
            $data = [];
            if (in_array('last_login_at', $columns)) {
                $data['last_login_at'] = now();
            }
            if (in_array('last_login_ip', $columns)) {
                $data['last_login_ip'] = $ipAddress;
            }
            if (!empty($data)) {
                $this->update($data);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo actualizar último login', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener tiempo restante de bloqueo en minutos
     */
    public function getLockTimeRemaining()
    {
        if (!$this->isLocked()) {
            return 0;
        }

        return max(1, ceil(now()->diffInMinutes($this->locked_until, false)));
    }

    /**
     * Obtener tiempo restante de bloqueo en formato legible
     */
    public function getLockTimeRemainingFormatted()
    {
        if (!$this->isLocked()) {
            return null;
        }

        $totalMinutes = $this->getLockTimeRemaining();
        
        if ($totalMinutes < 1) {
            return 'menos de 1 minuto';
        }
        
        if ($totalMinutes == 1) {
            return '1 minuto';
        }
        
        if ($totalMinutes < 60) {
            return $totalMinutes . ' minutos';
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        if ($hours == 1 && $minutes == 0) {
            return '1 hora';
        }
        
        if ($hours == 1) {
            return "1 hora y $minutes minutos";
        }
        
        if ($minutes == 0) {
            return "$hours horas";
        }
        
        return "$hours horas y $minutes minutos";
    }

    /**
     * Obtener la URL del avatar del usuario
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            $rel = ltrim($this->avatar, '/');
            if (\Storage::disk('public')->exists($rel)) {
                return \Storage::disk('public')->url($rel);
            }
            $publicPath = public_path($rel);
            if (file_exists($publicPath)) {
                return asset($rel);
            }
            if (str_starts_with($rel, 'storage/')) {
                $stripped = substr($rel, strlen('storage/'));
                $publicStripped = public_path($stripped);
                if (file_exists($publicStripped)) {
                    return asset($stripped);
                }
                if (Storage::disk('public')->exists($stripped)) {
                    return asset('storage/' . $stripped);
                }
            }
            // Caso 3: si avatar ya es una URL absoluta
            if (preg_match('/^https?:\/\//i', $this->avatar)) {
                return $this->avatar;
            }
        }
        return asset('assets/images/avatar/avatar1.png');
    }

    /**
     * Helper methods para verificar permisos del usuario
     */
    
    /**
     * Verificar si el usuario puede acceder al dashboard
     */
    public function canViewDashboard()
    {
        return $this->can('dashboard.view');
    }

    /**
     * Verificar si el usuario puede acceder a ventas
     */
    public function canAccessVentas()
    {
        return $this->can('ventas.view') || $this->can('ventas.create');
    }

    /**
     * Verificar si el usuario puede acceder al inventario
     */
    public function canAccessInventario()
    {
        return $this->can('inventario.view') || $this->can('inventario.create') || $this->can('inventario.edit');
    }

    /**
     * Verificar si el usuario puede acceder a compras
     */
    public function canAccessCompras()
    {
        return $this->can('compras.view') || $this->can('compras.create');
    }

    /**
     * Verificar si el usuario puede acceder a usuarios y roles
     */
    public function canAccessUsuarios()
    {
        return $this->can('usuarios.view') || $this->can('usuarios.create') || $this->can('usuarios.edit');
    }

    /**
     * Verificar si el usuario puede acceder a la configuración del sistema
     */
    public function canAccessConfiguracion()
    {
        return $this->can('config.system') || $this->can('config.backups') || $this->can('config.logs');
    }

    /**
     * Verificar si el usuario puede acceder a ubicaciones/almacén
     */
    public function canAccessUbicaciones()
    {
        return $this->can('ubicaciones.view') || $this->can('ubicaciones.create') || $this->can('ubicaciones.edit');
    }

    /**
     * Verificar si es dueño (máximo nivel)
     */
    public function isDueno()
    {
        return $this->hasRole('dueño');
    }

    /**
     * Verificar si es gerente
     */
    public function isGerente()
    {
        return $this->hasRole('gerente');
    }

    /**
     * Verificar si es administrador
     */
    public function isAdministrador()
    {
        return $this->hasRole('administrador');
    }

    /**
     * Verificar si tiene roles de alta gerencia (dueño, gerente, admin)
     */
    public function isHighLevel()
    {
        return $this->hasRole(['dueño', 'gerente', 'administrador']);
    }

    /**
     * Verificar si es vendedor
     */
    public function isVendedor()
    {
        return $this->hasRole('vendedor');
    }

    /**
     * Verificar si es vendedor-almacenero
     */
    public function isVendedorAlmacenero()
    {
        return $this->hasRole('vendedor-almacenero');
    }

    /**
     * Verificar si es almacenero
     */
    public function isAlmacenero()
    {
        return $this->hasRole('almacenero');
    }

    /**
     * Verificar si es supervisor
     */
    public function isSupervisor()
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Obtener los permisos del usuario en formato legible
     */
    public function getPermissionsListAttribute()
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Obtener los roles del usuario en formato legible
     */
    public function getRolesListAttribute()
    {
        return $this->getRoleNames()->toArray();
    }

    /**
     * Suscripciones Web Push del usuario
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(\App\Models\PushSubscription::class);
    }
}
