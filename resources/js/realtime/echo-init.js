// Inicialización de Laravel Echo con soporte Pusher y WebSockets self-hosted
(function(){
  function safeLog() { /* silenciado */ }

  function initEcho() {
    try {
      // Esperar a que las librerías CDN estén disponibles
      if (!window.Echo || !window.Pusher || !window.__ECHO_CONFIG__) {
        return setTimeout(initEcho, 250);
      }

      var cfg = window.__ECHO_CONFIG__ || {};
      var csrf = document.querySelector('meta[name="csrf-token"]');

      // Desactivar logs de Pusher
      try { window.Pusher.logToConsole = false; } catch (_) {}

      var base = {
        broadcaster: 'pusher',
        key: cfg.key,
        forceTLS: !!cfg.forceTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
          headers: {
            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
          }
        }
      };

      // Cloud Pusher (cluster) vs self-hosted (host/port)
      if (cfg.cluster) {
        base.cluster = cfg.cluster;
      } else {
        base.wsHost = cfg.wsHost || window.location.hostname;
        base.wsPort = cfg.wsPort || 6001;
        base.wssPort = cfg.wsPort || 6001;
      }

      window.echo = new window.Echo(base);

      safeLog('Echo inicializado');

      // Suscripción a canal público de ventas
      window.echo.channel('ventas')
        .listen('VentaProcesada', function (e) {
          try {
            if (window.notificationManager && typeof window.notificationManager.loadNotifications === 'function') {
              window.notificationManager.loadNotifications();
            }
            // Feedback visual rápido sin romper UI existente
            if (typeof Swal !== 'undefined') {
              Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }).fire({
                icon: 'success',
                title: 'Nueva venta: S/ ' + (e.total || '0.00')
              });
            }
          } catch (_) {}
        });

      // Suscripción a canal privado por usuario
      if (cfg.userId) {
        window.echo.private('users.' + cfg.userId)
          .listen('VentaProcesada', function (e) {
            try {
              if (window.notificationManager && typeof window.notificationManager.loadNotifications === 'function') {
                window.notificationManager.loadNotifications();
              }
            } catch (_) {}
          });
      }
    } catch (err) {
      // No romper la app si falla echo
    }
  }

  // Iniciar al cargar
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initEcho();
  } else {
    document.addEventListener('DOMContentLoaded', initEcho, { once: true });
  }
})();