<?php

namespace App\Services;

use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Company as GreenterCompany;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Note as GreenterNote;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\See;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\Company;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class GreenterService
{
    private $see;
    private $company;
    
    public function __construct()
    {
        $this->initializeGreenter();
    }
    
    /**
     * Inicializar configuración de Greenter
     */
    private function initializeGreenter()
    {
        try {
            // Obtener configuración de la empresa principal
            $company = Company::where('activo', true)->first();
            
            if (!$company) {
                throw new Exception('No se encontró empresa activa configurada');
            }

            // Configurar certificado
            $certificate = null;
            if ($company->certificado_path && file_exists($company->certificado_path)) {
                $pfx = file_get_contents($company->certificado_path);
                $certificate = new X509Certificate($pfx, $company->certificado_password);
            }
            
            // Configurar empresa para Greenter
            $this->company = new GreenterCompany();
            $this->company->setRuc($company->ruc)
                         ->setRazonSocial($company->razon_social)
                         ->setNombreComercial($company->nombre_comercial)
                         ->setAddress($this->getCompanyAddress($company));
            
            // Configurar SEE (Servicio de Emisión Electrónica)
            $this->see = new See();
            
            // Configurar servicio SUNAT
            $service = $this->getSunatService($company->modo_prueba);
            $this->see->setService($service);
            
            // Configurar certificado si existe
            if ($certificate) {
                $this->see->setCertificate($certificate);
            }
            
            // Configurar credenciales SOL
            $this->see->setClaveSOL(
                $company->ruc,
                $company->usuario_sol,
                $company->clave_sol
            );
                     
        } catch (Exception $e) {
            Log::error('Error inicializando Greenter: ' . $e->getMessage());
            // No lanzar excepción para permitir que la aplicación arranque aunque no haya DB o configuración
            // throw new Exception('Error en configuración de facturación electrónica: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener dirección de la empresa para Greenter
     */
    private function getCompanyAddress(Company $company)
    {
        $address = new Address();
        $address->setUbigueo($company->ubigeo ?: '150101')
                ->setDepartamento($company->departamento ?: 'LIMA')
                ->setProvincia($company->provincia ?: 'LIMA')
                ->setDistrito($company->distrito ?: 'LIMA')
                ->setUrbanizacion($company->urbanizacion ?: '-')
                ->setDireccion($company->direccion ?: 'AV. EJEMPLO 123');
                
        return $address;
    }

    /**
     * Obtener servicio SUNAT según ambiente
     */
    private function getSunatService($modoPrueba = true)
    {
        if ($modoPrueba) {
            return SunatEndpoints::FE_BETA;
        } else {
            return SunatEndpoints::FE_PRODUCCION;
        }
    }

    /**
     * Enviar factura a SUNAT
     */
    public function enviarFactura(Invoice $invoice)
    {
        try {
            $greenterInvoice = $this->convertToGreenterInvoice($invoice);
            
            $result = $this->see->send($greenterInvoice);
            
            // Actualizar invoice con resultados
            $this->updateInvoiceWithResult($invoice, $result, $greenterInvoice);
            
            return $result;

        } catch (Exception $e) {
            Log::error('Error enviando factura a SUNAT: ' . $e->getMessage());
            
            // Actualizar invoice con error
            $invoice->update([
                'estado_sunat' => 'ERROR',
                'codigo_error' => $e->getCode(),
                'mensaje_error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Enviar boleta a SUNAT
     */
    public function enviarBoleta(Boleta $boleta)
    {
        try {
            $greenterNote = $this->convertToGreenterNote($boleta);
            
            $result = $this->see->send($greenterNote);
            
            // Actualizar boleta con resultados
            $this->updateBoletaWithResult($boleta, $result, $greenterNote);
            
            return $result;

        } catch (Exception $e) {
            Log::error('Error enviando boleta a SUNAT: ' . $e->getMessage());
            
            // Actualizar boleta con error
            $boleta->update([
                'estado_sunat' => 'ERROR',
                'codigo_error' => $e->getCode(),
                'mensaje_error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Convertir Invoice a formato Greenter
     */
    private function convertToGreenterInvoice(Invoice $invoice)
    {
        $invoice->load(['details', 'legends', 'client']);

        $greenterInvoice = new GreenterInvoice();
        
        // Datos básicos
        $greenterInvoice->setUblVersion('2.1')
                       ->setTipoOperacion($invoice->tipo_operacion)
                       ->setTipoDoc($invoice->tipo_documento)
                       ->setSerie($invoice->serie)
                       ->setCorrelativo($invoice->numero)
                       ->setFechaEmision(new \DateTime($invoice->fecha_emision))
                       ->setTipoMoneda($invoice->tipo_moneda);

        // Cliente
        $client = new GreenterClient();
        $client->setTipoDoc($invoice->cliente_tipo_documento)
               ->setNumDoc($invoice->cliente_numero_documento)
               ->setRznSocial($invoice->cliente_razon_social);

        if ($invoice->cliente_direccion) {
            $client->setAddress($this->createClientAddress($invoice));
        }

        $greenterInvoice->setClient($client);

        // Empresa
        $greenterInvoice->setCompany($this->company);

        // Detalles
        $details = [];
        foreach ($invoice->details as $detail) {
            $saleDetail = new SaleDetail();
            $saleDetail->setCodProducto($detail->codigo_interno)
                      ->setUnidad($detail->codigo_unidad_medida)
                      ->setDescripcion($detail->descripcion)
                      ->setCantidad($detail->cantidad)
                      ->setMtoValorUnitario($detail->valor_unitario)
                      ->setMtoValorVenta($detail->valor_total)
                      ->setMtoBaseIgv($detail->total_base_igv)
                      ->setPorcentajeIgv($detail->porcentaje_igv)
                      ->setIgv($detail->total_igv)
                      ->setTipAfeIgv($detail->codigo_tipo_afectacion_igv)
                      ->setTotalImpuestos($detail->total_impuestos)
                      ->setMtoPrecioUnitario($detail->precio_unitario);

            $details[] = $saleDetail;
        }
        $greenterInvoice->setDetails($details);

        // Leyendas
        $legends = [];
        foreach ($invoice->legends as $legend) {
            $greenterLegend = new Legend();
            $greenterLegend->setCode($legend->codigo)
                          ->setValue($legend->descripcion);
            $legends[] = $greenterLegend;
        }
        $greenterInvoice->setLegends($legends);

        // Totales
        $greenterInvoice->setMtoOperGravadas($invoice->total_operaciones_gravadas)
                       ->setMtoOperInafectas($invoice->total_operaciones_inafectas)
                       ->setMtoOperExoneradas($invoice->total_operaciones_exoneradas)
                       ->setMtoIGV($invoice->total_igv)
                       ->setTotalImpuestos($invoice->total_impuestos)
                       ->setValorVenta($invoice->total_valor_venta)
                       ->setMtoImpVenta($invoice->total_precio_venta);

        return $greenterInvoice;
    }

    /**
     * Convertir Boleta a formato Greenter Note
     */
    private function convertToGreenterNote(Boleta $boleta)
    {
        $boleta->load(['details', 'legends', 'client']);

        $greenterNote = new GreenterNote();
        
        // Datos básicos
        $greenterNote->setUblVersion('2.1')
                    ->setTipoDoc($boleta->tipo_documento)
                    ->setSerie($boleta->serie)
                    ->setCorrelativo($boleta->numero)
                    ->setFechaEmision(new \DateTime($boleta->fecha_emision))
                    ->setTipoMoneda($boleta->tipo_moneda);

        // Cliente
        $client = new GreenterClient();
        $client->setTipoDoc($boleta->cliente_tipo_documento)
               ->setNumDoc($boleta->cliente_numero_documento)
               ->setRznSocial($boleta->cliente_razon_social);

        $greenterNote->setClient($client);

        // Empresa
        $greenterNote->setCompany($this->company);

        // Detalles
        $details = [];
        foreach ($boleta->details as $detail) {
            $saleDetail = new SaleDetail();
            $saleDetail->setCodProducto($detail->codigo_interno)
                      ->setUnidad($detail->codigo_unidad_medida)
                      ->setDescripcion($detail->descripcion)
                      ->setCantidad($detail->cantidad)
                      ->setMtoValorUnitario($detail->valor_unitario)
                      ->setMtoValorVenta($detail->valor_total)
                      ->setMtoBaseIgv($detail->total_base_igv)
                      ->setPorcentajeIgv($detail->porcentaje_igv)
                      ->setIgv($detail->total_igv)
                      ->setTipAfeIgv($detail->codigo_tipo_afectacion_igv)
                      ->setTotalImpuestos($detail->total_impuestos)
                      ->setMtoPrecioUnitario($detail->precio_unitario);

            $details[] = $saleDetail;
        }
        $greenterNote->setDetails($details);

        // Leyendas
        $legends = [];
        foreach ($boleta->legends as $legend) {
            $greenterLegend = new Legend();
            $greenterLegend->setCode($legend->codigo)
                          ->setValue($legend->descripcion);
            $legends[] = $greenterLegend;
        }
        $greenterNote->setLegends($legends);

        // Totales
        $greenterNote->setMtoOperGravadas($boleta->total_operaciones_gravadas)
                    ->setMtoOperInafectas($boleta->total_operaciones_inafectas)
                    ->setMtoOperExoneradas($boleta->total_operaciones_exoneradas)
                    ->setMtoIGV($boleta->total_igv)
                    ->setTotalImpuestos($boleta->total_impuestos)
                    ->setValorVenta($boleta->total_valor_venta)
                    ->setMtoImpVenta($boleta->total_precio_venta);

        return $greenterNote;
    }

    /**
     * Crear dirección del cliente
     */
    private function createClientAddress($document)
    {
        $address = new Address();
        $address->setDireccion($document->cliente_direccion ?: '-');
        return $address;
    }

    /**
     * Actualizar factura con resultado de SUNAT
     */
    private function updateInvoiceWithResult(Invoice $invoice, $result, $greenterInvoice)
    {
        $updateData = [
            'enviado_sunat' => true,
            'fecha_envio_sunat' => now(),
            'xml_unsigned' => $this->see->getFactory()->getLastXml(),
            'xml_signed' => $this->see->getXmlSigned($greenterInvoice),
        ];

        if ($result->isSuccess()) {
            $updateData['estado_sunat'] = 'ACEPTADO';
            $updateData['cdr'] = $result->getCdrResponse();
            $updateData['hash'] = $greenterInvoice->getHash();
            
            // Generar QR
            $updateData['qr'] = $this->generateQrCode($invoice);
        } else {
            $updateData['estado_sunat'] = 'RECHAZADO';
            $updateData['codigo_error'] = $result->getError()->getCode();
            $updateData['mensaje_error'] = $result->getError()->getMessage();
        }

        $invoice->update($updateData);
    }

    /**
     * Actualizar boleta con resultado de SUNAT
     */
    private function updateBoletaWithResult(Boleta $boleta, $result, $greenterNote)
    {
        $updateData = [
            'enviado_sunat' => true,
            'fecha_envio_sunat' => now(),
            'xml_unsigned' => $this->see->getFactory()->getLastXml(),
            'xml_signed' => $this->see->getXmlSigned($greenterNote),
        ];

        if ($result->isSuccess()) {
            $updateData['estado_sunat'] = 'ACEPTADO';
            $updateData['cdr'] = $result->getCdrResponse();
            $updateData['hash'] = $greenterNote->getHash();
            
            // Generar QR
            $updateData['qr'] = $this->generateQrCode($boleta);
        } else {
            $updateData['estado_sunat'] = 'RECHAZADO';
            $updateData['codigo_error'] = $result->getError()->getCode();
            $updateData['mensaje_error'] = $result->getError()->getMessage();
        }

        $boleta->update($updateData);
    }

    /**
     * Generar código QR
     */
    private function generateQrCode($document)
    {
        try {
            $company = Company::where('activo', true)->first();
            
            $qrString = implode('|', [
                $company->ruc,
                $document->tipo_documento,
                $document->serie,
                $document->numero,
                $document->total_igv,
                $document->precio_total,
                $document->fecha_emision,
                $document->cliente_tipo_documento,
                $document->cliente_numero_documento,
                $document->hash ?? ''
            ]);

            $qrCode = new QrCode($qrString);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            $filename = "qr_{$document->tipo_documento}_{$document->serie}_{$document->numero}.png";
            $path = "qr_codes/{$filename}";
            
            Storage::disk('public')->put($path, $result->getString());
            
            return Storage::disk('public')->url($path);

        } catch (Exception $e) {
            Log::error('Error generando QR: ' . $e->getMessage());
            return null;
        }
    }
}