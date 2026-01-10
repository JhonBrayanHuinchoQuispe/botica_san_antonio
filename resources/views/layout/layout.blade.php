<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<!-- ⚡ PRELOAD RECURSOS CRÍTICOS PARA VELOCIDAD MÁXIMA -->
<link rel="preload" href="{{ asset('assets/images/logotipo.png') }}" as="image" type="image/png">
<link rel="preload" href="{{ asset('assets/css/preloader.css') }}" as="style">
<link rel="preload" href="{{ asset('assets/js/app.js') }}" as="script">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://code.iconify.design">

<x-head />


<body class="bg-neutral-100" data-turbo="true">

    <!-- 🔇 Silenciar logs de consola globalmente (mantener errores) -->
    <script>
        (function(){
            var noop = function(){};
            try {
                console.log = noop;
                console.info = noop;
                console.debug = noop;
                console.warn = noop;
            } catch(e) {}
            // Mantener console.error para errores reales
        })();
    </script>

    <!-- ⚡ PRELOADER GLOBAL: oculto por defecto, se muestra sólo si se necesita -->
    <div id="preloader">
        <div class="preloader-content">
            <img src="{{ asset('assets/images/logotipo.png') }}" alt="logo" loading="eager" decoding="sync">
            <div class="loading-text">Botica San Antonio</div>
        </div>
    </div>

    <!-- ⚡ SCRIPT PRINCIPAL - Manejo del ocultamiento del preloader -->
    <script>
        // 🧠 MANEJO GLOBAL DEL OCULTAMIENTO
        (function() {
            const preloader = document.getElementById('preloader');
            if (!preloader) return;
            
            const startTime = performance.now();
            let isHidden = false;
            
            function hidePreloader() {
                if (isHidden) return;
                isHidden = true;
                
                preloader.style.transition = 'all 0.3s ease-out';
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
                preloader.style.transform = 'scale(0.95)';
                preloader.style.pointerEvents = 'none';
                
                setTimeout(() => {
                    if (preloader.parentNode) {
                        preloader.remove();
                    }
                }, 300);
                // Silenciado: log de ocultamiento del preloader
            }
            
            // Ocultar cuando esté listo
            function checkAndHide() {
                const elapsedTime = performance.now() - startTime;
                const minShowTime = 600; // Tiempo mínimo visible global
                const remainingTime = Math.max(0, minShowTime - elapsedTime);
                setTimeout(hidePreloader, remainingTime);
            }
            
            // Triggers para ocultar
            if (document.readyState === 'complete') {
                checkAndHide();
            } else if (document.readyState === 'interactive') {
                // DOM listo pero recursos pendientes
                setTimeout(checkAndHide, 150);
            } else {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(checkAndHide, 200);
                }, { once: true });
                
                window.addEventListener('load', checkAndHide, { once: true });
            }
            
            // Fallback de seguridad global
            const maxShowTime = 6000;
            setTimeout(() => {
                if (!isHidden) {
                    // Silenciado: log de timeout del preloader
                    hidePreloader();
                }
            }, maxShowTime);
        })();
    </script>

    <x-sidebar />

    <script>
        (function(){
            try {
                var collapsed = localStorage.getItem('sidebar_collapsed') === '1';
                var sb = document.querySelector('.sidebar');
                var main = document.querySelector('.dashboard-main');
                if (collapsed) {
                    if (sb) sb.classList.add('active');
                    if (main) main.classList.add('active');
                } else {
                    if (sb) sb.classList.remove('active');
                    if (main) main.classList.remove('active');
                }
            } catch(e) {}
        })();
    </script>
                
    <main class="dashboard-main {{ request()->cookie('sidebar_collapsed') === '1' ? 'active' : '' }}">

        <x-navbar />
        <div class="dashboard-main-body">
            
            <x-breadcrumb title='{{ isset($title) ? $title : "" }}' subTitle='{{ isset($subTitle) ? $subTitle : "" }}' />

            @yield('content')
        
        </div>
        <x-footer />

    </main>

    <!-- 🔔 Sistema de Notificaciones en Tiempo Real -->
    <script src="{{ asset('assets/js/notifications/notifications.js') }}" defer></script>
    
    <!-- 🔌 Echo + Pusher (CDN) para tiempo real -->
    <script>
        window.__ECHO_CONFIG__ = {
            key: "{{ config('broadcasting.connections.pusher.key') }}",
            wsHost: "{{ config('broadcasting.connections.pusher.options.host') }}",
            wsPort: {{ (int) config('broadcasting.connections.pusher.options.port') }},
            forceTLS: {{ config('broadcasting.connections.pusher.options.useTLS') ? 'true' : 'false' }},
            userId: {{ (int) (auth()->id() ?? 0) }}
        };
    </script>
    <script src="https://js.pusher.com/8.4/pusher.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js" defer></script>
    <script src="{{ asset('assets/js/realtime/echo-init.js') }}" defer></script>
    
    <!-- ⚡ OPTIMIZADOR DE RENDIMIENTO GLOBAL -->
    <script src="{{ asset('assets/js/performance-optimizer.js') }}" defer></script>
    
    <x-script  script='{!! isset($script) ? $script : "" !!}' />
    
    <!-- ⚡ Carga diferida de Iconify para no bloquear -->
    <script>
        // Cargar Iconify después de que todo esté listo para máxima velocidad
        window.addEventListener('load', function() {
            const script = document.createElement('script');
            script.src = 'https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        });
    </script>

    <!-- 🔔 Web Push: solicitar permiso en la primera interacción y suscribir -->
    <script>
        (function(){
            var vapid = "{{ config('services.push.vapid_public') }}";
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            if (!vapid) return; // aún no activado

            // Garantizar registro del SW
            navigator.serviceWorker.register('/sw.js').catch(function(){});

            function urlBase64ToUint8Array(base64String) {
                var padding = '='.repeat((4 - base64String.length % 4) % 4);
                var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                var rawData = window.atob(base64);
                var outputArray = new Uint8Array(rawData.length);
                for (var i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
                return outputArray;
            }

            async function askAndSubscribe(){
                try {
                    var reg = await navigator.serviceWorker.ready;
                    var sub = await reg.pushManager.getSubscription();
                    if (!sub) {
                        var permission = await Notification.requestPermission();
                        if (permission !== 'granted') { return; }
                        sub = await reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(vapid)
                        });
                    }
                    await fetch("{{ route('push.subscribe') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(sub)
                    });
                } catch(e) {}
            }

            // Primera interacción del usuario dispara la solicitud de permiso
            var once = function(){
                window.removeEventListener('click', once);
                window.removeEventListener('keydown', once);
                window.removeEventListener('touchstart', once);
                askAndSubscribe();
            };
            window.addEventListener('click', once, { once: true });
            window.addEventListener('keydown', once, { once: true });
            window.addEventListener('touchstart', once, { once: true });
        })();
    </script>


</body>

</html>
<!-- Panel de pruebas eliminado para producción -->
