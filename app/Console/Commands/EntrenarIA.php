<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class EntrenarIA extends Command
{
    protected $signature = 'ia:entrenar';
    protected $description = 'Entrenar modelos IA';

    public function handle()
    {
        $ruta = base_path('ia/train.py');
        if (!file_exists($ruta)) {
            $this->line('sin script');
            return 0;
        }
        $p = new Process(['python', $ruta], base_path());
        $p->setTimeout(30);
        try { $p->run(); } catch (\Throwable $e) {}
        $this->line('ok');
        return 0;
    }
}
