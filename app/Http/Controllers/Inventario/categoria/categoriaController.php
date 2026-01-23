<?php
namespace App\Http\Controllers\Inventario\Categoria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::query();
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%'.$request->search.'%');
        }
        $categorias = $query->withCount('productos')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $categorias]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:100|unique:categorias,nombre',
                'descripcion' => 'nullable|string|max:255',
            ]);

            $categoria = Categoria::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => 'activo',
                'activo' => true
            ]);

            return response()->json(['success' => true, 'data' => $categoria]);
        } catch (\Exception $e) {
            Log::error('Error al guardar categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $validated = $request->validate([
                'nombre' => 'required|string|max:100|unique:categorias,nombre,'.$categoria->id,
                'descripcion' => 'nullable|string|max:255',
            ]);
            $categoria->update($validated);
            return response()->json(['success' => true, 'data' => $categoria]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            
            if ($categoria->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una categoría que tiene productos asociados.'
                ], 409);
            }

            $categoria->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente',
                'needsRefresh' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $categoria = Categoria::withCount('productos')->find($id);
            if (!$categoria) {
                return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            }
            return response()->json(['success' => true, 'data' => $categoria]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar categoría: ' . $e->getMessage());
            // Intentar cargar sin withCount si falla
            $categoria = Categoria::find($id);
            if ($categoria) {
                $categoria->productos_count = 0; // Valor por defecto
                return response()->json(['success' => true, 'data' => $categoria]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }
}
