<head>
    <style>
        html { background-color: #ef6060 !important; margin: 0 !important; padding: 0 !important; border: 0 !important; }
        body { margin: 0 !important; padding: 0 !important; border: 0 !important; background-color: #f3f4f6; position: relative !important; top: 0 !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body::before, body::after { display: none !important; content: none !important; }
        .navbar-header { position: sticky !important; top: 0 !important; z-index: 1000 !important; width: 100% !important; margin: 0 !important; border-top: 0 !important; }
        .modal-overlay { z-index: 1050 !important; background-color: rgba(0, 0, 0, 0.6) !important; backdrop-filter: blur(4px); }
        .modal-presentacion-overlay { z-index: 1100 !important; background-color: rgba(0, 0, 0, 0.6) !important; backdrop-filter: blur(4px); }
        .dashboard-main { overflow: visible !important; padding-top: 0 !important; margin-top: 0 !important; }
        .dashboard-main-body { padding-top: 0 !important; }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-cache-control" content="no-preview">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">
    <title>Panel de inventario y ventas - botica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo.png') }}" sizes="16x16">
    <meta name="application-name" content="Sistema de Botica">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Botica Sistema">
    <meta name="description" content="Sistema completo de gestión para boticas y farmacias">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    <meta name="msapplication-TileColor" content="#fb7185">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="theme-color" content="#fb7185">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.svg') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('images/icons/icon-192x192.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/icon-192x192.svg') }}">
    <link rel="apple-touch-icon" sizes="167x167" href="{{ asset('images/icons/icon-192x192.svg') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor-katex.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/full-calendar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/jquery-jvectormap-2.0.5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/prism.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/file-upload.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/audioplayer.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/app-style.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/custom-sidebar.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/navbar-search.css') }}?v={{ time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/preloader.css') }}?v={{ time() }}">
    <script type="module" src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script>
        (function(){
            localStorage.setItem('color-theme','light');
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        })();
    </script>
    @stack('head')
    @stack('styles')
</head>