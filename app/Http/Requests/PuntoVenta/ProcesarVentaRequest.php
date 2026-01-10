<?php

namespace App\Http\Requests\PuntoVenta;

use Illuminate\Foundation\Http\FormRequest;

class ProcesarVentaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,yape',
            'efectivo_recibido' => 'required_if:metodo_pago,efectivo|numeric|min:0',
            'cliente_dni' => 'nullable|string|size:8',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'con_comprobante' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'productos.required' => 'Debe agregar al menos un producto',
            'productos.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'efectivo_recibido.required_if' => 'Debe especificar el efectivo recibido',
            'cliente_dni.size' => 'El DNI debe tener 8 dígitos'
        ];
    }
}