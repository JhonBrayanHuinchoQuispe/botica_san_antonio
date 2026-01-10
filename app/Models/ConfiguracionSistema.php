<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuracion_sistema';

    protected $fillable = [
        'igv_habilitado',
        'igv_porcentaje',
        'igv_nombre',
        'incluir_igv_precios',
        'mostrar_igv_tickets',
        'descuentos_habilitados',
        'descuento_maximo_porcentaje',
        'requiere_autorizacion_descuento',
        'descuento_sin_autorizacion_max',
        'promociones_habilitadas',
        'tipos_promocion',
        'serie_boleta',
        'serie_factura',
        'serie_ticket',
        'numeracion_boleta',
        'numeracion_factura',
        'numeracion_ticket',
        'generar_pdf_automatico',
        'moneda',
        'simbolo_moneda',
        'decimales',
        'imprimir_automatico',
        'impresora_predeterminada',
        'nombre_empresa',
        'ruc_empresa',
        'direccion_empresa',
        'telefono_empresa',
        'email_empresa',
        'logo_empresa',
        'impresora_principal',
        'impresora_tickets',
        'impresora_reportes',
        'copias_ticket',
        'papel_ticket_ancho',
        'ticket_mostrar_logo',
        'ticket_mostrar_direccion',
        'ticket_mostrar_telefono',
        'ticket_mostrar_igv',
        'ticket_mensaje_pie',
        'ticket_ancho_papel',
        'ticket_margen_superior',
        'ticket_margen_inferior',
        'alertas_stock_minimo',
        'stock_minimo_global',
        'alertas_vencimiento',
        'dias_alerta_vencimiento',
        'alertas_email',
        'email_alertas',
        'alertas_sistema'
    ];

    protected $casts = [
        'igv_habilitado' => 'boolean',
        'igv_porcentaje' => 'decimal:2',
        'incluir_igv_precios' => 'boolean',
        'mostrar_igv_tickets' => 'boolean',
        'descuentos_habilitados' => 'boolean',
        'descuento_maximo_porcentaje' => 'decimal:2',
        'requiere_autorizacion_descuento' => 'boolean',
        'descuento_sin_autorizacion_max' => 'decimal:2',
        'promociones_habilitadas' => 'boolean',
        'tipos_promocion' => 'array',
        'numeracion_boleta' => 'integer',
        'numeracion_factura' => 'integer',
        'numeracion_ticket' => 'integer',
        'generar_pdf_automatico' => 'boolean',
        'decimales' => 'integer',
        'imprimir_automatico' => 'boolean',
        'copias_ticket' => 'integer',
        'papel_ticket_ancho' => 'integer',
        'ticket_mostrar_logo' => 'boolean',
        'ticket_mostrar_direccion' => 'boolean',
        'ticket_mostrar_telefono' => 'boolean',
        'ticket_mostrar_igv' => 'boolean',
        'ticket_ancho_papel' => 'integer',
        'ticket_margen_superior' => 'integer',
        'ticket_margen_inferior' => 'integer',
        'alertas_stock_minimo' => 'boolean',
        'stock_minimo_global' => 'integer',
        'alertas_vencimiento' => 'boolean',
        'dias_alerta_vencimiento' => 'integer',
        'alertas_email' => 'boolean',
        'alertas_sistema' => 'boolean'
    ];

    /**
     * Obtener la configuración actual del sistema
     */
    public static function obtenerConfiguracion()
    {
        return self::first() ?? self::create([
            'igv_habilitado' => false,
            'igv_porcentaje' => 18.00,
            'igv_nombre' => 'IGV',
            'descuentos_habilitados' => true,
            'descuento_maximo_porcentaje' => 50.00,
            'requiere_autorizacion_descuento' => false,
            'descuento_sin_autorizacion_max' => 10.00,
            'promociones_habilitadas' => true,
            'tipos_promocion' => [
                '2x1' => ['activo' => false, 'descripcion' => 'Lleva 2 paga 1'],
                '3x2' => ['activo' => false, 'descripcion' => 'Lleva 3 paga 2'],
                'descuento_cantidad' => ['activo' => false, 'descripcion' => 'Descuento por cantidad']
            ]
        ]);
    }

    /**
     * Calcular IGV para un monto
     */
    public function calcularIGV($monto)
    {
        if (!$this->igv_habilitado) {
            return 0;
        }
        
        return round($monto * ($this->igv_porcentaje / 100), $this->decimales);
    }

    /**
     * Verificar si un descuento requiere autorización
     */
    public function requiereAutorizacion($porcentajeDescuento)
    {
        if (!$this->requiere_autorizacion_descuento) {
            return false;
        }
        
        return $porcentajeDescuento > $this->descuento_sin_autorizacion_max;
    }

    /**
     * Validar si un descuento es válido
     */
    public function validarDescuento($porcentajeDescuento)
    {
        if (!$this->descuentos_habilitados) {
            return ['valido' => false, 'mensaje' => 'Los descuentos no están habilitados'];
        }
        
        if ($porcentajeDescuento > $this->descuento_maximo_porcentaje) {
            return ['valido' => false, 'mensaje' => 'El descuento excede el máximo permitido'];
        }
        
        return ['valido' => true, 'requiere_autorizacion' => $this->requiereAutorizacion($porcentajeDescuento)];
    }
}