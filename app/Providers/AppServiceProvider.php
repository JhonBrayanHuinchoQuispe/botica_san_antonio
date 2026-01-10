<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Auth\Events\Login;
use App\Models\User;
use App\Models\Producto;
use App\Observers\ProductoObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar repositorios
        $this->app->bind(
            \App\Repositories\ProductoRepository::class,
            \App\Repositories\ProductoRepository::class
        );
        
        // Registrar servicios
        $this->app->bind(
            \App\Services\ProductoService::class,
            \App\Services\ProductoService::class
        );
        
        $this->app->bind(
            \App\Services\LoteService::class,
            \App\Services\LoteService::class
        );

        // Fix manual para el Auditor de Laravel Auditing
        $this->app->singleton(\OwenIt\Auditing\Contracts\Auditor::class, function ($app) {
            return new \OwenIt\Auditing\Auditor($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force Audit UserResolver
        Config::set('audit.user.resolver', \App\Auditing\Resolvers\UserResolver::class);

        // Forzar HTTPS sólo en producción o cuando realmente la petición sea segura
        try {
            $environment = app()->environment();
            $appUrlScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
            $isForwardedHttps = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';

            $shouldForceHttps = (
                in_array($environment, ['production', 'staging']) && $appUrlScheme === 'https'
            ) || request()->isSecure() || $isForwardedHttps;

            if ($shouldForceHttps) {
                URL::forceScheme('https');
                Config::set('session.secure', true);

                // Alinear el dominio de la cookie sólo si coincide con el host actual
                $configuredHost = parse_url(config('app.url'), PHP_URL_HOST);
                $currentHost = request()->getHost();
                if ($configuredHost && $configuredHost === $currentHost) {
                    Config::set('session.domain', $configuredHost);
                }
            } else {
                // En entornos locales, evitar forzar https y dominio
                Config::set('session.secure', false);
                Config::set('session.domain', null);
            }
        } catch (\Throwable $e) {
            // Evitar que falle el arranque por configuración
        }
        // Registrar observers
        Producto::observe(ProductoObserver::class);
        
        // Listener para actualizar last_login_at cuando un usuario hace login
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->updateLastLogin(request()->ip());
            }
        });
    }
}
