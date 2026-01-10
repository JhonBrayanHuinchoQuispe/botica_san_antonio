<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ConfiguracionHelper;
use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Storage;

class ConfiguracionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:sistema 
                            {action : Acción a realizar (get, set, export, import, reset, status)}
                            {--key= : Clave de configuración (para get/set)}
                            {--value= : Valor de configuración (para set)}
                            {--file= : Archivo para export/import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar configuraciones del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'get':
                return $this->getConfiguracion();
            case 'set':
                return $this->setConfiguracion();
            case 'export':
                return $this->exportConfiguracion();
            case 'import':
                return $this->importConfiguracion();
            case 'reset':
                return $this->resetConfiguracion();
            case 'status':
                return $this->statusSistema();
            default:
                $this->error("Acción no válida. Acciones disponibles: get, set, export, import, reset, status");
                return 1;
        }
    }

    /**
     * Obtener una configuración
     */
    private function getConfiguracion()
    {
        $key = $this->option('key');

        if (!$key) {
            // Mostrar todas las configuraciones
            $config = ConfiguracionHelper::obtenerToda();
            $this->table(['Clave', 'Valor'], $this->formatearConfiguraciones($config));
        } else {
            // Mostrar configuración específica
            $value = ConfiguracionHelper::obtener($key);
            $this->info("$key: " . ($value ?? 'null'));
        }

        return 0;
    }

    /**
     * Establecer una configuración
     */
    private function setConfiguracion()
    {
        $key = $this->option('key');
        $value = $this->option('value');

        if (!$key || !$value) {
            $this->error("Debe especificar --key y --value");
            return 1;
        }

        if (ConfiguracionHelper::establecer($key, $value)) {
            $this->info("Configuración '$key' establecida correctamente");
            return 0;
        } else {
            $this->error("Error al establecer la configuración");
            return 1;
        }
    }

    /**
     * Exportar configuraciones
     */
    private function exportConfiguracion()
    {
        $file = $this->option('file') ?? 'configuracion_sistema.json';

        $configuraciones = ConfiguracionHelper::exportar();

        if (empty($configuraciones)) {
            $this->warn("No hay configuraciones para exportar");
            return 1;
        }

        $json = json_encode($configuraciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (Storage::put($file, $json)) {
            $this->info("Configuraciones exportadas a: storage/app/$file");
            return 0;
        } else {
            $this->error("Error al exportar configuraciones");
            return 1;
        }
    }

    /**
     * Importar configuraciones
     */
    private function importConfiguracion()
    {
        $file = $this->option('file') ?? 'configuracion_sistema.json';

        if (!Storage::exists($file)) {
            $this->error("El archivo $file no existe en storage/app/");
            return 1;
        }

        $json = Storage::get($file);
        $configuraciones = json_decode($json, true);

        if (!$configuraciones) {
            $this->error("Error al leer el archivo JSON");
            return 1;
        }

        if ($this->confirm("¿Está seguro de importar las configuraciones? Esto sobrescribirá las configuraciones actuales.")) {
            if (ConfiguracionHelper::importar($configuraciones)) {
                $this->info("Configuraciones importadas correctamente");
                return 0;
            } else {
                $this->error("Error al importar configuraciones");
                return 1;
            }
        }

        return 0;
    }

    /**
     * Resetear configuraciones
     */
    private function resetConfiguracion()
    {
        if ($this->confirm("¿Está seguro de resetear todas las configuraciones? Esta acción no se puede deshacer.")) {
            try {
                ConfiguracionSistema::truncate();
                ConfiguracionHelper::limpiarCache();
                $this->info("Configuraciones reseteadas correctamente");
                return 0;
            } catch (\Exception $e) {
                $this->error("Error al resetear configuraciones: " . $e->getMessage());
                return 1;
            }
        }

        return 0;
    }

    /**
     * Mostrar estado del sistema
     */
    private function statusSistema()
    {
        $this->info("Estado del Sistema");
        $this->line("==================");

        $estado = ConfiguracionHelper::obtenerEstadoSistema();

        $datos = [];
        foreach ($estado as $componente => $status) {
            $datos[] = [
                ucfirst(str_replace('_', ' ', $componente)),
                $status ? '✅ OK' : '❌ ERROR'
            ];
        }

        $this->table(['Componente', 'Estado'], $datos);

        return 0;
    }

    /**
     * Formatear configuraciones para mostrar en tabla
     */
    private function formatearConfiguraciones($config)
    {
        $datos = [];
        $fillable = $config->getFillable();

        foreach ($fillable as $campo) {
            $valor = $config->{$campo};
            
            // Formatear valores especiales
            if (is_bool($valor)) {
                $valor = $valor ? 'true' : 'false';
            } elseif (is_null($valor)) {
                $valor = 'null';
            } elseif (is_array($valor)) {
                $valor = json_encode($valor);
            }

            $datos[] = [$campo, $valor];
        }

        return $datos;
    }
}
