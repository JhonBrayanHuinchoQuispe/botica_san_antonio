<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupController extends Controller
{
    public function index()
    {
        return view('admin.respaldos.index');
    }
    
    public function backup()
    {
        try {
            $fecha = Carbon::now()->format('Y-m-d_H-i-s');
            $backupPath = storage_path('app/backups');
            
            // Crear directorio si no existe
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            // Backup de la base de datos
            $dbFileName = "botica_backup_{$fecha}.sql";
            $dbBackupPath = "{$backupPath}/{$dbFileName}";
            
            $command = sprintf(
                'mysqldump -u %s -p%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                $dbBackupPath
            );
            exec($command);

            // Crear ZIP con DB y archivos
            $zip = new ZipArchive();
            $zipFileName = "botica_sistema_backup_{$fecha}.zip";
            $zipPath = "{$backupPath}/{$zipFileName}";

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Agregar DB
                $zip->addFile($dbBackupPath, $dbFileName);

                // Agregar imágenes
                $imagePath = public_path('storage/productos');
                $files = File::allFiles($imagePath);

                foreach ($files as $file) {
                    $relativePath = 'productos/' . $file->getRelativePathname();
                    $zip->addFile($file->getRealPath(), $relativePath);
                }

                $zip->close();

                // Eliminar SQL temporal
                File::delete($dbBackupPath);

                return response()->json([
                    'success' => true,
                    'message' => 'Respaldo creado exitosamente',
                    'filename' => $zipFileName
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear respaldo: ' . $e->getMessage()
            ], 500);
        }
    }
}