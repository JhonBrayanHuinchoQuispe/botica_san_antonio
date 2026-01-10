<?php

namespace App\Http\Controllers;

use OwenIt\Auditing\Models\Audit;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Obtener historial de auditoría de un producto
     */
    public function historial($id)
    {
        try {
            $audits = Audit::where('auditable_type', 'App\\Models\\Producto')
                ->where('auditable_id', $id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $historial = $audits->map(function($audit) {
                $changes = $audit->getModified();
                $isStatusChange = isset($changes['estado']) || isset($changes['activo']);
                
                // Determinar el tipo de evento
                $eventType = $audit->event;
                if ($eventType == 'updated' && $isStatusChange) {
                    $eventType = 'estado';
                }
                
                // Obtener el primer cambio para mostrar
                $cambio = null;
                if (count($changes) > 0) {
                    $firstKey = array_key_first($changes);
                    $cambio = [
                        'campo' => $firstKey,
                        'anterior' => $changes[$firstKey]['old'] ?? '-',
                        'nuevo' => $changes[$firstKey]['new'] ?? '-'
                    ];
                    
                    // Traducir valores de estado
                    if ($firstKey == 'estado' || $firstKey == 'activo') {
                        $cambio['campo'] = 'Estado';
                        $cambio['anterior'] = $cambio['anterior'] == 1 ? 'Activo' : 'Desactivado';
                        $cambio['nuevo'] = $cambio['nuevo'] == 1 ? 'Activo' : 'Desactivado';
                    }
                }
                
                return [
                    'id' => $audit->id,
                    'evento' => $eventType,
                    'fecha' => $audit->created_at->diffForHumans(),
                    'fecha_completa' => $audit->created_at->format('d/m/Y H:i:s'),
                    'usuario' => $audit->user ? $audit->user->name : 'Sistema',
                    'cambio' => $cambio,
                    'total_cambios' => count($changes)
                ];
            });

            return response()->json([
                'success' => true,
                'historial' => $historial
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
