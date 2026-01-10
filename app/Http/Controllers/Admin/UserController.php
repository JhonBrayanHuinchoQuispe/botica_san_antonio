<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Notifications\WelcomeUserNotification;

class UserController extends Controller
{
    /**
     * Mostrar lista de usuarios
     */
    public function index()
    {
        $usuarios = User::with('roles')->latest()->get();
        $roles = Role::all();
        
        return view('admin.usuarios.index', compact('usuarios', 'roles'));
    }

    /**
     * Mostrar formulario para crear usuario
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.usuarios.crear', compact('roles'));
    }

    /**
     * Almacenar nuevo usuario
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name'
        ]);

        // Crear nombre completo
        $nombreCompleto = trim($request->nombres . ' ' . $request->apellidos);

        // Procesar avatar si existe
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        // Crear usuario
        $user = User::create([
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'name' => $nombreCompleto,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono' => $request->telefono,
            // 'cargo' removido
            'direccion' => $request->direccion,
            'avatar' => $avatarPath,
            'email_verified_at' => now(),
        ]);

        // Asignar roles
        $user->syncRoles($request->roles);

        // Enviar correo de bienvenida con credenciales
        try {
            $user->notify(new WelcomeUserNotification($user, $request->password));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error al enviar correo de bienvenida: ' . $e->getMessage());
            // No detenemos el proceso si falla el correo, pero lo registramos
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Mostrar detalles del usuario
     */
    public function show(User $user)
    {
        $user->load('roles');
        
        // Si es una petición AJAX, devolver JSON para el modal
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nombres' => $user->nombres ?? '',
                    'apellidos' => $user->apellidos ?? '',
                    'email' => $user->email,
                    'telefono' => $user->telefono ?? 'No especificado',
                    'direccion' => $user->direccion ?? 'No especificada',
            // 'cargo' removido
                    'is_active' => $user->is_active,
                    'status_text' => $user->is_active ? 'Activo' : 'Inactivo',
                    'avatar_url' => $user->avatar_url ?? null,
                    'roles' => $user->roles->map(function($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name ?? $role->name,
                            'description' => $role->description,
                            'color' => $role->color ?? '#e53e3e',
                        ];
                    }),
                    'permissions_count' => $user->getAllPermissions()->count(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                    'last_login' => $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Nunca',
                ]
            ]);
        }
        
        // Vista normal para acceso directo
        return view('admin.usuarios.show', compact('user'));
    }

    /**
     * Mostrar formulario para editar usuario
     */
    public function edit(User $user)
    {
        $user->load('roles');
        
        // Si es una petición AJAX, devolver JSON para el modal
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nombres' => $user->nombres ?? '',
                    'apellidos' => $user->apellidos ?? '',
                    'email' => $user->email,
                    'telefono' => $user->telefono ?? '',
                    'direccion' => $user->direccion ?? '',
                    'cargo' => $user->cargo ?? '',
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'can_edit' => !in_array($user->email, ['brayanhuincho975@gmail.com']), // Proteger cuenta del dueño
                ]
            ]);
        }
        
        // Vista normal para acceso directo
        $roles = Role::all();
        return view('admin.usuarios.editar', compact('user', 'roles'));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name'
        ]);

        // Crear nombre completo
        $nombreCompleto = trim($request->nombres . ' ' . $request->apellidos);

        // Procesar avatar si existe
        $avatarPath = $user->avatar;
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        // Actualizar datos
        $updateData = [
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'name' => $nombreCompleto,
            'email' => $request->email,
            'telefono' => $request->telefono,
            // 'cargo' removido
            'direccion' => $request->direccion,
            'avatar' => $avatarPath,
        ];

        // Actualizar contraseña solo si se proporciona
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Actualizar roles
        $user->syncRoles($request->roles);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user->fresh()->load('roles')
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(User $user)
    {
        // Proteger cuenta del dueño
        if ($user->email === 'brayanhuincho975@gmail.com') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta del dueño'
            ], 403);
        }
        try {
            // Intentar eliminar
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si hay violación de llave foránea (MySQL 1451 / SQLSTATE 23000)
            $info = $e->errorInfo ?? [];
            $sqlState = $info[0] ?? null; // Ej: '23000'
            $driverCode = isset($info[1]) ? (int)$info[1] : null; // Ej: 1451
            $errorCode = (int) $e->getCode();

            if ($sqlState === '23000' || $driverCode === 1451 || $errorCode === 1451) {
                // Fallback: desactivar usuario en lugar de eliminar
                $user->update(['is_active' => false]);

                return response()->json([
                    'success' => true,
                    'message' => 'El usuario tiene registros asociados. Se desactivó su acceso en lugar de eliminar.'
                ]);
            }

            // Otros errores
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener todos los usuarios (para tabla)
     */
    public function apiUsuarios()
    {
        try {
            $usuarios = User::with('roles')->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $usuarios
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios'
            ], 500);
        }
    }

    /**
     * Cambiar estado del usuario (activo/inactivo)
     */
    public function cambiarEstado(Request $request, User $user)
    {
        // Evitar que el usuario autenticado desactive/active su propia cuenta
        if (auth()->check() && $user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar el estado de tu propia cuenta'
            ], 403);
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $estado = $user->is_active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "Usuario {$estado} exitosamente",
            'is_active' => $user->is_active
        ]);
    }

    /**
     * Resetear contraseña del usuario
     */
    public function resetearPassword(Request $request, User $user)
    {
        $nuevaPassword = Str::random(8);
        
        $user->update([
            'password' => Hash::make($nuevaPassword)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña reseteada exitosamente',
            'nueva_password' => $nuevaPassword
        ]);
    }
}
