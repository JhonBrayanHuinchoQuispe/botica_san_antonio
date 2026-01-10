<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Correlative;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class ConfiguracionController extends Controller
{
    /**
     * Obtener configuración de la empresa
     */
    public function obtenerEmpresa(): JsonResponse
    {
        try {
            $empresa = Company::with(['branches', 'correlatives'])->where('activo', true)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración de empresa'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $empresa
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración de la empresa
     */
    public function actualizarEmpresa(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'nullable|string|size:6',
            'departamento' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'distrito' => 'nullable|string|max:100',
            'urbanizacion' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:50',
            'modo_prueba' => 'boolean',
            'certificado_password' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $empresa = Company::where('activo', true)->first();

            if (!$empresa) {
                // Crear nueva empresa
                $empresa = Company::create(array_merge($request->all(), ['activo' => true]));
            } else {
                // Actualizar empresa existente
                $empresa->update($request->all());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuración de empresa actualizada exitosamente',
                'data' => $empresa
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir certificado digital
     */
    public function subirCertificado(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'certificado' => 'required|file|mimes:pfx,p12|max:2048',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo de certificado inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa = Company::where('activo', true)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración de empresa'
                ], 404);
            }

            // Eliminar certificado anterior si existe
            if ($empresa->certificado_path && Storage::exists($empresa->certificado_path)) {
                Storage::delete($empresa->certificado_path);
            }

            // Guardar nuevo certificado
            $file = $request->file('certificado');
            $filename = 'certificado_' . $empresa->ruc . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('certificados', $filename, 'local');

            // Actualizar empresa
            $empresa->update([
                'certificado_path' => storage_path('app/' . $path),
                'certificado_password' => $request->password
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Certificado subido exitosamente',
                'data' => [
                    'certificado_path' => $path,
                    'fecha_subida' => now()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir certificado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar sucursales
     */
    public function listarSucursales(): JsonResponse
    {
        try {
            $sucursales = Branch::with('company')->where('activo', true)->get();

            return response()->json([
                'success' => true,
                'data' => $sucursales
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar sucursales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear sucursal
     */
    public function crearSucursal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:10|unique:branches,codigo',
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'nullable|string|size:6',
            'departamento' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'distrito' => 'nullable|string|max:100',
            'urbanizacion' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresa = Company::where('activo', true)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración de empresa'
                ], 404);
            }

            $sucursal = Branch::create(array_merge($request->all(), [
                'company_id' => $empresa->id,
                'activo' => true
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada exitosamente',
                'data' => $sucursal
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar correlativos
     */
    public function listarCorrelativos(): JsonResponse
    {
        try {
            $correlativos = Correlative::with(['company', 'branch'])
                                     ->where('activo', true)
                                     ->orderBy('tipo_documento')
                                     ->orderBy('serie')
                                     ->get();

            return response()->json([
                'success' => true,
                'data' => $correlativos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar correlativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear correlativo
     */
    public function crearCorrelativo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'tipo_documento' => 'required|string|in:01,03,07,08',
            'serie' => 'required|string|max:4',
            'numero_actual' => 'required|integer|min:1',
            'numero_maximo' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que el número actual sea menor al máximo
        if ($request->numero_actual >= $request->numero_maximo) {
            return response()->json([
                'success' => false,
                'message' => 'El número actual debe ser menor al número máximo'
            ], 422);
        }

        try {
            $empresa = Company::where('activo', true)->first();
            $sucursal = Branch::findOrFail($request->branch_id);

            // Verificar que no exista el mismo correlativo
            $correlativoExistente = Correlative::where('company_id', $empresa->id)
                                              ->where('branch_id', $request->branch_id)
                                              ->where('tipo_documento', $request->tipo_documento)
                                              ->where('serie', $request->serie)
                                              ->where('activo', true)
                                              ->first();

            if ($correlativoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un correlativo activo para este tipo de documento y serie'
                ], 409);
            }

            $correlativo = Correlative::create(array_merge($request->all(), [
                'company_id' => $empresa->id,
                'activo' => true
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Correlativo creado exitosamente',
                'data' => $correlativo->load(['company', 'branch'])
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar correlativo
     */
    public function actualizarCorrelativo(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero_actual' => 'required|integer|min:1',
            'numero_maximo' => 'required|integer|min:1',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $correlativo = Correlative::findOrFail($id);

            // Validar que el número actual sea menor al máximo
            if ($request->numero_actual >= $request->numero_maximo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El número actual debe ser menor al número máximo'
                ], 422);
            }

            $correlativo->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Correlativo actualizado exitosamente',
                'data' => $correlativo->load(['company', 'branch'])
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar conexión con SUNAT
     */
    public function probarConexionSunat(): JsonResponse
    {
        try {
            $empresa = Company::where('activo', true)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración de empresa'
                ], 404);
            }

            // Aquí puedes implementar una prueba real de conexión con SUNAT
            // Por ahora, solo validamos que los datos estén completos
            $datosCompletos = !empty($empresa->ruc) && 
                            !empty($empresa->usuario_sol) && 
                            !empty($empresa->clave_sol);

            if (!$datosCompletos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan datos de configuración SUNAT (RUC, Usuario SOL, Clave SOL)'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración SUNAT válida',
                'data' => [
                    'ruc' => $empresa->ruc,
                    'usuario_sol' => $empresa->usuario_sol,
                    'modo_prueba' => $empresa->modo_prueba,
                    'certificado_configurado' => !empty($empresa->certificado_path)
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}