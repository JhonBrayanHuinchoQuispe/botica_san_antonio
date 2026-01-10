<?php

namespace App\Console\Commands;

use App\Models\PuntoVenta\Venta;
use App\Models\PuntoVenta\VentaDetalle;
use App\Models\PuntoVenta\Cliente;
use App\Models\Producto;
use App\Services\FacturacionElectronicaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestSunatIntegration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sunat:test {--mode=beta : Usar modo beta de SUNAT}';

    /**
     * The description of the console command.
     */
    protected $description = 'Probar la integración completa con SUNAT usando datos de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando pruebas de integración SUNAT...');
        $this->newLine();
        
        // Verificar configuración
        if (!$this->verificarConfiguracion()) {
            return 1;
        }
        
        // Crear datos de prueba
        $venta = $this->crearVentaPrueba();
        
        if (!$venta) {
            $this->error('❌ Error al crear venta de prueba');
            return 1;
        }
        
        $this->info("✅ Venta de prueba creada: #{$venta->numero_venta}");
        
        // Probar generación de boleta
        $this->info('📄 Generando comprobante electrónico...');
        
        try {
            $facturacionService = new FacturacionElectronicaService();
            $resultado = $facturacionService->generarBoleta($venta);
            
            if ($resultado['success']) {
                $this->info("✅ Comprobante generado exitosamente:");
                
                // Acceder a los datos desde el nivel correcto
                $serieNumero = $resultado['serie_numero'] ?? $resultado['data']['serie_numero'] ?? 'N/A';
                $hash = $resultado['hash'] ?? $resultado['data']['hash'] ?? 'N/A';
                $xmlPath = $resultado['xml_path'] ?? $resultado['data']['xml_path'] ?? 'N/A';
                
                $this->line("   Serie-Número: {$serieNumero}");
                $this->line("   Hash: {$hash}");
                $this->line("   XML: {$xmlPath}");
                
                if (isset($resultado['pdf_path'])) {
                    $this->line("   PDF: {$resultado['pdf_path']}");
                }
                
                // Verificar estado en SUNAT
                $this->info('🔍 Verificando estado en SUNAT...');
                $estadoResultado = $facturacionService->verificarEstadoSunat($venta->id);
                
                if ($estadoResultado) {
                    $this->info('✅ Estado verificado en SUNAT');
                } else {
                    $this->warn('⚠️  No se pudo verificar el estado en SUNAT');
                }
                
            } else {
                $this->error("❌ Error al generar comprobante: {$resultado['message']}");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción al generar comprobante: {$e->getMessage()}");
            Log::error('Error en prueba SUNAT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        // Probar conexión
        $this->info('🌐 Probando conexión con SUNAT...');
        
        try {
            $conexionResultado = $facturacionService->probarConexion();
            
            if ($conexionResultado['success']) {
                $this->info('✅ Conexión exitosa con SUNAT');
                if (isset($conexionResultado['datos'])) {
                    $this->line('   Datos de respuesta: ' . json_encode($conexionResultado['datos'], JSON_PRETTY_PRINT));
                }
            } else {
                $this->warn("⚠️  Problema de conexión: {$conexionResultado['message']}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error de conexión: {$e->getMessage()}");
        }
        
        $this->newLine();
        $this->info('🎉 Pruebas de integración completadas');
        
        // Mostrar resumen
        $this->mostrarResumen($venta);
        
        return 0;
    }
    
    /**
     * Verificar configuración necesaria
     */
    private function verificarConfiguracion()
    {
        $this->info('🔧 Verificando configuración...');
        
        $errores = [];
        
        // Verificar RUC
        if (!config('sunat.empresa.ruc')) {
            $errores[] = 'RUC de empresa no configurado';
        }
        
        // Verificar credenciales SOL
        if (!config('sunat.sunat.usuario_sol') || !config('sunat.sunat.clave_sol')) {
            $errores[] = 'Credenciales SOL no configuradas';
        }
        
        // Verificar certificado (solo en modo producción)
        $certificadoPath = config('sunat.sunat.certificado_path');
        if ($this->option('mode') !== 'beta') {
            if (!$certificadoPath || !file_exists($certificadoPath)) {
                $errores[] = 'Certificado digital no encontrado';
            }
        } else {
            // En modo beta, solo verificar que el archivo exista
            if (!$certificadoPath || !file_exists($certificadoPath)) {
                $this->warn('⚠️  Certificado no encontrado, pero continuando en modo BETA');
            }
        }
        
        if (!empty($errores)) {
            $this->error('❌ Errores de configuración encontrados:');
            foreach ($errores as $error) {
                $this->line("   • {$error}");
            }
            $this->newLine();
            $this->line('💡 Configura SUNAT en: /admin/sunat/configuracion');
            return false;
        }
        
        $this->info('✅ Configuración verificada correctamente');
        return true;
    }
    
    /**
     * Crear venta de prueba
     */
    private function crearVentaPrueba()
    {
        try {
            DB::beginTransaction();
            
            // Buscar o crear cliente de prueba
            $cliente = Cliente::firstOrCreate(
                ['dni' => '12345678'],
                [
                    'nombres' => 'Cliente de Prueba',
                    'apellido_paterno' => 'SUNAT',
                    'apellido_materno' => 'Test',
                    'email' => 'prueba@sunat.test',
                    'telefono' => '999999999',
                    'direccion' => 'Av. Prueba 123, Lima'
                ]
            );
            
            // Buscar productos existentes con stock o crear uno de prueba
            $producto = Producto::where('stock_actual', '>', 0)->first();
            if (!$producto) {
                $producto = Producto::create([
                    'nombre' => 'Producto de Prueba SUNAT',
                    'codigo_barras' => 'TEST-SUNAT-001',
                    'precio_venta' => 10.00,
                    'stock_actual' => 100,
                    'stock_minimo' => 5,
                    'categoria' => 'Medicamentos',
                    'estado' => 'activo'
                ]);
            }
            
            // Crear venta
            $venta = Venta::create([
                'numero_venta' => 'TEST-' . now()->format('YmdHis'),
                'cliente_id' => $cliente->id,
                'usuario_id' => 1,
                'tipo_comprobante' => 'boleta',
                'subtotal' => 10.00,
                'igv' => 1.80,
                'total' => 11.80,
                'metodo_pago' => 'efectivo',
                'estado' => 'completada',
                'fecha_venta' => now()
            ]);
            
            // Crear detalle de venta usando insert directo para evitar observers
            DB::table('venta_detalles')->insert([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'precio_unitario' => 10.00,
                'subtotal' => 10.00,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Actualizar stock del producto manualmente (para evitar validaciones del observer)
            $producto->decrement('stock_actual', 1);
            
            DB::commit();
            
            return $venta;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al crear venta de prueba: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Mostrar resumen de la prueba
     */
    private function mostrarResumen($venta)
    {
        $this->info('📊 RESUMEN DE PRUEBA');
        $this->line('═══════════════════════════════════════');
        $this->line("Venta ID: {$venta->id}");
        $this->line("Número: {$venta->numero_venta}");
        $this->line("Cliente: {$venta->cliente->nombre}");
        $this->line("Total: S/ {$venta->total}");
        $this->line("Tipo: {$venta->tipo_comprobante}");
        
        if ($venta->comprobante_electronico) {
            $ce = $venta->comprobante_electronico;
            $this->line("Serie-Número: {$ce->serie_numero}");
            $this->line("Estado SUNAT: {$ce->estado_sunat}");
            $this->line("Hash: {$ce->hash}");
        }
        
        $this->line('═══════════════════════════════════════');
        $this->newLine();
        
        if ($this->option('mode') === 'beta') {
            $this->warn('⚠️  Ejecutado en modo BETA - Los comprobantes no son válidos para producción');
        } else {
            $this->info('✅ Ejecutado en modo PRODUCCIÓN');
        }
    }
}