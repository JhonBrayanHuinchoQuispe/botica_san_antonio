<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<x-head />
<body class="bg-neutral-100" data-turbo="true">
    <div id="preloader">
        <div class="preloader-content">
            <img src="{{ asset('assets/images/logotipo.png') }}" alt="logo" loading="eager" decoding="sync">
            <div class="loading-text">Botica San Antonio</div>
        </div>
    </div>
    
    <script>
        (function(){
            var noop = function(){};
            try {
                console.log = noop;
                console.info = noop;
                console.debug = noop;
                console.warn = noop;
            } catch(e) {}
        })();
    </script>

    <script>
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
            }

            function checkAndHide() {
                const elapsedTime = performance.now() - startTime;
                const minShowTime = 600;
                
                if (document.readyState === 'complete') {
                    const remainingTime = Math.max(0, minShowTime - elapsedTime);
                    setTimeout(hidePreloader, remainingTime);
                }
            }

            if (document.readyState === 'complete') {
                checkAndHide();
            } else {
                window.addEventListener('load', checkAndHide, { once: true });
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(checkAndHide, 1500);
                }, { once: true });
            }

            const maxShowTime = 6000;
            setTimeout(() => {
                if (!isHidden) {
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

    <script src="{{ asset('assets/js/notifications/notifications.js') }}" defer></script>
    
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
    
    <script src="{{ asset('assets/js/performance-optimizer.js') }}" defer></script>
    
    <x-script  script='{!! isset($script) ? $script : "" !!}' />
    
    <script>
        window.addEventListener('load', function() {
            const script = document.createElement('script');
            script.src = 'https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        });
    </script>

    <script>
        (function(){
            var vapid = "{{ config('services.push.vapid_public') }}";
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            if (!vapid) return;
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