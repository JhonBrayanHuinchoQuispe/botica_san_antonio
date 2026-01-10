<?php
namespace App\Http\Controllers\Inventario\presentacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Presentacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresentacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Presentacion::query();
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%'.$request->search.'%');
        }
        $presentaciones = $query->withCount('productos')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $presentaciones]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:presentaciones,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $lastId = DB::table('presentaciones')->max('id') ?? 0;
            $nextId = $lastId + 1;

            $presentacion = new Presentacion();
            $presentacion->id = $nextId;
            $presentacion->nombre = $request->nombre;
            $presentacion->descripcion = $request->descripcion;
            $presentacion->estado = 'activo';
            $presentacion->save();

            DB::commit();

            return response()->json(['success' => true, 'data' => $presentacion]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar presentación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la presentación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $presentacion = Presentacion::findOrFail($id);
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:presentaciones,nombre,'.$presentacion->id,
            'descripcion' => 'nullable|string|max:255',
        ]);
        $presentacion->update($validated);
        return response()->json(['success' => true, 'data' => $presentacion]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $presentacion = Presentacion::findOrFail($id);
            
            if ($presentacion->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una presentación que tiene productos asociados.'
                ], 409);
            }

            $presentacion->delete();
            
            $presentaciones = DB::table('presentaciones')
                ->orderBy('id')
                ->get();
                
            $counter = 1;
            foreach ($presentaciones as $p) {
                DB::table('presentaciones')
                    ->where('id', $p->id)
                    ->update(['id' => $counter]);
                $counter++;
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presentación eliminada correctamente',
                'needsRefresh' => true
            ]);
            
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Error al eliminar presentación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la presentación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $presentacion = Presentacion::withCount('productos')->find($id);
        if (!$presentacion) {
            return response()->json(['success' => false, 'message' => 'Presentación no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $presentacion]);
    }
}
