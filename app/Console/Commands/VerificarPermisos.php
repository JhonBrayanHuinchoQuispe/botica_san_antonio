<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class VerificarPermisos extends Command
{
    protected $signature = 'permisos:verificar {--user=1}';
    protected $description = 'Verificar permisos de un usuario';

    public function handle()
    {
        $userId = $this->option('user');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuario con ID $userId no encontrado");
            return;
        }

        $this->info("=== VERIFICACIÓN DE PERMISOS ===");
        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->info("Roles: " . $user->getRoleNames()->implode(', '));
        $this->info("Total de permisos: " . $user->getAllPermissions()->count());
        
        $this->newLine();
        $this->info("Permisos específicos:");
        foreach ($user->getAllPermissions() as $permission) {
            $this->info("  ✓ {$permission->name}");
        }

        $this->newLine();
        $this->info("Helper methods:");
        $this->info("  canViewDashboard: " . ($user->canViewDashboard() ? 'SÍ' : 'NO'));
        $this->info("  canAccessVentas: " . ($user->canAccessVentas() ? 'SÍ' : 'NO'));
        $this->info("  canAccessInventario: " . ($user->canAccessInventario() ? 'SÍ' : 'NO'));
    }
} 