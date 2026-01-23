@extends('layout.layout')

@section('title', 'Asistente IA')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ai/chat.css') }}">
@endpush

@section('content')
<div class="ai-chat-page">
    
    <div class="ai-header-compact">
        <div class="ai-header-content">
            <div class="ai-header-icon">
                <iconify-icon icon="solar:cpu-bolt-bold-duotone"></iconify-icon>
            </div>
            <div class="ai-header-text">
                <h1>Asistente Farmacia</h1>
                <p>Consulta información de tu botica</p>
            </div>
            <div class="ai-status ai-status-disconnected" id="aiStatus">
                <div class="status-dot"></div>
                <span>Conectado</span>
            </div>
        </div>
    </div>

    
    <div class="ai-chat-container">
        
        <div class="ai-sidebar">
            <div class="ai-sidebar-section">
                <h3><iconify-icon icon="solar:lightbulb-bolt-bold-duotone"></iconify-icon> Sugerencias</h3>
                <div class="ai-suggestions" id="aiSuggestions">
                    @foreach($suggestions as $suggestion)
                    <button class="ai-suggestion-btn" data-suggestion="{{ $suggestion }}">
                        {{ $suggestion }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="ai-sidebar-section">
                <h3><iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon> Predicciones</h3>
                <div class="ai-predictions">
                    <button class="ai-prediction-btn" id="btnPredictSales" data-prediction="sales">
                        <iconify-icon icon="solar:graph-up-bold-duotone"></iconify-icon>
                        <span>Predicción de Ventas</span>
                    </button>
                    <button class="ai-prediction-btn" id="btnPredictStock" data-prediction="stock">
                        <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                        <span>Análisis de Stock</span>
                    </button>
                    <button class="ai-prediction-btn" id="btnAnalyticsTrends" data-prediction="trends">
                        <iconify-icon icon="solar:chart-square-bold-duotone"></iconify-icon>
                        <span>Tendencias de Ventas</span>
                    </button>
                </div>
            </div>

            <div class="ai-sidebar-section">
                <h3><iconify-icon icon="solar:question-circle-bold-duotone"></iconify-icon> Ejemplos</h3>
                <div class="ai-examples">
                    <div class="ai-example">
                        <strong>Ventas:</strong> "¿Cuánto vendí hoy?"
                    </div>
                    <div class="ai-example">
                        <strong>Stock:</strong> "¿Qué productos están agotados?"
                    </div>
                    <div class="ai-example">
                        <strong>Lotes:</strong> "¿Qué lotes vencen en 30 días?"
                    </div>
                    <div class="ai-example">
                        <strong>Comparación:</strong> "Compara ventas de enero vs febrero"
                    </div>
                </div>
            </div>

            <div class="ai-sidebar-section">
                <button class="ai-clear-btn" id="btnClearHistory">
                    <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                    Limpiar historial
                </button>
            </div>
        </div>

        
        <div class="ai-chat-main">
            
            <div class="ai-messages" id="aiMessages">
                
                <div class="ai-message ai-message-bot">
                    <div class="ai-avatar">
                        <iconify-icon icon="solar:stars-bold-duotone"></iconify-icon>
                    </div>
                    <div class="ai-message-content">
                        <div class="ai-message-text">
                            ¡Hola, {{ auth()->user()->name ?? 'Usuario' }}! 👋
                            <br><br>
                            Soy tu asistente de IA para <strong>Botica San Antonio</strong>. 
                            Puedo ayudarte con:
                            <ul>
                                <li>📊 Consultas de ventas y estadísticas</li>
                                <li>📦 Información de inventario y stock</li>
                                <li>⏰ Alertas de productos por vencer</li>
                                <li>📈 Comparaciones y análisis</li>
                            </ul>
                            ¿En qué puedo ayudarte hoy?
                        </div>
                        <div class="ai-message-time">Ahora</div>
                    </div>
                </div>
            </div>

            
            <div class="ai-input-container">
                <div class="ai-input-wrapper">
                    <input 
                        type="text" 
                        id="aiInput" 
                        class="ai-input" 
                        placeholder="Escribe tu pregunta aquí..."
                        autocomplete="off"
                    >
                    <button class="ai-send-btn" id="aiSendBtn">
                        <iconify-icon icon="solar:plain-bold"></iconify-icon>
                    </button>
                </div>
                <div class="ai-input-hint">
                    Presiona <kbd>Enter</kbd> para enviar
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/ai/image-handler.js') }}"></script>
<script src="{{ asset('assets/js/ai/chat.js') }}"></script>
<script src="{{ asset('assets/js/ai/predictions-manager.js') }}"></script>
@endpush
