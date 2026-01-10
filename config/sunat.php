<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración SUNAT
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con los servicios de SUNAT
    | para facturación electrónica
    |
    */

    // Ambiente de SUNAT (beta para pruebas, produccion para producción)
    'ambiente' => env('SUNAT_AMBIENTE', 'beta'),
    
    // Modo de pruebas
    'modo_pruebas' => env('SUNAT_MODO_PRUEBAS', true),

    // URLs de servicios SUNAT
    'urls' => [
        'beta' => [
            'factura' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
            'guia' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService',
            'retencion' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService',
            'consulta' => 'https://e-beta.sunat.gob.pe/ol-ti-itconsvalicpe-beta/billValidService',
        ],
        'produccion' => [
            'factura' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
            'guia' => 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService',
            'retencion' => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService',
            'consulta' => 'https://e-factura.sunat.gob.pe/ol-ti-itconsvalicpe/billValidService',
        ],
    ],

    // Credenciales SOL
    'sol' => [
        'user' => env('SUNAT_SOL_USER'),
        'password' => env('SUNAT_SOL_PASSWORD'),
    ],

    // Datos de la empresa
    'empresa' => [
        'ruc' => env('SUNAT_RUC'),
        'razon_social' => env('SUNAT_RAZON_SOCIAL'),
        'nombre_comercial' => env('SUNAT_NOMBRE_COMERCIAL'),
        'direccion' => env('SUNAT_DIRECCION'),
        'ubigeo' => env('SUNAT_UBIGEO'),
        'departamento' => env('SUNAT_DEPARTAMENTO'),
        'provincia' => env('SUNAT_PROVINCIA'),
        'distrito' => env('SUNAT_DISTRITO'),
        'telefono' => env('SUNAT_TELEFONO'),
        'email' => env('SUNAT_EMAIL'),
    ],

    // Series de comprobantes
    'series' => [
        'boleta' => env('SUNAT_SERIE_BOLETA', 'B001'),
        'factura' => env('SUNAT_SERIE_FACTURA', 'F001'),
        'nota_credito' => env('SUNAT_SERIE_NOTA_CREDITO', 'BC01'),
        'nota_debito' => env('SUNAT_SERIE_NOTA_DEBITO', 'BD01'),
    ],

    // Certificado digital
    'certificado' => [
        'path' => env('SUNAT_CERTIFICATE_PATH'),
        'password' => env('SUNAT_CERTIFICATE_PASSWORD'),
    ],

    // Configuración de archivos
    'archivos' => [
        'xml_path' => storage_path('app/sunat/xml'),
        'cdr_path' => storage_path('app/sunat/cdr'),
        'pdf_path' => storage_path('app/sunat/pdf'),
    ],
    
    // Códigos de tipos de documento
    'tipos_documento' => [
        '01' => 'Factura',
        '03' => 'Boleta de Venta',
        '07' => 'Nota de Crédito',
        '08' => 'Nota de Débito',
        '09' => 'Guía de Remisión',
        'RC' => 'Resumen de Comprobantes',
        'RA' => 'Resumen de Anulaciones',
    ],

    // Códigos de tipos de operación
    'tipos_operacion' => [
        '0101' => 'Venta interna',
        '0102' => 'Venta interna - Anticipos',
        '0200' => 'Exportación',
        '0401' => 'Venta interna - No gravada',
        '1001' => 'Operación onerosa',
        '1002' => 'Operación gratuita',
    ],

    // Códigos de moneda
    'monedas' => [
        'PEN' => 'Soles',
        'USD' => 'Dólares Americanos',
        'EUR' => 'Euros',
    ],

    // Códigos de tipos de afectación IGV
    'tipos_afectacion_igv' => [
        '10' => 'Gravado - Operación Onerosa',
        '20' => 'Exonerado - Operación Onerosa',
        '30' => 'Inafecto - Operación Onerosa',
        '40' => 'Exportación',
    ],

    // Códigos de unidades de medida
    'unidades_medida' => [
        'NIU' => 'Unidad',
        'KGM' => 'Kilogramo',
        'GRM' => 'Gramo',
        'LTR' => 'Litro',
        'MLT' => 'Mililitro',
        'MTR' => 'Metro',
        'CJA' => 'Caja',
        'PQT' => 'Paquete',
        'DOC' => 'Docena',
        'SET' => 'Juego',
        'BOT' => 'Botella',
        'BOL' => 'Bolsa',
        'LAT' => 'Lata',
        'TUB' => 'Tubo',
        'GAL' => 'Galón',
        'ZZ' => 'Unidad de servicio',
    ],

    // Configuración de logs
    'logs' => [
        'habilitar' => true,
        'nivel' => env('SUNAT_LOG_LEVEL', 'info'),
        'archivo' => storage_path('logs/sunat.log')
    ]
];