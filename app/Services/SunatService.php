<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunatService
{
    private $config;
    private $token;
    private $ambiente;
    
    public function __construct()
    {
        $this->config = config('sunat');
        $this->ambiente = $this->config['ambiente'];
    }
    
    /**
     * Generar token de acceso para las APIs de SUNAT
     */
    public function generarToken()
    {
        try {
            $url = str_replace(
                '{client_id}', 
                $this->config['api']['id'], 
                $this->config['urls'][$this->ambiente]['token']
            );
            
            $response = Http::asForm()->post($url, [
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes',
                'client_id' => $this->config['api']['id'],
                'client_secret' => $this->config['api']['clave'],
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['access_token'];
                
                Log::info('Token SUNAT generado exitosamente');
                return $this->token;
            }
            
            throw new Exception('Error al generar token: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('Error generando token SUNAT: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Consultar validez de un comprobante
     */
    public function consultarComprobante($ruc, $tipo, $serie, $numero)
    {
        try {
            if (!$this->token) {
                $this->generarToken();
            }
            
            $url = $this->config['urls'][$this->ambiente]['consulta'];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'numRuc' => $ruc,
                'codComp' => $tipo, // 03 para boleta
                'numeroSerie' => $serie,
                'numero' => $numero,
                'fechaEmision' => date('d/m/Y'),
                'monto' => 0 // Para consulta no es necesario
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new Exception('Error consultando comprobante: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('Error consultando comprobante: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verificar si estamos en modo pruebas
     */
    public function esModoProduccion()
    {
        return $this->ambiente === 'produccion';
    }
    
    /**
     * Obtener configuración de la empresa
     */
    public function getDatosEmpresa()
    {
        return $this->config['empresa'];
    }
    
    /**
     * Obtener serie de boleta
     */
    public function getSerieBoleta()
    {
        return $this->config['series']['boleta'];
    }
} 