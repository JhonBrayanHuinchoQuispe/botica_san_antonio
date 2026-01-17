<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\Producto;

class ProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productoId = $this->route('producto') ? $this->route('producto')->id : null;
        
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\,\(\)]+$/' // Solo letras, números y algunos símbolos
            ],
            'codigo_barras' => [
                'required',
                'string',
                'max:50',
                'regex:/^[0-9A-Za-z\-]+$/', // Solo números, letras y guiones
                Rule::unique('productos', 'codigo_barras')->ignore($productoId)
            ],
            'lote' => [
                'required',
                'string',
                'max:100'
            ],
            'categoria' => [
                'required',
                'string',
                'max:100'
            ],
            'marca' => [
                'required',
                'string',
                'max:100'
            ],
            'presentacion' => [
                'required',
                'string',
                'max:100'
            ],
            'concentracion' => [
                'required',
                'string',
                'max:100'
            ],
            'proveedor_id' => [
                'nullable',
                'integer',
                'exists:proveedores,id'
            ],
            'stock_actual' => [
                'required',
                'integer',
                'min:0',
                'max:999999'
            ],
            'stock_minimo' => [
                'required',
                'integer',
                'min:0',
                'max:999999'
            ],
            'ubicacion' => [
                'nullable',
                'string',
                'max:100'
            ],
            'fecha_fabricacion' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'fecha_vencimiento' => [
                'nullable',
                'date',
                'after:fecha_fabricacion',
                'after:today'
            ],
            'precio_compra' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999.99'
            ],
            'precio_venta' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
                'gte:precio_compra'
            ],
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['Normal', 'Bajo stock', 'Por vencer', 'Vencido'])
            ],
            'imagen' => [
                'sometimes',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:2048' // 2MB
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números y algunos símbolos básicos.',
            
            'codigo_barras.required' => 'El código de barras es obligatorio.',
            'codigo_barras.unique' => 'Este código de barras ya está registrado.',
            'codigo_barras.max' => 'El código de barras no puede exceder 50 caracteres.',
            'codigo_barras.regex' => 'El código de barras solo puede contener números, letras y guiones.',
            
            'lote.required' => 'El lote es obligatorio.',
            'lote.max' => 'El lote no puede exceder 100 caracteres.',
            
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.max' => 'La categoría no puede exceder 100 caracteres.',
            
            'marca.required' => 'La marca es obligatoria.',
            'marca.max' => 'La marca no puede exceder 100 caracteres.',
            
            'presentacion.required' => 'La presentación es obligatoria.',
            'presentacion.max' => 'La presentación no puede exceder 100 caracteres.',
            
            'concentracion.required' => 'La concentración es obligatoria.',
            'concentracion.max' => 'La concentración no puede exceder 100 caracteres.',
            'concentracion.regex' => 'La concentración debe tener el formato: número + unidad (ej: 500mg, 2.5ml, 10%).',
            
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.integer' => 'El stock actual debe ser un número entero.',
            'stock_actual.min' => 'El stock actual no puede ser negativo.',
            'stock_actual.max' => 'El stock actual no puede exceder 999,999.',
            
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_minimo.max' => 'El stock mínimo no puede exceder 999,999.',
            
            // ubicación: ahora opcional
            'ubicacion.max' => 'La ubicación no puede exceder 100 caracteres.',
            
            'fecha_fabricacion.required' => 'La fecha de fabricación es obligatoria.',
            'fecha_fabricacion.date' => 'La fecha de fabricación debe ser una fecha válida.',
            'fecha_fabricacion.before_or_equal' => 'La fecha de fabricación no puede ser futura.',
            
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la fecha de fabricación y a hoy.',
            
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número.',
            'precio_compra.gt' => 'El precio de compra debe ser mayor a 0.',
            'precio_compra.max' => 'El precio de compra no puede exceder 999,999.99.',
            
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.gt' => 'El precio de venta debe ser mayor a 0.',
            'precio_venta.max' => 'El precio de venta no puede exceder 999,999.99.',
            'precio_venta.gte' => 'El precio de venta debe ser mayor o igual al precio de compra.',
            
            'estado.in' => 'El estado seleccionado no es válido.',
            
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif, svg.',
            'imagen.max' => 'La imagen no puede exceder 2MB.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del producto',
            'codigo_barras' => 'código de barras',
            'lote' => 'lote',
            'categoria' => 'categoría',
            'marca' => 'marca',
            'presentacion' => 'presentación',
            'concentracion' => 'concentración',
            'stock_actual' => 'stock actual',
            'stock_minimo' => 'stock mínimo',
            'ubicacion' => 'ubicación',
            'fecha_fabricacion' => 'fecha de fabricación',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'precio_compra' => 'precio de compra',
            'precio_venta' => 'precio de venta',
            'estado' => 'estado',
            'imagen' => 'imagen'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateProductoDuplicado($validator);
            // Solo validar fechas si fecha_vencimiento está presente
            if ($this->input('fecha_vencimiento')) {
                $this->validateFechas($validator);
            }
            $this->validatePrecios($validator);
        });
    }

    /**
     * Validar que no exista un producto con el mismo nombre y concentración
     */
    protected function validateProductoDuplicado(Validator $validator): void
    {
        $nombre = $this->input('nombre');
        $concentracion = $this->input('concentracion');
        $productoId = $this->route('producto') ? $this->route('producto')->id : null;

        if ($nombre && $concentracion) {
            $existe = Producto::where('nombre', 'LIKE', $nombre)
                             ->where('concentracion', 'LIKE', $concentracion)
                             ->when($productoId, function($query) use ($productoId) {
                                 return $query->where('id', '!=', $productoId);
                             })
                             ->exists();

            if ($existe) {
                $validator->errors()->add('nombre', 
                    "Ya existe un producto con el nombre '{$nombre}' y concentración '{$concentracion}'. " .
                    "Si es un producto diferente, cambia el nombre o la concentración."
                );
                $validator->errors()->add('concentracion', 
                    "Esta combinación de nombre y concentración ya está registrada."
                );
            }
        }
    }

    /**
     * Validar fechas de manera más específica
     */
    protected function validateFechas(Validator $validator): void
    {
        $fechaFabricacion = $this->input('fecha_fabricacion');
        $fechaVencimiento = $this->input('fecha_vencimiento');

        // Solo validar si ambas fechas están presentes
        if ($fechaFabricacion && $fechaVencimiento) {
            $fabricacion = \Carbon\Carbon::parse($fechaFabricacion);
            $vencimiento = \Carbon\Carbon::parse($fechaVencimiento);
            
            // Validar que la diferencia sea razonable (mínimo 30 días)
            if ($vencimiento->diffInDays($fabricacion) < 30) {
                $validator->errors()->add('fecha_vencimiento', 
                    'La fecha de vencimiento debe ser al menos 30 días posterior a la fecha de fabricación.'
                );
            }

            // Validar que no venza muy pronto (mínimo 7 días desde hoy)
            if ($vencimiento->diffInDays(now()) < 7) {
                $validator->errors()->add('fecha_vencimiento', 
                    'La fecha de vencimiento debe ser al menos 7 días desde hoy.'
                );
            }
        }
    }

    /**
     * Validar precios de manera más específica
     */
    protected function validatePrecios(Validator $validator): void
    {
        $precioCompra = $this->input('precio_compra');
        $precioVenta = $this->input('precio_venta');

        if ($precioCompra && $precioVenta) {
            $compra = (float) $precioCompra;
            $venta = (float) $precioVenta;
            
            // Validar margen mínimo del 5%
            $margenMinimo = $compra * 1.05;
            if ($venta < $margenMinimo) {
                $validator->errors()->add('precio_venta', 
                    'El precio de venta debe ser al menos 5% mayor al precio de compra. ' .
                    'Precio mínimo sugerido: S/ ' . number_format($margenMinimo, 2)
                );
            }

            // Validar margen máximo del 500% (para evitar errores)
            $margenMaximo = $compra * 5;
            if ($venta > $margenMaximo) {
                $validator->errors()->add('precio_venta', 
                    'El precio de venta parece muy alto. Verifica que sea correcto. ' .
                    'Precio máximo sugerido: S/ ' . number_format($margenMaximo, 2)
                );
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar y formatear datos antes de la validación
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim(ucwords(strtolower($this->nombre)))
            ]);
        }

        if ($this->has('concentracion')) {
            $this->merge([
                'concentracion' => trim(strtolower($this->concentracion))
            ]);
        }

        if ($this->has('precio_compra')) {
            $this->merge([
                'precio_compra' => (float) str_replace(',', '', $this->precio_compra)
            ]);
        }

        if ($this->has('precio_venta')) {
            $this->merge([
                'precio_venta' => (float) str_replace(',', '', $this->precio_venta)
            ]);
        }

        if ($this->has('stock_actual')) {
            $this->merge([
                'stock_actual' => (int) $this->stock_actual
            ]);
        }

        if ($this->has('stock_minimo')) {
            $this->merge([
                'stock_minimo' => (int) $this->stock_minimo
            ]);
        }
    }
}
