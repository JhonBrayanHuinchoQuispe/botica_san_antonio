<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema
    |--------------------------------------------------------------------------
    |
    | Aquí se definen las configuraciones por defecto del sistema
    |
    */

    'empresa' => [
        'nombre_default' => 'Mi Empresa',
        'ruc_default' => '',
        'direccion_default' => '',
        'telefono_default' => '',
        'email_default' => '',
        'logo_path' => 'storage/logos/',
        'logo_max_size' => 2048, // KB
        'logo_allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        
        // Datos adicionales para facturación electrónica
        'ubigeo_default' => '150101',
        'departamento_default' => 'LIMA',
        'provincia_default' => 'LIMA',
        'distrito_default' => 'LIMA',
        'urbanizacion_default' => '-',
        'web_default' => '',
    ],

    'igv' => [
        'porcentaje_default' => 18.00,
        'tasa' => env('IGV_TASA', 18.00), // Tasa de IGV en porcentaje
        'nombre_default' => 'IGV',
        'incluir_precios_default' => true,
        'incluido_precios' => env('IGV_INCLUIDO_PRECIOS', true), // Si los precios incluyen IGV
        'mostrar_ticket_default' => true,
        'codigo_afectacion' => '10', // Código de afectación por defecto (Gravado)
    ],

    'sunat' => [
        'ruc' => env('SUNAT_RUC', ''),
        'razon_social' => env('SUNAT_RAZON_SOCIAL', ''),
        'nombre_comercial' => env('SUNAT_NOMBRE_COMERCIAL', ''),
        'usuario_sol' => env('SUNAT_USUARIO_SOL', ''),
        'clave_sol' => env('SUNAT_CLAVE_SOL', ''),
        'certificado_password' => env('SUNAT_CERTIFICADO_PASSWORD', ''),
        'produccion' => env('SUNAT_PRODUCCION', false),
        
        // URLs de servicios SUNAT
        'url_beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
        'url_produccion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
        
        // Configuraciones adicionales
        'timeout' => 30, // Timeout en segundos para las peticiones
        'max_reintentos' => 3, // Máximo número de reintentos
        'certificado_path' => 'storage/certificados/',
        'certificado_max_size' => 5120, // KB
        
        // Configuraciones de archivos
        'archivos' => [
            'guardar_xml' => true,
            'guardar_pdf' => true,
            'ruta_xml' => 'sunat/xml',
            'ruta_pdf' => 'sunat/pdf',
            'ruta_cdr' => 'sunat/cdr',
        ],
        
        // Configuraciones de logs
        'logs' => [
            'habilitar' => env('SUNAT_LOGS', true),
            'nivel' => env('SUNAT_LOG_LEVEL', 'info'), // debug, info, warning, error
            'archivo' => 'sunat.log',
            'max_archivos' => 30, // Máximo número de archivos de log a mantener
        ],
        
        // Configuraciones de validaciones
        'validaciones' => [
            'validar_ruc' => true,
            'validar_dni' => true,
            'validar_certificado' => true,
            'validar_conexion' => false, // Validar conexión antes de enviar
        ],
        
        // Configuraciones de desarrollo
        'desarrollo' => [
            'simular_envios' => env('SUNAT_SIMULAR_ENVIOS', false),
            'debug_xml' => env('SUNAT_DEBUG_XML', false),
            'mostrar_errores_detallados' => env('SUNAT_DEBUG_ERRORS', false),
        ],
    ],

    'impresoras' => [
        'papel_ancho_default' => 80, // mm
        'copias_ticket_default' => 1,
        'tipos_papel' => [
            '58' => '58mm',
            '80' => '80mm',
            'A4' => 'A4',
            'Carta' => 'Carta'
        ]
    ],

    'tickets' => [
        'margen_superior_default' => 5, // mm
        'margen_inferior_default' => 5, // mm
        'mostrar_logo_default' => true,
        'mostrar_direccion_default' => true,
        'mostrar_telefono_default' => true,
        'mostrar_igv_default' => true,
        'pie_pagina_default' => 'Gracias por su compra'
    ],

    'comprobantes' => [
        'serie_factura_default' => 'F001',
        'serie_boleta_default' => 'B001',
        'serie_ticket_default' => 'T001',
        'serie_nota_credito_default' => 'BC01',
        'serie_nota_debito_default' => 'BD01',
        'numeracion_inicial' => 1,
        'papel_size_default' => 'A4',
        'orientacion_default' => 'portrait',
        'mostrar_qr_default' => true,
        'mostrar_hash_default' => true,
        'copias_factura_default' => 2,
        'copias_boleta_default' => 2,
        'copias_ticket_default' => 1,
        
        // Configuraciones de numeración
        'numeracion' => [
            'longitud_correlativo' => 8, // Longitud del correlativo (ej: 00000001)
            'reiniciar_anual' => true, // Reiniciar numeración cada año
        ],
        
        // Monedas soportadas
        'monedas' => [
            'PEN' => 'Soles',
            'USD' => 'Dólares Americanos',
            'EUR' => 'Euros',
        ],
        'moneda_principal' => 'PEN',
        'decimales' => 2,
        'simbolo_moneda' => 'S/',
    ],

    'alertas' => [
        'stock_minimo_global_default' => 10,
        'dias_anticipacion_vencimiento_default' => 30,
        'dias_anticipacion_stock_default' => 7,
        'nivel_criticidad_default' => 'medio',
        'frecuencia_email_default' => 'diario',
        'niveles_criticidad' => [
            'bajo' => 'Bajo',
            'medio' => 'Medio',
            'alto' => 'Alto',
            'critico' => 'Crítico'
        ],
        'frecuencias_email' => [
            'inmediato' => 'Inmediato',
            'diario' => 'Diario',
            'semanal' => 'Semanal',
            'mensual' => 'Mensual'
        ]
    ],

    'cache' => [
        'tipos' => [
            'application' => 'Caché de Aplicación',
            'route' => 'Caché de Rutas',
            'view' => 'Caché de Vistas',
            'config' => 'Caché de Configuración'
        ],
        'optimizaciones' => [
            'config' => 'Optimizar Configuración',
            'route' => 'Optimizar Rutas',
            'autoload' => 'Optimizar Autoload'
        ],
        'frecuencias_limpieza' => [
            'nunca' => 'Nunca',
            'diario' => 'Diario',
            'semanal' => 'Semanal',
            'mensual' => 'Mensual'
        ]
    ],

    'sistema' => [
        'version' => '1.0.0',
        'nombre' => 'Sistema de Botica',
        'desarrollador' => 'Tu Empresa',
        'soporte_email' => 'soporte@tuempresa.com',
        'backup_path' => 'storage/backups/',
        'logs_path' => 'storage/logs/',
        'max_file_upload' => 10240, // KB
        'timezone_default' => 'America/Lima'
    ]
    ,
    'ventas' => [
        // Si es true, genera comprobante electrónico de forma asíncrona
        'comprobante_async' => env('VENTA_COMPROBANTE_ASYNC', true),
    ]
];