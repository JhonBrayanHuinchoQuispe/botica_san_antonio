<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Mostrar lista de roles
     */
    public function index()
    {
        // Obtener todos los roles y ordenar con "Gerente" primero
        $roles = Role::with('permissions')->get()->sortBy(function($role) {
            return $role->name === 'Gerente' ? 0 : 1;
        })->values();
        
        // Permisos agrupados por módulo (compatibilidad con modelo actual)
        $permisos = Permission::all()->groupBy('modulo');
        
        return view('admin.roles.index', compact('roles', 'permisos'));
    }

    /**
     * Mostrar formulario para crear rol
     */
    public function create()
    {
        $permisos = Permission::all()->groupBy('modulo');
        return view('admin.roles.crear', compact('permisos'));
    }

    /**
     * Almacenar nuevo rol
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'exists:permissions,id'
        ]);

        // Crear rol (Spatie Role puede ignorar columnas extras por fillable)
        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);
        // Forzar guardado del color personalizado
        $role->forceFill(['color' => $request->color])->save();

        // Asignar permisos
        $role->permissions()->sync($request->permisos);

        return response()->json([
            'success' => true,
            'message' => 'Rol creado exitosamente',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Mostrar detalles del rol
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        
        // Si es una petición AJAX, devolver JSON para el modal
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description ?? 'Sin descripción',
                    'color' => $role->color ?? '#e53e3e',
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users->count(),
                    'permissions' => $role->permissions->map(function($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $permission->display_name ?? $permission->name,
                            'description' => $permission->description,
                        ];
                    }),
                    'users' => $role->users->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'avatar' => $user->avatar_url ?? null,
                        ];
                    }),
                    'is_protected' => in_array($role->name, ['dueño', 'gerente']),
                    'created_at' => $role->created_at->format('d/m/Y'),
                    'updated_at' => $role->updated_at->format('d/m/Y'),
                ]
            ]);
        }
        
        // Vista normal para acceso directo
        $permisos = Permission::all()->groupBy('modulo');
        return view('admin.roles.show', compact('role', 'permisos'));
    }

    /**
     * Mostrar formulario para editar rol
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        
        // Si es una petición AJAX, devolver JSON para el modal
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description,
                    'color' => $role->color ?? '#e53e3e',
                    'permissions' => $role->permissions->pluck('id')->toArray(),
                    'is_protected' => in_array($role->name, ['dueño', 'gerente']),
                ]
            ]);
        }
        
        // Vista normal para acceso directo
        $permisos = Permission::all()->groupBy('modulo');
        return view('admin.roles.editar', compact('role', 'permisos'));
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, Role $role)
    {
        // Proteger roles críticos de modificaciones
        if (in_array($role->name, ['dueño', 'gerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este rol está protegido y no puede ser modificado'
            ], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'exists:permissions,id'
        ]);

        // Actualizar campos estándar
        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);
        // Forzar actualización del color (evitar restricciones de fillable)
        $role->forceFill(['color' => $request->color])->save();

        // Actualizar permisos
        $role->permissions()->sync($request->permisos);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente',
            'role' => $role->fresh()->load('permissions')
        ]);
    }

    /**
     * Eliminar rol
     */
    public function destroy(Role $role)
    {
        // Proteger roles críticos de eliminación
        if (in_array($role->name, ['dueño', 'gerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este rol está protegido y no puede ser eliminado'
            ], 403);
        }

        // Verificar si hay usuarios con este rol
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados'
            ], 422);
        }

        // Eliminar relaciones con permisos
        $role->permissions()->detach();

        // Eliminar rol
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente'
        ]);
    }

    /**
     * Asignar permisos específicos a un rol
     */
    public function asignarPermisos(Request $request, Role $role)
    {
        $request->validate([
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'exists:permissions,id'
        ]);

        $role->permissions()->sync($request->permisos);

        return response()->json([
            'success' => true,
            'message' => 'Permisos asignados exitosamente',
            'role' => $role->fresh()->load('permissions')
        ]);
    }

    /**
     * Cambiar estado del rol (activo/inactivo)
     */
    public function cambiarEstado(Request $request, Role $role)
    {
        // Proteger roles críticos
        if (in_array($role->name, ['dueño', 'gerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este rol está protegido y no puede cambiar su estado'
            ], 403);
        }

        $role->update([
            'is_active' => !$role->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $role->is_active ? 'Rol activado' : 'Rol desactivado',
            'is_active' => $role->is_active
        ]);
    }

    /**
     * API para obtener todos los roles (para tabla)
     */
    public function apiRoles()
    {
        try {
            $roles = Role::with('permissions')->get()->sortBy(function($role) {
                return $role->name === 'Gerente' ? 0 : 1;
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles'
            ], 500);
        }
    }
}
