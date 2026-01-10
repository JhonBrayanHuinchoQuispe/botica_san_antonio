<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Services\CloudinaryService;

class MigrarImagenesCloudinary extends Command
{
    protected $signature = 'imagenes:migrar-cloudinary {--folder=productos} {--limit=0}';
    protected $description = 'Sube imágenes locales de productos a Cloudinary y actualiza URLs';

    public function handle(): int
    {
        $service = new CloudinaryService();
        if (! $service->isEnabled()) {
            $this->error('Cloudinary no está configurado');
            return self::FAILURE;
        }

        $folder = (string) $this->option('folder');
        $limit = (int) $this->option('limit');

        $query = Producto::query()
            ->whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->where(function ($q) {
                $q->where('imagen', 'not like', 'http%');
            })
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $productos = $query->get();
        if ($productos->isEmpty()) {
            $this->info('No hay imágenes locales para migrar');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($productos as $producto) {
            $path = $this->resolverRutaLocal((string) $producto->imagen);
            if (! $path) {
                $this->warn('No encontrada: '.$producto->imagen.' (#'.$producto->id.')');
                continue;
            }

            $url = $service->uploadLocalPath($path, $folder);
            if (! $url) {
                $this->warn('Falló subida: '.$producto->imagen.' (#'.$producto->id.')');
                $debug = $this->debugUpload($service, $path, $folder);
                if ($debug) {
                    $this->line('Status: '.$debug['status']);
                    $this->line('Respuesta: '.$debug['body']);
                }
                continue;
            }

            $producto->imagen = $url;
            $producto->save();
            $count++;
            $this->line('OK #'.$producto->id.' => '.$url);
        }

        $this->info('Total migradas: '.$count);
        return self::SUCCESS;
    }

    protected function resolverRutaLocal(string $raw): ?string
    {
        $rel = ltrim($raw, '/');
        $candidates = [];
        $candidates[] = storage_path('app/public/'.$rel);
        $candidates[] = public_path($rel);
        if (strpos($rel, '/') === false) {
            $candidates[] = public_path('productos/'.$rel);
        }
        if (str_starts_with($rel, 'storage/')) {
            $stripped = substr($rel, strlen('storage/'));
            $candidates[] = storage_path('app/public/'.$stripped);
            $candidates[] = public_path($stripped);
        }
        foreach ($candidates as $p) {
            if (is_readable($p)) {
                return $p;
            }
        }
        return null;
    }

    protected function debugUpload(CloudinaryService $service, string $path, string $folder): ?array
    {
        if (! is_readable($path)) {
            return null;
        }
        $timestamp = time();
        $params = [
            'folder' => $folder,
            'timestamp' => $timestamp,
            'overwrite' => '1',
        ];
        ksort($params);
        $toSign = [];
        foreach ($params as $key => $value) {
            $toSign[] = $key.'='.$value;
        }
        $signature = sha1(implode('&', $toSign) . env('CLOUDINARY_API_SECRET', ''));
        $client = \Illuminate\Support\Facades\Http::asMultipart();
        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            $client = $client->withoutVerifying();
        }
        $response = $client
            ->attach('file', fopen($path, 'r'), basename($path))
            ->post('https://api.cloudinary.com/v1_1/'.env('CLOUDINARY_CLOUD_NAME', '').'/image/upload', [
                'api_key' => env('CLOUDINARY_API_KEY', ''),
                'timestamp' => $timestamp,
                'signature' => $signature,
                'folder' => $folder,
                'overwrite' => '1',
            ]);
        return ['status' => $response->status(), 'body' => $response->body()];
    }
}
