<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Exception;

class PdfTemplateService
{
    /**
     * Formatos de PDF disponibles
     */
    private $formats = [
        'a4' => 'A4',
        'a5' => 'A5', 
        '80mm' => '80mm Ticket',
        '50mm' => '50mm Ticket',
        'ticket' => 'Ticket (Legacy)'
    ];

    /**
     * Tipos de documentos disponibles
     */
    private $documentTypes = [
        'invoice' => 'factura',
        'boleta' => 'boleta',
        'credit_note' => 'nota_credito',
        'debit_note' => 'nota_debito',
        'dispatch_guide' => 'guia_remision'
    ];

    /**
     * Obtener ruta de plantilla
     */
    public function getTemplatePath($documentType, $format)
    {
        // Mapear tipo de documento
        $templateType = $this->documentTypes[$documentType] ?? $documentType;
        
        // Mapear formato
        $templateFormat = $this->getTemplateFormat($format);
        
        // Construir ruta de plantilla
        $templatePath = "pdf.{$templateFormat}.{$templateType}";
        
        // Verificar si existe la plantilla específica
        if (View::exists($templatePath)) {
            return $templatePath;
        }
        
        // Fallback a plantilla A4 si no existe el formato específico
        $fallbackPath = "pdf.a4.{$templateType}";
        if (View::exists($fallbackPath)) {
            return $fallbackPath;
        }
        
        throw new Exception("No se encontró plantilla para {$documentType} en formato {$format}");
    }

    /**
     * Mapear formato a directorio de plantilla
     */
    private function getTemplateFormat($format)
    {
        $formatMap = [
            'a4' => 'a4',
            'a5' => 'a5',
            '80mm' => '80mm',
            '50mm' => '50mm',
            'ticket' => '80mm' // ticket usa el formato 80mm por defecto
        ];

        return $formatMap[$format] ?? 'a4';
    }

    /**
     * Obtener formatos disponibles
     */
    public function getAvailableFormats()
    {
        return $this->formats;
    }

    /**
     * Obtener tipos de documentos disponibles
     */
    public function getAvailableDocumentTypes()
    {
        return $this->documentTypes;
    }

    /**
     * Verificar si existe plantilla
     */
    public function templateExists($documentType, $format)
    {
        try {
            $this->getTemplatePath($documentType, $format);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Listar todas las plantillas disponibles
     */
    public function listAvailableTemplates()
    {
        $templates = [];
        
        foreach ($this->documentTypes as $type => $templateName) {
            foreach ($this->formats as $format => $formatName) {
                if ($this->templateExists($type, $format)) {
                    $templates[] = [
                        'type' => $type,
                        'format' => $format,
                        'template_name' => $templateName,
                        'format_name' => $formatName,
                        'path' => $this->getTemplatePath($type, $format)
                    ];
                }
            }
        }
        
        return $templates;
    }

    /**
     * Obtener configuración de papel para formato
     */
    public function getPaperConfig($format)
    {
        $configs = [
            'a4' => [
                'width' => '210mm',
                'height' => '297mm',
                'margin' => '10mm'
            ],
            'a5' => [
                'width' => '148mm', 
                'height' => '210mm',
                'margin' => '8mm'
            ],
            '80mm' => [
                'width' => '80mm',
                'height' => 'auto',
                'margin' => '2mm'
            ],
            '50mm' => [
                'width' => '50mm',
                'height' => 'auto', 
                'margin' => '1mm'
            ],
            'ticket' => [
                'width' => '80mm',
                'height' => 'auto',
                'margin' => '2mm'
            ]
        ];

        return $configs[$format] ?? $configs['a4'];
    }

    /**
     * Validar formato
     */
    public function isValidFormat($format)
    {
        return array_key_exists($format, $this->formats);
    }

    /**
     * Validar tipo de documento
     */
    public function isValidDocumentType($type)
    {
        return array_key_exists($type, $this->documentTypes);
    }
}