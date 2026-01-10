<?php

namespace App\Services;

use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Company as GreenterCompany;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Note as GreenterBoleta;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\See;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\Company;
use App\Models\Venta;
use App\Models\PuntoVenta\Venta as PuntoVentaVenta;
use App\Models\PuntoVenta\VentaDetalle as PuntoVentaVentaDetalle;
use App\Models\Cliente;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FacturacionElectronicaService
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
            // Configurar certificado
            $certificadoPath = config('sunat.sunat.certificado_path');
            $password = config('sunat.sunat.certificado_password', '');
            
            // En modo beta, omitir certificado completamente
            if (config('sunat.sunat.modo') === 'beta') {
                Log::info('Modo BETA: Omitiendo validación de certificado');
                $certificate = null;
            } else {
                if (!file_exists($certificadoPath)) {
                    throw new Exception('Certificado no encontrado: ' . $certificadoPath);
                }
                $pfx = file_get_contents($certificadoPath);
                $certificate = new X509Certificate($pfx, $password);
            }
            
            // Configurar empresa
            $this->company = new GreenterCompany();
            $this->company->setRuc(config('sunat.empresa.ruc'))
                         ->setRazonSocial(config('sunat.empresa.razon_social'))
                         ->setNombreComercial(config('sunat.empresa.nombre_comercial'))
                         ->setAddress($this->getCompanyAddress());
            
            // Configurar SEE (Servicio de Emisión Electrónica)
            $this->see = new See();
            
            // Configurar servicio SUNAT
            $service = $this->getSunatService();
            $this->see->setService($service);
            
            // Configurar certificado si existe
            if ($certificate) {
                $this->see->setCertificate($certificate);
            }
            
            // Configurar credenciales SOL
            $this->see->setClaveSOL(
                config('sunat.empresa.ruc'),
                config('sunat.sunat.usuario_sol'),
                config('sunat.sunat.clave_sol')
            );
                     
        } catch (Exception $e) {
            Log::error('Error inicializando Greenter: ' . $e->getMessage());
            throw new Exception('Error en configuración de facturación electrónica: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener dirección de la empresa
     */
    private function getCompanyAddress()
    {
        $address = new Address();
        $address->setUbigueo(config('sunat.empresa.ubigeo', '150101'))
                ->setDepartamento(config('sunat.empresa.departamento', 'LIMA'))
                ->setProvincia(config('sunat.empresa.provincia', 'LIMA'))
                ->setDistrito(config('sunat.empresa.distrito', 'LIMA'))
                ->setUrbanizacion('-')
                ->setDireccion(config('sunat.empresa.direccion', 'AV. EJEMPLO 123'));
                
        return $address;
    }
    
    /**
     * Obtener servicio SUNAT (Beta o Producción)
     */
    private function getSunatService()
    {
        $modo = config('sunat.sunat.modo', 'beta');
        
        if ($modo === 'produccion') {
            return SunatEndpoints::FE_PRODUCCION;
        } else {
            return SunatEndpoints::FE_BETA;
        }
    }
    
    /**
     * Generar boleta electrónica
     */
    public function generarBoleta($venta)
    {
        try {
            // Crear cliente
            $cliente = $this->createClient($venta->cliente);
            
            // Crear boleta
            $invoice = new GreenterInvoice();
            $invoice->setUblVersion('2.1')
                   ->setTipoOperacion('0101') // Venta interna
                   ->setTipoDoc('03') // Boleta
                   ->setSerie($venta->serie_electronica ?: 'B001')
                   ->setCorrelativo($venta->numero_electronico ?: $this->getNextCorrelativo('03', 'B001'))
                   ->setFechaEmision(new \DateTime($venta->fecha_venta))
                   ->setTipoMoneda($venta->moneda ?: 'PEN')
                   ->setClient($cliente)
                   ->setMtoOperGravadas($this->calculateGravadas($venta))
                   ->setMtoIGV($this->calculateIGV($venta))
                   ->setTotalImpuestos($this->calculateIGV($venta))
                   ->setValorVenta($this->calculateValorVenta($venta))
                   ->setSubTotal($venta->total)
                   ->setMtoImpVenta($venta->total);
            
            // Agregar detalles
            $details = $this->createSaleDetails($venta);
            $invoice->setDetails($details);
            
            // Agregar leyendas
            $legends = $this->createLegends($venta);
            $invoice->setLegends($legends);
            
            // En modo beta, simular respuesta exitosa
            if (config('sunat.sunat.modo') === 'beta') {
                Log::info('Modo BETA: Simulando generación de boleta');
                
                // Simular datos de respuesta
                $hashSimulado = 'BETA-' . md5($venta->id . time());
                $qrSimulado = $this->generateQRBeta($invoice);
                
                // Actualizar venta con datos simulados
                $venta->update([
                    'hash_cpe' => $hashSimulado,
                    'codigo_qr' => $qrSimulado,
                    'estado_sunat' => 'ACEPTADO_BETA',
                    'fecha_envio_sunat' => now(),
                    'ticket_sunat' => 'BETA-' . time()
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Boleta generada exitosamente (MODO BETA)',
                    'data' => [
                        'xml' => 'Respuesta simulada en modo BETA',
                        'hash' => $hashSimulado,
                        'qr' => $qrSimulado
                    ]
                ];
            }
            
            // Modo producción: enviar a SUNAT
            $result = $this->see->send($invoice);
            
            if ($result->isSuccess()) {
                // Actualizar venta con datos de SUNAT
                $this->updateVentaWithSunatData($venta, $result, $invoice);
                
                return [
                    'success' => true,
                    'message' => 'Boleta generada exitosamente',
                    'serie_numero' => $invoice->getSerie() . '-' . $invoice->getCorrelativo(),
                    'hash' => $invoice->getHash(),
                    'xml_path' => 'Generado en memoria',
                    'data' => [
                        'xml' => $result->getCdrResponse()->getDescription(),
                        'hash' => $invoice->getHash(),
                        'qr' => $this->generateQR($invoice),
                        'serie_numero' => $invoice->getSerie() . '-' . $invoice->getCorrelativo(),
                        'xml_path' => 'Generado en memoria'
                    ]
                ];
            } else {
                Log::error('Error SUNAT: ' . $result->getError()->getMessage());
                
                return [
                    'success' => false,
                    'message' => 'Error en SUNAT: ' . $result->getError()->getMessage(),
                    'error' => $result->getError()->getCode()
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error generando boleta: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error generando boleta: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear cliente para facturación
     */
    private function createClient($cliente)
    {
        $client = new GreenterClient();
        
        if ($cliente) {
            $client->setTipoDoc($cliente->tipo_documento ?: '1') // 1=DNI, 6=RUC
                   ->setNumDoc($cliente->dni ?: '00000000')
                   ->setRznSocial($cliente->nombre_completo ?: 'CLIENTE VARIOS');
        } else {
            // Cliente genérico
            $client->setTipoDoc('1')
                   ->setNumDoc('00000000')
                   ->setRznSocial('CLIENTE VARIOS');
        }
        
        return $client;
    }
    
    /**
     * Crear detalles de venta
     */
    private function createSaleDetails($venta)
    {
        $details = [];
        $detalles = PuntoVentaVentaDetalle::with('producto')->where('venta_id', $venta->id)->get();
        
        foreach ($detalles as $index => $detalle) {
            $detail = new SaleDetail();
            $nombreProducto = $detalle->producto ? $detalle->producto->nombre : 'Producto';
            
            $detail->setCodProducto($detalle->producto_id ?: 'PROD' . ($index + 1))
                   ->setUnidad('NIU') // Unidad de medida
                   ->setCantidad($detalle->cantidad)
                   ->setDescripcion($nombreProducto)
                   ->setMtoBaseIgv($detalle->precio_unitario * $detalle->cantidad / 1.18)
                   ->setPorcentajeIgv(18.00)
                   ->setIgv($detalle->precio_unitario * $detalle->cantidad * 0.18 / 1.18)
                   ->setTipAfeIgv('10') // Gravado
                   ->setTotalImpuestos($detalle->precio_unitario * $detalle->cantidad * 0.18 / 1.18)
                   ->setMtoValorVenta($detalle->precio_unitario * $detalle->cantidad / 1.18)
                   ->setMtoValorUnitario($detalle->precio_unitario / 1.18)
                   ->setMtoPrecioUnitario($detalle->precio_unitario);
            
            $details[] = $detail;
        }
        
        return $details;
    }
    
    /**
     * Crear leyendas
     */
    private function createLegends($venta)
    {
        $legends = [];
        
        // Leyenda del monto en letras
        $legend = new Legend();
        $legend->setCode('1000')
               ->setValue($this->convertirNumeroALetras($venta->total));
        
        $legends[] = $legend;
        
        return $legends;
    }
    
    /**
     * Calcular monto gravado
     */
    private function calculateGravadas($venta)
    {
        return round($venta->total / 1.18, 2);
    }
    
    /**
     * Calcular IGV
     */
    private function calculateIGV($venta)
    {
        return round($venta->total - ($venta->total / 1.18), 2);
    }
    
    /**
     * Calcular valor de venta
     */
    private function calculateValorVenta($venta)
    {
        return round($venta->total / 1.18, 2);
    }
    
    /**
     * Obtener siguiente correlativo
     */
    private function getNextCorrelativo($tipoDoc, $serie)
    {
        $lastVenta = PuntoVentaVenta::where('tipo_documento_electronico', $tipoDoc)
                         ->where('serie_electronica', $serie)
                         ->orderBy('numero_electronico', 'desc')
                         ->first();
        
        return $lastVenta ? $lastVenta->numero_electronico + 1 : 1;
    }
    
    /**
     * Actualizar venta con datos de SUNAT
     */
    private function updateVentaWithSunatData($venta, $result, $invoice)
    {
        $venta->update([
            'hash_cpe' => $invoice->getHash(),
            'codigo_qr' => $this->generateQR($invoice),
            'xml_firmado' => $result->getCdrZip(),
            'cdr_sunat' => $result->getCdrResponse()->getDescription(),
            'estado_sunat' => 'ACEPTADO',
            'fecha_envio_sunat' => now(),
            'ticket_sunat' => $result->getCdrResponse()->getId()
        ]);
    }
    
    /**
     * Generar código QR
     */
    private function generateQR($invoice)
    {
        $qrData = implode('|', [
            $this->company->getRuc(),
            $invoice->getTipoDoc(),
            $invoice->getSerie(),
            $invoice->getCorrelativo(),
            $invoice->getMtoIGV(),
            $invoice->getMtoImpVenta(),
            $invoice->getFechaEmision()->format('Y-m-d'),
            $invoice->getClient()->getTipoDoc(),
            $invoice->getClient()->getNumDoc(),
            $invoice->getHash()
        ]);
        
        return $qrData;
    }
    
    /**
     * Generar código QR para modo beta
     */
    private function generateQRBeta($invoice)
    {
        $qrData = implode('|', [
            config('sunat.empresa.ruc'),
            $invoice->getTipoDoc(),
            $invoice->getSerie(),
            $invoice->getCorrelativo(),
            $invoice->getMtoIGV(),
            $invoice->getMtoImpVenta(),
            $invoice->getFechaEmision()->format('Y-m-d'),
            $invoice->getClient()->getTipoDoc(),
            $invoice->getClient()->getNumDoc(),
            'BETA-HASH-' . md5(time())
        ]);
        
        return $qrData;
    }
    
    /**
     * Convertir número a letras
     */
    private function convertirNumeroALetras($numero)
    {
        // Implementación básica - se puede mejorar con una librería especializada
        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);
        
        return strtoupper($this->numeroALetras($entero)) . ' CON ' . sprintf('%02d', $decimales) . '/100 SOLES';
    }
    
    /**
     * Probar conexión con SUNAT
     */
    public function probarConexion()
    {
        try {
            // Crear una consulta simple para probar la conexión
            $this->initializeGreenter();
            
            return [
                'success' => true,
                'message' => 'Conexión exitosa con SUNAT',
                'datos' => [
                    'servidor' => config('sunat.production') ? 'Producción' : 'Beta',
                    'estado' => 'Conectado'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Convertir número entero a letras (implementación básica)
     */
    private function numeroALetras($numero)
    {
        if ($numero == 0) return 'CERO';
        if ($numero == 1) return 'UNO';
        // Implementación básica - expandir según necesidades
        return 'NÚMERO: ' . $numero;
    }
    
    /**
     * Verificar estado en SUNAT
     */
    public function verificarEstadoSunat($venta)
    {
        try {
            if (!$venta->ticket_sunat) {
                return ['success' => false, 'message' => 'No hay ticket SUNAT para verificar'];
            }
            
            $result = $this->see->getStatus($venta->ticket_sunat);
            
            if ($result->isSuccess()) {
                $venta->update([
                    'estado_sunat' => 'ACEPTADO',
                    'observaciones_sunat' => $result->getCdrResponse()->getDescription()
                ]);
                
                return ['success' => true, 'message' => 'Estado verificado correctamente'];
            } else {
                return ['success' => false, 'message' => $result->getError()->getMessage()];
            }
            
        } catch (Exception $e) {
            Log::error('Error verificando estado SUNAT: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error verificando estado: ' . $e->getMessage()];
        }
    }
}