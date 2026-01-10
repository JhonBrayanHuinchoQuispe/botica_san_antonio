<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\ResetPasswordCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    /**
     * Login del usuario móvil
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ], [
                'email.required' => 'El email es requerido',
                'email.email' => 'El email debe ser válido',
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Verificar si el usuario existe
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar si la cuenta está activa
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está desactivada. Contacta al administrador.'
                ], 403);
            }

            // Verificar si la cuenta está bloqueada
            if ($user->isLocked()) {
                $timeRemaining = $user->getLockTimeRemainingFormatted();
                return response()->json([
                    'success' => false,
                    'message' => "Cuenta bloqueada temporalmente. Intenta nuevamente en $timeRemaining."
                ], 423);
            }

            // Verificar la contraseña
            if (!Hash::check($request->password, $user->password)) {
                $user->incrementFailedAttempts();
                
                $attemptsRemaining = 5 - $user->failed_login_attempts;
                $message = $attemptsRemaining > 0 
                    ? "Credenciales incorrectas. Te quedan $attemptsRemaining intentos."
                    : 'Cuenta bloqueada por múltiples intentos fallidos.';

                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 401);
            }

            // Login exitoso
            $user->resetFailedAttempts();
            $user->updateLastLogin($request->ip());

            // Crear token de acceso
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'nombres' => $user->nombres,
                        'apellidos' => $user->apellidos,
                        'email' => $user->email,
                        'telefono' => $user->telefono,
                        'cargo' => $user->cargo,
                        'avatar' => $user->avatar,
                        'full_name' => $user->full_name,
                        'initials' => $user->initials,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name')
                    ],
                    'token' => $token
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Logout del usuario móvil
     */
    public function logout(Request $request)
    {
        try {
            // Revocar el token actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'nombres' => $user->nombres,
                        'apellidos' => $user->apellidos,
                        'email' => $user->email,
                        'telefono' => $user->telefono,
                        'cargo' => $user->cargo,
                        'avatar' => $user->avatar,
                        'full_name' => $user->full_name,
                        'initials' => $user->initials,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                        'last_login_at' => $user->last_login_at,
                        'force_password_change' => $user->force_password_change
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ], [
                'current_password.required' => 'La contraseña actual es requerida',
                'new_password.required' => 'La nueva contraseña es requerida',
                'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres',
                'new_password.confirmed' => 'La confirmación de contraseña no coincide',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Verificar contraseña actual
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 401);
            }

            // Actualizar contraseña
            $user->update([
                'password' => Hash::make($request->new_password),
                'force_password_change' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Verificar token
     */
    public function verifyToken(Request $request)
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'success' => true,
                'message' => 'Token válido',
                'data' => [
                    'valid' => true,
                    'user_id' => $user->id,
                    'email' => $user->email
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'data' => [
                    'valid' => false
                ]
            ], 401);
        }
    }

    /**
     * Enviar código de recuperación de contraseña
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El formato del correo electrónico no es válido',
                'email.exists' => 'El correo electrónico no está registrado en nuestro sistema',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Generar código de 6 dígitos
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Guardar código en la base de datos
            $user->update([
                'reset_code' => $code,
                'reset_code_expires_at' => now()->addMinutes(5), // Expira en 5 minutos
            ]);

            // Enviar email con el código
            try {
                Mail::to($user->email)->send(new ResetPasswordCodeMail($code, $user->name));
            } catch (\Exception $e) {
                \Log::error('Error enviando email de recuperación: ' . $e->getMessage());
                // Continuar aunque falle el email para propósitos de desarrollo
            }

            return response()->json([
                'success' => true,
                'message' => 'Código de recuperación enviado exitosamente',
                'data' => [
                    'email' => $user->email,
                    'expires_in_minutes' => 15
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar código de recuperación',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Verificar código de recuperación
     */
    public function verifyResetCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string|size:6',
            ], [
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El formato del correo electrónico no es válido',
                'email.exists' => 'El correo electrónico no está registrado',
                'code.required' => 'El código es requerido',
                'code.size' => 'El código debe tener 6 dígitos',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Verificar si el código existe y no ha expirado
            if (!$user->reset_code || $user->reset_code !== $request->code) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación es incorrecto'
                ], 422);
            }

            if ($user->reset_code_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación ha expirado'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Código verificado exitosamente',
                'data' => [
                    'email' => $user->email,
                    'code_valid' => true
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar código',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Restablecer contraseña con código
     */
    public function resetPasswordWithCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string|size:6',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El formato del correo electrónico no es válido',
                'email.exists' => 'El correo electrónico no está registrado',
                'code.required' => 'El código es requerido',
                'code.size' => 'El código debe tener 6 dígitos',
                'password.required' => 'La nueva contraseña es requerida',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres',
                'password.confirmed' => 'La confirmación de contraseña no coincide',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Verificar código
            if (!$user->reset_code || $user->reset_code !== $request->code) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación es incorrecto'
                ], 422);
            }

            if ($user->reset_code_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación ha expirado'
                ], 422);
            }

            // Actualizar contraseña y limpiar código
            $user->update([
                'password' => Hash::make($request->password),
                'reset_code' => null,
                'reset_code_expires_at' => null,
                'force_password_change' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
                'data' => [
                    'email' => $user->email
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restablecer contraseña',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Reenviar código de recuperación
     */
    public function resendResetCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El formato del correo electrónico no es válido',
                'email.exists' => 'El correo electrónico no está registrado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Generar nuevo código
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Actualizar código en la base de datos
            $user->update([
                'reset_code' => $code,
                'reset_code_expires_at' => now()->addMinutes(5),
            ]);

            // Enviar email con el nuevo código
            try {
                Mail::to($user->email)->send(new ResetPasswordCodeMail($code, $user->name));
            } catch (\Exception $e) {
                \Log::error('Error reenviando email de recuperación: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Código reenviado exitosamente',
                'data' => [
                    'email' => $user->email,
                    'expires_in_minutes' => 15
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar código',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Actualizar perfil del usuario
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'nombres' => 'nullable|string|max:255',
                'apellidos' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'nombres.max' => 'El nombre no puede exceder 255 caracteres',
                'apellidos.max' => 'El apellido no puede exceder 255 caracteres',
                'email.email' => 'El formato del correo electrónico no es válido',
                'email.unique' => 'Este correo electrónico ya está en uso',
                'avatar.image' => 'El archivo debe ser una imagen',
                'avatar.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif',
                'avatar.max' => 'La imagen no puede ser mayor a 2MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            if ($request->filled('nombres')) { $updateData['nombres'] = $request->nombres; }
            if ($request->filled('apellidos')) { $updateData['apellidos'] = $request->apellidos; }
            if ($request->filled('email')) { $updateData['email'] = $request->email; }
            if ($request->filled('nombres') || $request->filled('apellidos')) {
                $nombreBase = $request->filled('nombres') ? $request->nombres : ($user->nombres ?? '');
                $apellidoBase = $request->filled('apellidos') ? $request->apellidos : ($user->apellidos ?? '');
                $updateData['name'] = trim($nombreBase . ' ' . $apellidoBase);
            }

            // Manejar avatar si se proporciona
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('public/avatars', $avatarName);
                
                // Eliminar avatar anterior si existe
                if ($user->avatar && \Storage::exists('public/' . $user->avatar)) {
                    \Storage::delete('public/' . $user->avatar);
                }
                
                $updateData['avatar'] = 'avatars/' . $avatarName;
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}