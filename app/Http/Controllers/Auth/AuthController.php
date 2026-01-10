<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request as HttpRequest;
use App\Models\PasswordResetCode;
use App\Notifications\ResetPasswordCodeNotification;

class AuthController extends Controller
{

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:6'],
            ]);

            $email = $request->email;
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            // 1. Verificar rate limiting por IP
            $ipKey = 'login-attempts:ip:' . $ipAddress;
            if (RateLimiter::tooManyAttempts($ipKey, 10)) { // 10 intentos por IP en 1 hora
                $seconds = RateLimiter::availableIn($ipKey);
                $minutes = ceil($seconds / 60);
                
                $timeMessage = $minutes == 1 ? '1 minuto' : "$minutes minutos";
                
                Log::warning('IP bloqueada por demasiados intentos', [
                    'ip' => $ipAddress,
                    'email' => $email,
                    'seconds_remaining' => $seconds,
                    'minutes_remaining' => $minutes
                ]);
                
                return back()->withErrors([
                    'email' => "Demasiados intentos desde esta dirección IP. Por seguridad, intente nuevamente en $timeMessage."
                ])->withInput($request->except('password'));
            }

            // 2. Verificar si el usuario existe y está bloqueado
            $user = \App\Models\User::where('email', $email)->first();
            if ($user && $user->isLocked()) {
                $timeRemaining = $user->getLockTimeRemainingFormatted();
                $minutesRemaining = $user->getLockTimeRemaining();
                
                LoginAttempt::recordAttempt($email, $ipAddress, $userAgent, false);
                RateLimiter::hit($ipKey, 3600); // 1 hora
                
                Log::warning('Intento de login en cuenta bloqueada', [
                    'email' => $email,
                    'ip' => $ipAddress,
                    'minutes_remaining' => $minutesRemaining,
                    'time_formatted' => $timeRemaining
                ]);

                return back()->withErrors([
                    'email' => "Cuenta temporalmente bloqueada por seguridad. Intente nuevamente en $timeRemaining."
                ])->withInput($request->except('password'));
            }

            // 3. Verificar rate limiting por email
            $emailKey = 'login-attempts:email:' . $email;
            if (RateLimiter::tooManyAttempts($emailKey, 5)) { // 5 intentos por email en 15 minutos
                if ($user) {
                    $user->lockAccount(30); // Bloquear por 30 minutos
                }
                
                LoginAttempt::recordAttempt($email, $ipAddress, $userAgent, false);
                RateLimiter::hit($ipKey, 3600);
                
                Log::warning('Usuario bloqueado por demasiados intentos', [
                    'email' => $email,
                    'ip' => $ipAddress
                ]);

                return back()->withErrors([
                    'email' => 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente por seguridad.'
                ])->withInput($request->except('password'));
            }

            // 4. Intentar autenticación
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $user = Auth::user();
                
                // Login exitoso - resetear contadores
                $user->resetFailedAttempts();
                $user->updateLastLogin($ipAddress);
                
                RateLimiter::clear($emailKey);
                LoginAttempt::recordAttempt($email, $ipAddress, $userAgent, true);
                
                $request->session()->regenerate();
                
                Log::info('Usuario logueado exitosamente', [
                    'email' => $email,
                    'ip' => $ipAddress,
                    'user_agent' => $userAgent
                ]);
                
                return redirect()->route('dashboard.analisis')
                    ->with('success', 'Bienvenido al sistema');
            }

            // 5. Login fallido - incrementar contadores
            if ($user) {
                $user->incrementFailedAttempts();
            }
            
            RateLimiter::hit($emailKey, 900); // 15 minutos
            RateLimiter::hit($ipKey, 3600); // 1 hora
            LoginAttempt::recordAttempt($email, $ipAddress, $userAgent, false);

            Log::warning('Intento de login fallido', [
                'email' => $email,
                'ip' => $ipAddress,
                'failed_attempts' => $user ? $user->failed_login_attempts : 'unknown'
            ]);

            $remainingAttempts = 5 - RateLimiter::attempts($emailKey);
            $message = $remainingAttempts > 1 
                ? "Credenciales incorrectas. Le quedan $remainingAttempts intentos."
                : "Credenciales incorrectas. Último intento antes del bloqueo.";

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en login', [
                'email' => $request->email ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Error al iniciar sesión. Por favor, intente nuevamente.')
                ->withInput($request->except('password'));
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Log::info('Usuario cerró sesión', ['email' => Auth::user()->email]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Has cerrado sesión correctamente');
    }

    /**
     * Mostrar formulario de solicitud de recuperación de contraseña
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Enviar código de recuperación de contraseña
     */
    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'El campo email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.exists' => 'No existe una cuenta con este email.',
        ]);

        // Verificar rate limiting
        $key = 'password-reset-code:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Demasiados intentos. Inténtalo de nuevo en {$seconds} segundos."
            ]);
        }

        try {
            // Crear código de recuperación
            $resetCode = PasswordResetCode::createForEmail(
                $request->email,
                $request->ip(),
                $request->userAgent()
            );

            // Buscar el usuario
            $user = \App\Models\User::where('email', $request->email)->first();

            // Enviar notificación con el código
            $user->notify(new ResetPasswordCodeNotification($resetCode->code));

            // Incrementar rate limiting
            RateLimiter::hit($key, 300); // 5 minutos

            Log::info('Password reset code sent', [
                'email' => $request->email,
                'code_id' => $resetCode->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return redirect()->route('password.verify-code-form', ['email' => $request->email])->with([
                'status' => 'Te hemos enviado un código de verificación a tu email.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending password reset code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()->withErrors(['email' => 'No se pudo enviar el código de recuperación. Inténtalo más tarde.']);
        }
    }

    /**
     * Mostrar formulario de restablecimiento de contraseña
     */
    public function showResetPasswordForm(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'request' => $request,
            'token' => $token
        ]);
    }

    /**
     * Mostrar formulario para verificar código de recuperación
     */
    public function showVerifyCodeForm(Request $request)
    {
        $email = $request->session()->get('email') ?? $request->get('email');
        
        if (!$email) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Sesión expirada. Solicita un nuevo código.'
            ]);
        }

        return view('auth.verify-reset-code', compact('email'));
    }

    /**
     * Verificar código de recuperación
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.exists' => 'No existe una cuenta con este email.',
            'code.required' => 'El código es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
        ]);

        // Verificar rate limiting para verificación de códigos
        $key = 'verify-reset-code:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'code' => "Demasiados intentos. Inténtalo de nuevo en {$seconds} segundos."
            ]);
        }

        try {
            // Verificar el código
            $resetCode = PasswordResetCode::validateCode($request->email, $request->code);

            if (!$resetCode) {
                RateLimiter::hit($key, 300); // 5 minutos
                
                return back()->withErrors([
                    'code' => 'El código es inválido o ha expirado.'
                ])->withInput();
            }

            Log::info('Password reset code verified successfully', [
                'email' => $request->email,
                'code_id' => $resetCode->id,
                'ip' => $request->ip()
            ]);

            // Limpiar rate limiting
            RateLimiter::clear($key);

            // Redirigir al formulario de nueva contraseña
            return redirect()->route('password.reset-form-with-code')->with([
                'email' => $request->email,
                'code' => $request->code,
                'status' => 'Código verificado correctamente. Ahora puedes cambiar tu contraseña.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error verifying reset code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()->withErrors([
                'code' => 'Error al verificar el código. Inténtalo más tarde.'
            ])->withInput();
        }
    }

    /**
     * Mostrar formulario para cambiar contraseña con código
     */
    public function showResetFormWithCode(Request $request)
    {
        $email = $request->session()->get('email');
        $code = $request->session()->get('code');
        
        if (!$email || !$code) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Sesión expirada. Solicita un nuevo código.'
            ]);
        }

        // Verificar que el código sigue siendo válido
        $resetCode = PasswordResetCode::validateCode($email, $code);
        if (!$resetCode) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'El código ha expirado. Solicita un nuevo código.'
            ]);
        }

        return view('auth.reset-password-with-code', compact('email', 'code'));
    }

    /**
     * Actualizar contraseña con código
     */
    public function updatePasswordWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|min:6|confirmed',
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.exists' => 'No existe una cuenta con este email.',
            'code.required' => 'El código es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        try {
            // Verificar el código una vez más
            $resetCode = PasswordResetCode::validateCode($request->email, $request->code);

            if (!$resetCode) {
                return back()->withErrors([
                    'code' => 'El código es inválido o ha expirado.'
                ])->withInput();
            }

            // Buscar el usuario
            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors([
                    'email' => 'Usuario no encontrado.'
                ])->withInput();
            }

            // Actualizar la contraseña
            $user->forceFill([
                'password' => Hash::make($request->password)
            ])->setRememberToken(Str::random(60));

            $user->save();

            // Marcar el código como usado
            $resetCode->markThisAsUsed();

            // Limpiar códigos expirados
            PasswordResetCode::cleanExpired();

            Log::info('Password updated successfully with code', [
                'user_id' => $user->id,
                'email' => $request->email,
                'code_id' => $resetCode->id,
                'ip' => $request->ip()
            ]);

            // Retornar vista con SweetAlert
            return view('auth.password-reset-success', [
                'message' => '¡Contraseña cambiada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating password with code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()->withErrors([
                'password' => 'Error al cambiar la contraseña. Inténtalo más tarde.'
            ])->withInput();
        }
    }

    /**
     * Reenviar código de recuperación
     */
    public function resendResetCode(Request $request)
    {
        Log::info('Resend code request received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'all_data' => $request->all()
        ]);

        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'El email es obligatorio.',
                'email.email' => 'El formato del email no es válido.',
                'email.exists' => 'No existe una cuenta con este email.',
            ]);
            
            Log::info('Validation passed for resend code', ['email' => $request->email]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for resend code', [
                'email' => $request->email,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        }

        // Verificar rate limiting para reenvío
        $key = 'resend-reset-code:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 2)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Rate limit exceeded for resend code', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'seconds_remaining' => $seconds
            ]);
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Espera {$seconds} segundos."
            ], 429);
        }

        try {
            Log::info('Starting resend code process', ['email' => $request->email]);
            // Invalidar todos los códigos anteriores para este email
            PasswordResetCode::where('email', $request->email)
                ->where('used', false)
                ->update(['used' => true]);

            // Crear nuevo código
            $resetCode = PasswordResetCode::createForEmail(
                $request->email,
                $request->ip(),
                $request->userAgent()
            );

            // Buscar el usuario
            $user = \App\Models\User::where('email', $request->email)->first();

            // Enviar notificación con el nuevo código
            $user->notify(new ResetPasswordCodeNotification($resetCode->code));

            // Incrementar rate limiting
            RateLimiter::hit($key, 120); // 2 minutos

            Log::info('Password reset code resent', [
                'email' => $request->email,
                'code_id' => $resetCode->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nuevo código enviado a tu email.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error resending reset code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar el código. Inténtalo más tarde.'
            ], 500);
        }
    }

    /**
     * Restablecer la contraseña
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ser un correo electrónico válido.',
                'token.required' => 'Token de restablecimiento requerido.'
            ]);

            Log::info('Intento de restablecimiento de contraseña', ['email' => $request->email]);

            // Restablecer la contraseña
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                Log::info('Contraseña restablecida exitosamente', ['email' => $request->email]);
                
                return redirect()->route('login')->with('password_reset_success', 
                    '¡Contraseña restablecida exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.'
                );
            }

            Log::warning('Error al restablecer contraseña', [
                'email' => $request->email,
                'status' => $status
            ]);

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);

        } catch (\Exception $e) {
            Log::error('Error en resetPassword', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['email' => 'Error al restablecer la contraseña. Por favor, intente nuevamente.'])
                ->withInput();
        }
    }
}