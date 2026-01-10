<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeAssetsSimple extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:assets-simple';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simple CSS/JS optimization without external dependencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Iniciando optimización simple de assets...');
        $this->newLine();

        $this->createDirectories();
        $this->createBasicAssets();
        $this->optimizeCSS();
        $this->optimizeJS();
        $this->generateManifest();
        $this->displayResults();

        return 0;
    }

    private function createDirectories()
    {
        $this->info('📁 Creando directorios necesarios...');
        
        $directories = [
            'public/dist',
            'public/dist/css',
            'public/dist/js',
            'public/dist/images',
            'public/dist/fonts'
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("  ✅ Creado: {$dir}");
            }
        }

        $this->newLine();
    }

    private function createBasicAssets()
    {
        $this->info('📝 Creando assets básicos...');

        // CSS principal optimizado
        $css = "/* Estilos optimizados de la aplicación */
:root{--primary-color:#667eea;--secondary-color:#764ba2;--bg-color:#f8f9fa;--text-color:#212529;--border-color:rgba(0,0,0,.125);--shadow:0 .125rem .25rem rgba(0,0,0,.075)}
*{box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background-color:var(--bg-color);color:var(--text-color);margin:0;padding:0;line-height:1.5}
.container{max-width:1200px;margin:0 auto;padding:0 15px}
.navbar-brand{font-weight:700;color:var(--primary-color)}
.card{box-shadow:var(--shadow);border:1px solid var(--border-color);border-radius:.375rem;background:#fff;margin-bottom:1rem}
.card-header{padding:.75rem 1rem;background-color:#f8f9fa;border-bottom:1px solid var(--border-color);font-weight:600}
.card-body{padding:1rem}
.btn{display:inline-block;padding:.375rem .75rem;margin-bottom:0;font-size:.875rem;font-weight:400;line-height:1.5;text-align:center;text-decoration:none;vertical-align:middle;cursor:pointer;border:1px solid transparent;border-radius:.375rem;transition:all .15s ease-in-out}
.btn-primary{color:#fff;background:linear-gradient(135deg,var(--primary-color) 0%,var(--secondary-color) 100%);border:none}
.btn-primary:hover{background:linear-gradient(135deg,#5a6fd8 0%,#6a4190 100%);transform:translateY(-1px)}
.table{width:100%;margin-bottom:0;border-collapse:collapse}
.table th,.table td{padding:.75rem;vertical-align:top;border-top:1px solid #dee2e6}
.table thead th{vertical-align:bottom;border-bottom:2px solid #dee2e6;font-weight:600}
.loading{opacity:.6;pointer-events:none}
.dashboard-card{transition:transform .2s ease}
.dashboard-card:hover{transform:translateY(-2px)}
.stats-card{background:linear-gradient(135deg,var(--primary-color) 0%,var(--secondary-color) 100%);color:#fff;padding:1.5rem;border-radius:.5rem}
.form-control{display:block;width:100%;padding:.375rem .75rem;font-size:.875rem;line-height:1.5;color:#495057;background-color:#fff;border:1px solid #ced4da;border-radius:.375rem;transition:border-color .15s ease-in-out,box-shadow .15s ease-in-out}
.form-control:focus{color:#495057;background-color:#fff;border-color:#80bdff;outline:0;box-shadow:0 0 0 .2rem rgba(0,123,255,.25)}
@media (max-width:768px){.table-responsive{font-size:.875rem;overflow-x:auto}.container{padding:0 10px}.card{margin-bottom:.5rem}}
.spinner-border{display:inline-block;width:1rem;height:1rem;vertical-align:text-bottom;border:.125em solid currentColor;border-right-color:transparent;border-radius:50%;animation:spinner-border .75s linear infinite}
@keyframes spinner-border{to{transform:rotate(360deg)}}";

        File::put('public/dist/css/app.min.css', $css);
        $this->line('  ✅ CSS optimizado creado');

        // JavaScript principal optimizado
        $js = "document.addEventListener('DOMContentLoaded',function(){
// Inicializar tooltips si Bootstrap está disponible
if(typeof bootstrap!=='undefined'&&bootstrap.Tooltip){
var tooltipTriggerList=[].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
tooltipTriggerList.map(function(tooltipTriggerEl){return new bootstrap.Tooltip(tooltipTriggerEl)})
}
// Confirmar eliminaciones
document.querySelectorAll('.btn-delete').forEach(function(btn){
btn.addEventListener('click',function(e){
if(!confirm('¿Está seguro de que desea eliminar este elemento?')){e.preventDefault()}
})
});
// Estados de carga en formularios
document.querySelectorAll('form').forEach(function(form){
form.addEventListener('submit',function(){
var submitBtn=form.querySelector('button[type=\"submit\"]');
if(submitBtn){
submitBtn.disabled=true;
submitBtn.innerHTML='<span class=\"spinner-border spinner-border-sm\" role=\"status\"></span> Procesando...'
}
})
});
// Lazy loading para imágenes
if('IntersectionObserver' in window){
var imageObserver=new IntersectionObserver(function(entries,observer){
entries.forEach(function(entry){
if(entry.isIntersecting){
var img=entry.target;
img.src=img.dataset.src;
img.classList.remove('lazy');
imageObserver.unobserve(img)
}
})
});
document.querySelectorAll('img[data-src]').forEach(function(img){imageObserver.observe(img)})
}
// Optimización de tablas grandes
var tables=document.querySelectorAll('.table-responsive table');
tables.forEach(function(table){
if(table.rows.length>100){
table.classList.add('table-sm')
}
});
// Cache de formularios en localStorage
var forms=document.querySelectorAll('form[data-cache]');
forms.forEach(function(form){
var cacheKey='form_'+form.dataset.cache;
// Restaurar datos del cache
var cachedData=localStorage.getItem(cacheKey);
if(cachedData){
try{
var data=JSON.parse(cachedData);
Object.keys(data).forEach(function(key){
var input=form.querySelector('[name=\"'+key+'\"]');
if(input&&input.type!=='password'){input.value=data[key]}
})
}catch(e){}
}
// Guardar en cache al cambiar
form.addEventListener('input',function(){
var formData={};
var inputs=form.querySelectorAll('input,select,textarea');
inputs.forEach(function(input){
if(input.name&&input.type!=='password'){formData[input.name]=input.value}
});
localStorage.setItem(cacheKey,JSON.stringify(formData))
});
// Limpiar cache al enviar
form.addEventListener('submit',function(){localStorage.removeItem(cacheKey)})
})
});";

        File::put('public/dist/js/app.min.js', $js);
        $this->line('  ✅ JavaScript optimizado creado');

        $this->newLine();
    }

    private function optimizeCSS()
    {
        $this->info('🎨 Optimizando archivos CSS existentes...');

        $cssFiles = [
            'public/css/style.css',
            'public/css/custom.css',
            'public/css/bootstrap.min.css'
        ];

        $combinedCSS = '';
        $originalSize = 0;

        foreach ($cssFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                $originalSize += strlen($content);
                $combinedCSS .= $content . "\n";
                $this->line("  - Procesado: " . basename($file));
            }
        }

        if ($combinedCSS) {
            // Minificación básica de CSS
            $minified = $this->minifyCSS($combinedCSS);
            File::put('public/dist/css/combined.min.css', $minified);
            
            $newSize = strlen($minified);
            $savings = $originalSize > 0 ? round((($originalSize - $newSize) / $originalSize) * 100, 1) : 0;
            
            $this->line("  ✅ CSS combinado y minificado (ahorro: {$savings}%)");
        }

        $this->newLine();
    }

    private function optimizeJS()
    {
        $this->info('⚡ Optimizando archivos JavaScript existentes...');

        $jsFiles = [
            'public/js/app.js',
            'public/js/inventario.js',
            'public/js/ventas.js',
            'public/js/dashboard.js'
        ];

        $combinedJS = '';
        $originalSize = 0;

        foreach ($jsFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                $originalSize += strlen($content);
                $combinedJS .= $content . "\n";
                $this->line("  - Procesado: " . basename($file));
            }
        }

        if ($combinedJS) {
            // Minificación básica de JavaScript
            $minified = $this->minifyJS($combinedJS);
            File::put('public/dist/js/combined.min.js', $minified);
            
            $newSize = strlen($minified);
            $savings = $originalSize > 0 ? round((($originalSize - $newSize) / $originalSize) * 100, 1) : 0;
            
            $this->line("  ✅ JavaScript combinado y minificado (ahorro: {$savings}%)");
        }

        $this->newLine();
    }

    private function minifyCSS($css)
    {
        // Remover comentarios
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espacios en blanco innecesarios
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espacios alrededor de caracteres especiales
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Remover punto y coma final
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }

    private function minifyJS($js)
    {
        // Remover comentarios de línea
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remover comentarios de bloque
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remover espacios en blanco excesivos
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remover espacios alrededor de operadores
        $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }

    private function generateManifest()
    {
        $this->info('📋 Generando manifiesto de assets...');

        $manifest = [
            'css/app.css' => '/dist/css/app.min.css',
            'css/combined.css' => '/dist/css/combined.min.css',
            'js/app.js' => '/dist/js/app.min.js',
            'js/combined.js' => '/dist/js/combined.min.js'
        ];

        File::put('public/dist/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $this->line('  ✅ Manifiesto generado');

        $this->newLine();
    }

    private function displayResults()
    {
        $this->info('📊 Resultados de la optimización:');
        $this->newLine();

        $distPath = 'public/dist';
        
        if (File::exists($distPath)) {
            $this->displayFileStats("{$distPath}/css", 'CSS');
            $this->displayFileStats("{$distPath}/js", 'JavaScript');
        }

        $this->newLine();
        $this->info('💡 Para usar los assets optimizados en tus vistas:');
        $this->line('  <link rel="stylesheet" href="{{ asset(\'dist/css/app.min.css\') }}">');
        $this->line('  <script src="{{ asset(\'dist/js/app.min.js\') }}"></script>');
        $this->newLine();
        $this->info('✅ Optimización simple de assets completada');
    }

    private function displayFileStats($path, $type)
    {
        if (!File::exists($path)) {
            return;
        }

        $files = File::files($path);
        $totalSize = 0;

        $this->line("  📁 {$type}:");
        
        foreach ($files as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $sizeFormatted = $this->formatBytes($size);
            $this->line("    - {$file->getFilename()}: {$sizeFormatted}");
        }

        $totalFormatted = $this->formatBytes($totalSize);
        $this->line("    📊 Total {$type}: {$totalFormatted}");
        $this->newLine();
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}