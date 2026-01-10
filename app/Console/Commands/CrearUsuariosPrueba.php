<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class CrearUsuariosPrueba extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usuarios:crear-prueba';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear usuarios de prueba con diferentes roles para probar el sistema de permisos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creando usuarios de prueba...');
        $this->newLine();

        $usuarios = [
            [
                'name' => 'María Gerente',
                'email' => 'gerente@farmacia.com',
                'password' => 'password123',
                'rol' => 'gerente',
                'cargo' => 'Gerente General',
                'descripcion' => 'Segundo al mando - Acceso casi total'
            ],
            [
                'name' => 'Luis Administrador',
                'email' => 'administrador@farmacia.com',
                'password' => 'password123',
                'rol' => 'administrador',
                'cargo' => 'Administrador',
                'descripcion' => 'Gestión administrativa sin logs críticos'
            ],
            [
                'name' => 'Carmen Vendedora',
                'email' => 'vendedor@farmacia.com', 
                'password' => 'password123',
                'rol' => 'vendedor',
                'cargo' => 'Vendedora',
                'descripcion' => 'Solo ventas y consultas de productos'
            ],
            [
                'name' => 'Juan Almacenero',
                'email' => 'almacenero@farmacia.com',
                'password' => 'password123',
                'rol' => 'almacenero',
                'cargo' => 'Encargado de Almacén',
                'descripcion' => 'Solo inventario, almacén y compras'
            ],
            [
                'name' => 'Carlos Mixto',
                'email' => 'mixto@farmacia.com',
                'password' => 'password123',
                'rol' => 'vendedor-almacenero',
                'cargo' => 'Vendedor-Almacenero',
                'descripcion' => 'Ventas + inventario + almacén'
            ],
            [
                'name' => 'Ana Supervisora',
                'email' => 'supervisor@farmacia.com',
                'password' => 'password123',
                'rol' => 'supervisor',
                'cargo' => 'Supervisora',
                'descripcion' => 'Supervisión con reportes'
            ]
        ];

        foreach ($usuarios as $userData) {
            // Verificar si el email ya existe
            $existeUsuario = User::where('email', $userData['email'])->first();
            
            if ($existeUsuario) {
                $this->warn("⚠️  Usuario {$userData['email']} ya existe, actualizando rol...");
                $usuario = $existeUsuario;
            } else {
                // Crear nuevo usuario
                $usuario = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'cargo' => $userData['cargo'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $this->info("✅ Usuario {$userData['name']} creado");
            }

            // Verificar si el rol existe
            $rol = Role::where('name', $userData['rol'])->first();
            if (!$rol) {
                $this->error("❌ Rol '{$userData['rol']}' no existe. Ejecuta primero: php artisan db:seed --class=RolesAndPermissionsSeeder");
                continue;
            }

            // Asignar rol
            $usuario->syncRoles([$userData['rol']]);
            $this->info("   🔑 Rol '{$userData['rol']}' asignado - {$userData['descripcion']}");
        }

        $this->newLine();
        $this->info('🎉 ¡Usuarios de prueba creados exitosamente!');
        $this->newLine();
        
        $this->table(
            ['Usuario', 'Email', 'Rol', 'Cargo', 'Password'],
            collect($usuarios)->map(function($user) {
                return [
                    $user['name'],
                    $user['email'], 
                    $user['rol'],
                    $user['cargo'],
                    $user['password']
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info('📋 INSTRUCCIONES PARA PRUEBAS:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('1. Inicia sesión con cualquiera de estos usuarios');
        $this->info('2. Observa cómo cambia el sidebar según el rol');
        $this->info('3. Prueba acceder a secciones sin permisos');
        $this->info('4. Verifica que las rutas estén protegidas');
        $this->newLine();
    }
} 