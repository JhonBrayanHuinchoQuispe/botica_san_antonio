<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;

class CloudinaryService
{
    protected bool $enabled = false;
    protected string $cloudName = '';
    protected string $apiKey = '';
    protected string $apiSecret = '';

    public function __construct()
    {
        $url = (string) env('CLOUDINARY_URL', '');
        if ($url !== '') {
            if (preg_match('#^cloudinary://(?P<key>[^:]+):(?P<secret>[^@]+)@(?P<cloud>[^/]+)$#', $url, $m)) {
                $this->apiKey = $m['key'];
                $this->apiSecret = $m['secret'];
                $this->cloudName = $m['cloud'];
            }
        }
        if ($this->cloudName === '' || $this->apiKey === '' || $this->apiSecret === '') {
            $this->cloudName = (string) env('CLOUDINARY_CLOUD_NAME');
            $this->apiKey = (string) env('CLOUDINARY_API_KEY');
            $this->apiSecret = (string) env('CLOUDINARY_API_SECRET');
        }
        $this->enabled = $this->cloudName !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function uploadProductImage($file, string $folder = 'productos'): ?string
    {
        if (!$this->enabled || !$file) {
            return null;
        }

        $timestamp = time();
        $preset = (string) env('CLOUDINARY_UPLOAD_PRESET', '');
        $params = [
            'folder' => $folder,
            'timestamp' => $timestamp,
            'overwrite' => '1',
        ];
        $signature = null;
        if ($preset === '') {
            ksort($params);
            $toSign = [];
            foreach ($params as $key => $value) {
                $toSign[] = $key.'='.$value;
            }
            $signature = sha1(implode('&', $toSign) . $this->apiSecret);
        }

        $client = Http::asMultipart();
        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            $client = $client->withoutVerifying();
        }
        $request = $client
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post('https://api.cloudinary.com/v1_1/'.$this->cloudName.'/image/upload', array_filter([
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'folder' => $folder,
                'overwrite' => '1',
                'upload_preset' => $preset ?: null,
            ]));
        $response = $request;

        if ($response->successful()) {
            $json = $response->json();
            return $json['secure_url'] ?? $json['url'] ?? null;
        }
        \Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);

        return null;
    }

    public function uploadLocalPath(string $path, string $folder = 'productos'): ?string
    {
        if (!$this->enabled || !is_readable($path)) {
            return null;
        }

        $timestamp = time();
        $preset = (string) env('CLOUDINARY_UPLOAD_PRESET', '');
        $params = [
            'folder' => $folder,
            'timestamp' => $timestamp,
            'overwrite' => '1',
        ];
        $signature = null;
        if ($preset === '') {
            ksort($params);
            $toSign = [];
            foreach ($params as $key => $value) {
                $toSign[] = $key.'='.$value;
            }
            $signature = sha1(implode('&', $toSign) . $this->apiSecret);
        }

        $client = Http::asMultipart();
        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            $client = $client->withoutVerifying();
        }
        $response = $client
            ->attach('file', fopen($path, 'r'), basename($path))
            ->post('https://api.cloudinary.com/v1_1/'.$this->cloudName.'/image/upload', array_filter([
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'folder' => $folder,
                'overwrite' => '1',
                'upload_preset' => $preset ?: null,
            ]));

        if ($response->successful()) {
            $json = $response->json();
            return $json['secure_url'] ?? $json['url'] ?? null;
        }
        \Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);

        return null;
    }
}
