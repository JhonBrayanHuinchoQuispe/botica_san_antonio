<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\User;

class AnalyzeQueryPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:analyze {--queries : Analizar consultas lentas} {--cache : Analizar uso de cache} {--indexes : Verificar índices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analiza el rendimiento de consultas y detecta problemas de optimización';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando análisis de rendimiento...');
        
        if ($this->option('queries')) {
            $this->analyzeSlowQueries();
        }
        
        if ($this->option('cache')) {
            $this->analyzeCacheUsage();
        }
        
        if ($this->option('indexes')) {
            $this->analyzeIndexes();
        }
        
        if (!$this->option('queries') && !$this->option('cache') && !$this->option('indexes')) {
            $this->analyzeSlowQueries();
            $this->analyzeCacheUsage();
            $this->analyzeIndexes();
        }
        
        $this->info('✅ Análisis completado!');
        
        return 0;
    }
    
    private function analyzeSlowQueries()
    {
        $this->info('📊 Analizando consultas lentas...');
        
        // Habilitar log de consultas
        DB::enableQueryLog();
        
        // Simular consultas comunes
        $startTime = microtime(true);
        
        // Test 1: Búsqueda de productos sin eager loading
        $this->line('Test 1: Búsqueda de productos...');
        $productos = Producto::where('nombre', 'like', '%paracetamol%')->limit(10)->get();
        $time1 = microtime(true) - $startTime;
        
        // Test 2: Ventas con detalles (posible N+1)
        $startTime = microtime(true);
        $this->line('Test 2: Ventas con detalles...');
        $ventas = Venta::with(['detalles', 'usuario'])->limit(5)->get();
        $time2 = microtime(true) - $startTime;
        
        // Test 3: Productos con categorías
        $startTime = microtime(true);
        $this->line('Test 3: Productos con relaciones...');
        $productosConRelaciones = Producto::with(['categoria_model', 'presentacion_model'])->limit(10)->get();
        $time3 = microtime(true) - $startTime;
        
        $queries = DB::getQueryLog();
        
        $this->table(
            ['Test', 'Tiempo (ms)', 'Consultas'],
            [
                ['Búsqueda productos', round($time1 * 1000, 2), count($queries)],
                ['Ventas con detalles', round($time2 * 1000, 2), 'N/A'],
                ['Productos con relaciones', round($time3 * 1000, 2), 'N/A']
            ]
        );
        
        // Mostrar consultas más lentas
        if (count($queries) > 0) {
            $this->warn('Consultas ejecutadas: ' . count($queries));
            foreach (array_slice($queries, 0, 3) as $query) {
                $this->line('SQL: ' . substr($query['query'], 0, 100) . '...');
                $this->line('Tiempo: ' . $query['time'] . 'ms');
                $this->line('---');
            }
        }
    }
    
    private function analyzeCacheUsage()
    {
        $this->info('💾 Analizando uso de cache...');
        
        try {
            // Test de Redis
            $startTime = microtime(true);
            Cache::put('test_performance', 'test_value', 60);
            $value = Cache::get('test_performance');
            $cacheTime = microtime(true) - $startTime;
            
            // Test de consulta sin cache
            $startTime = microtime(true);
            $count = Producto::count();
            $dbTime = microtime(true) - $startTime;
            
            // Test de consulta con cache
            $startTime = microtime(true);
            $cachedCount = Cache::remember('productos_count', 300, function () {
                return Producto::count();
            });
            $cachedDbTime = microtime(true) - $startTime;
            
            $this->table(
                ['Operación', 'Tiempo (ms)', 'Resultado'],
                [
                    ['Cache Redis', round($cacheTime * 1000, 4), $value ? 'OK' : 'ERROR'],
                    ['Consulta DB directa', round($dbTime * 1000, 2), $count . ' productos'],
                    ['Consulta DB con cache', round($cachedDbTime * 1000, 2), $cachedCount . ' productos']
                ]
            );
            
            $improvement = $dbTime > 0 ? round((($dbTime - $cachedDbTime) / $dbTime) * 100, 1) : 0;
            $this->info("Mejora con cache: {$improvement}%");
            
        } catch (\Exception $e) {
            $this->error('Error analizando cache: ' . $e->getMessage());
        }
    }
    
    private function analyzeIndexes()
    {
        $this->info('🗂️  Analizando índices de base de datos...');
        
        try {
            // Verificar índices en tabla productos
            $productosIndexes = DB::select("SHOW INDEX FROM productos");
            $ventasIndexes = DB::select("SHOW INDEX FROM ventas");
            
            $this->line('Índices en tabla productos:');
            foreach ($productosIndexes as $index) {
                $this->line("- {$index->Key_name} ({$index->Column_name})");
            }
            
            $this->line('');
            $this->line('Índices en tabla ventas:');
            foreach ($ventasIndexes as $index) {
                $this->line("- {$index->Key_name} ({$index->Column_name})");
            }
            
            // Analizar uso de índices con EXPLAIN
            $this->line('');
            $this->info('Análisis de uso de índices:');
            
            $explains = [
                "SELECT * FROM productos WHERE nombre LIKE '%test%' LIMIT 10",
                "SELECT * FROM ventas WHERE fecha_venta >= CURDATE() - INTERVAL 7 DAY",
                "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id LIMIT 10"
            ];
            
            foreach ($explains as $sql) {
                try {
                    $explain = DB::select("EXPLAIN " . $sql);
                    $this->line("Query: " . substr($sql, 0, 50) . "...");
                    if (!empty($explain)) {
                        $this->line("Tipo: " . $explain[0]->type . " | Filas: " . $explain[0]->rows . " | Key: " . ($explain[0]->key ?? 'NULL'));
                    }
                    $this->line('---');
                } catch (\Exception $e) {
                    $this->line("Error en query: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error analizando índices: ' . $e->getMessage());
        }
    }
}
