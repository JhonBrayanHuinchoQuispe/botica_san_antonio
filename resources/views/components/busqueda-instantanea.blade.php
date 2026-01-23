@props([
    'placeholder' => 'Buscar productos...',
    'inputClass' => 'form-control',
    'showCategoria' => true,
    'showStock' => true,
    'onSelect' => null
])

<div x-data="busquedaInstantanea" class="position-relative">
    
    <div class="input-group">
        <input 
            type="text" 
            x-model="termino"
            :class="inputClass"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @focus="mostrarResultados = resultados.length > 0"
            @blur="setTimeout(() => mostrarResultados = false, 200)"
        >
        <div class="input-group-append" x-show="cargando">
            <span class="input-group-text">
                <i class="fas fa-spinner fa-spin"></i>
            </span>
        </div>
    </div>

    
    <div 
        x-show="mostrarResultados && resultados.length > 0"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="position-absolute w-100 bg-white border rounded shadow-lg mt-1"
        style="z-index: 1050; max-height: 400px; overflow-y: auto;"
    >
        <template x-for="producto in resultados" :key="producto.id">
            <div 
                @click="seleccionarProducto(producto)"
                class="p-3 border-bottom cursor-pointer hover-bg-light d-flex align-items-center"
                style="cursor: pointer;"
                @mouseenter="$el.classList.add('bg-light')"
                @mouseleave="$el.classList.remove('bg-light')"
            >
                
                <div class="me-3">
                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-box text-white"></i>
                    </div>
                </div>

                
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark" x-text="producto.nombre"></div>
                    <div class="small text-muted">
                        <span x-text="'Código: ' + producto.codigo"></span>
                        @if($showCategoria)
                        <span x-show="producto.categoria" x-text="' | ' + producto.categoria.nombre"></span>
                        @endif
                    </div>
                    @if($showStock)
                    <div class="small">
                        <span class="badge" 
                              :class="producto.stock > 10 ? 'bg-success' : producto.stock > 0 ? 'bg-warning' : 'bg-danger'"
                              x-text="'Stock: ' + producto.stock">
                        </span>
                    </div>
                    @endif
                </div>

                
                <div class="text-end">
                    <div class="fw-bold text-primary" x-text="'S/ ' + parseFloat(producto.precio_venta).toFixed(2)"></div>
                </div>
            </div>
        </template>

        
        <div x-show="mostrarResultados && resultados.length === 0 && termino.length >= 2" 
             x-cloak
             class="p-3 text-center text-muted">
            <i class="fas fa-search me-2"></i>
            No se encontraron productos
        </div>
    </div>

    
    <div x-show="termino.length > 0 && termino.length < 2" 
         x-cloak
         class="position-absolute w-100 bg-info text-white p-2 rounded mt-1 small"
         style="z-index: 1049;">
        <i class="fas fa-info-circle me-1"></i>
        Escribe al menos 2 caracteres para buscar
    </div>
</div>

@if($onSelect)
<script>
document.addEventListener('alpine:init', () => {
    document.addEventListener('producto-seleccionado', (event) => {
        const producto = event.detail;
        {{ $onSelect }}(producto);
    });
});
</script>
@endif

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}

.cursor-pointer {
    cursor: pointer;
}

[x-cloak] { 
    display: none !important; 
}
</style>