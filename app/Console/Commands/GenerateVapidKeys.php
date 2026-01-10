<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:generate-vapid';
    protected $description = 'Genera llaves VAPID (public/private) para Web Push';

    public function handle(): int
    {
        $this->info('Generando llaves VAPID...');
        $keys = VAPID::createVapidKeys();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->newLine();
        $this->warn('Copia estas variables a tu archivo .env');
        return self::SUCCESS;
    }
}