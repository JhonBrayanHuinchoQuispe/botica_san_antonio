<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudinaryPingCommand extends Command
{
    protected $signature = 'cloudinary:ping';
    protected $description = 'Verifica conectividad con Cloudinary usando Admin API';

    public function handle(): int
    {
        $cloud = env('CLOUDINARY_CLOUD_NAME');
        $key = env('CLOUDINARY_API_KEY');
        $secret = env('CLOUDINARY_API_SECRET');
        $urlEnv = env('CLOUDINARY_URL');

        if ($urlEnv) {
            if (preg_match('#^cloudinary://(?P<key>[^:]+):(?P<secret>[^@]+)@(?P<cloud>[^/]+)$#', $urlEnv, $m)) {
                $key = $m['key'];
                $secret = $m['secret'];
                $cloud = $m['cloud'];
            }
        }

        if (!$cloud || !$key || !$secret) {
            $this->error('Faltan variables CLOUDINARY_* en .env');
            return self::FAILURE;
        }

        $client = Http::withBasicAuth($key, $secret);
        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            $client = $client->withoutVerifying();
        }

        $url = "https://api.cloudinary.com/v1_1/{$cloud}/resources/image?max_results=1";
        $resp = $client->get($url);
        $this->line('Cloud: '.$cloud.' | Key: '.substr($key, -4));
        $this->line('Status: '.$resp->status());
        if ($resp->successful()) {
            $this->info('Conectado correctamente a Cloudinary: '.$cloud);
            return self::SUCCESS;
        }
        $this->line('Respuesta: '.$resp->body());
        return self::FAILURE;
    }
}
