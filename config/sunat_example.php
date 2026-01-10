<?php

/**
 * Archivo de configuración de ejemplo para SUNAT
 * 
 * Copia este archivo como config/sunat.php y completa los datos reales
 * 
 * IMPORTANTE: No subir este archivo con datos reales al repositorio
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Empresa
    |--------------------------------------------------------------------------
    |
    | Datos de la empresa emisora de comprobantes
    |
    */
    'empresa' => [
        'ruc' => '20123456789', // RUC de tu empresa
        'razon_social' => 'MI EMPRESA S.A.C.',
        'nombre_comercial' => 'Mi Empresa',
        'direccion' => 'AV. EJEMPLO 123, LIMA, LIMA, PERU',
        'ubigeo' => '150101', // Código de ubigeo
        'distrito' => 'LIMA',
        'provincia' => 'LIMA',
        'departamento' => 'LIMA',
        'pais' => 'PE',
        'telefono' => '01-1234567',
        'email' => 'facturacion@miempresa.com'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración SUNAT
    |--------------------------------------------------------------------------
    |
    | Credenciales y configuración para conectar con SUNAT
    |
    */
    'sunat' => [
        // Credenciales SOL (Sistema de Operaciones en Línea)
        'usuario_sol' => 'MODDATOS', // Usuario SOL de SUNAT
        'clave_sol' => 'MODDATOS', // Clave SOL de SUNAT
        
        // Certificado digital
        'certificado_path' => storage_path('certificates/certificado.p12'),
        'certificado_password' => 'password_del_certificado',
        
        // URLs de SUNAT
        'urls' => [
            'produccion' => [
                'factura' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
                'guia' => 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService',
                'consulta' => 'https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService'
            ],
            'beta' => [
                'factura' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
                'guia' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService',
                'consulta' => 'https://e-beta.sunat.gob.pe/ol-it-wsconscpegem-beta/billConsultService'
            ]
        ],
        
        // Modo de operación (beta o produccion)
        'modo' => env('SUNAT_MODE', 'beta'),
        
        // Series de comprobantes
        'series' => [
            'boleta' => 'B001',
            'factura' => 'F001',
            'nota_credito' => 'BC01',
            'nota_debito' => 'BD01'
        ],
        
        // Configuración de archivos
        'rutas' => [
            'xml' => storage_path('app/sunat/xml'),
            'pdf' => storage_path('app/sunat/pdf'),
            'cdr' => storage_path('app/sunat/cdr')
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Comprobantes
    |--------------------------------------------------------------------------
    |
    | Configuración específica para la generación de comprobantes
    |
    */
    'comprobantes' => [
        // Moneda por defecto
        'moneda_defecto' => 'PEN',
        
        // IGV (18%)
        'igv_porcentaje' => 18,
        
        // Códigos de tipo de documento
        'tipos_documento' => [
            'dni' => '1',
            'carnet_extranjeria' => '4',
            'ruc' => '6',
            'pasaporte' => '7'
        ],
        
        // Códigos de tipo de comprobante
        'tipos_comprobante' => [
            'factura' => '01',
            'boleta' => '03',
            'nota_credito' => '07',
            'nota_debito' => '08'
        ],
        
        // Códigos de método de pago
        'metodos_pago' => [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    |
    | Configuración para el registro de actividades
    |
    */
    'logs' => [
        'habilitar' => true,
        'nivel' => 'info', // debug, info, warning, error
        'archivo' => storage_path('logs/sunat.log')
    ]
];