@extends('layout.layout')

@php
    $title = 'Asistente Inteligente';
    $subTitle = 'Análisis de Ventas e Inventario';
@endphp

@section('content')

<div class="ia-dashboard-container">
    <div class="grid grid-cols-12 gap-6 h-full">
        <div class="col-span-12 h-full">
            
            
            <div class="ia-card">
                
                
                <div class="ia-header">
                    <div class="ia-header-info">
                        <div class="ia-avatar-header">
                            <iconify-icon icon="fluent:bot-sparkle-24-filled"></iconify-icon>
                        </div>
                        <div>
                            <h2 class="ia-title">Asistente Virtual</h2>
                            <div class="ia-status">
                                <span class="ia-status-dot"></span>
                                <span class="ia-status-text">Sistema Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="ia-header-actions">
                        <button class="ia-action-btn" id="iaClear" title="Limpiar conversación">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                            <span class="hidden sm:inline">Limpiar</span>
                        </button>
                    </div>
                </div>

                
                <div class="ia-body" id="iaChat">
                    
                    <div class="ia-message ia-message-ai">
                        <div class="ia-message-avatar">
                            <iconify-icon icon="fluent:bot-sparkle-24-filled"></iconify-icon>
                        </div>
                        <div class="ia-message-content">
                            <div class="ia-bubble">
                                <p><strong>¡Hola! Soy la IA de tu farmacia.</strong> 🤖💊</p>
                                <p>Puedo ayudarte a analizar tus datos. Prueba preguntando:</p>
                                <ul class="ia-suggestions">
                                    <li>"¿Qué productos están agotados?"</li>
                                    <li>"Pronosticar ventas del próximo mes"</li>
                                    <li>"Top 10 productos más vendidos"</li>
                                    <li>"Productos por vencer en 30 días"</li>
                                </ul>
                            </div>
                            <span class="ia-timestamp">Ahora</span>
                        </div>
                    </div>
                    
                </div>

                
                <div class="ia-footer">
                    <div class="ia-input-wrapper">
                        <input type="text" id="iaInput" placeholder="Escribe tu consulta aquí..." autocomplete="off">
                        <button id="iaSend" class="ia-send-btn">
                            <iconify-icon icon="solar:plain-3-bold-duotone"></iconify-icon>
                        </button>
                    </div>
                    <div class="ia-disclaimer">La IA puede cometer errores. Verifica la información importante.</div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    
    :root {
        --ia-primary: #4f46e5; 
        --ia-primary-light: #6366f1;
        --ia-bg-user: #f3f4f6;
        --ia-text-user: #1f2937;
        --ia-bg-ai: #ffffff;
        --ia-border: #e5e7eb;
        --ia-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
    }

    
    .ia-dashboard-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding: 1rem;
        height: 85vh; 
    }

    
    .ia-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.5);
    }

    
    .ia-header {
        background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .ia-header-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .ia-avatar-header {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(5px);
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .ia-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin: 0;
        line-height: 1.2;
    }

    .ia-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .ia-status-dot {
        width: 8px;
        height: 8px;
        background-color: #34d399;
        border-radius: 50%;
        box-shadow: 0 0 8px #34d399;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
        100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
    }

    .ia-action-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .ia-action-btn:hover { background: rgba(255,255,255,0.25); }

    
    .ia-body {
        flex: 1;
        background-color: #f8fafc;
        background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
        background-size: 20px 20px;
        padding: 1.5rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        scroll-behavior: smooth;
    }

    
    .ia-message {
        display: flex;
        gap: 1rem;
        max-width: 85%;
        animation: slideIn 0.3s ease-out forwards;
        opacity: 0;
        transform: translateY(10px);
    }

    @keyframes slideIn {
        to { opacity: 1; transform: translateY(0); }
    }

    .ia-message-ai { align-self: flex-start; }
    .ia-message-user { align-self: flex-end; flex-direction: row-reverse; }

    .ia-message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .ia-message-ai .ia-message-avatar {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca;
    }

    .ia-message-user .ia-message-avatar {
        background: #e5e7eb;
        color: #4b5563;
    }

    .ia-bubble {
        padding: 1rem 1.25rem;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.5;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        position: relative;
    }

    .ia-message-ai .ia-bubble {
        background: #ffffff;
        color: #1e293b;
        border-top-left-radius: 4px;
        border: 1px solid #f1f5f9;
    }

    .ia-message-user .ia-bubble {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: white;
        border-top-right-radius: 4px;
    }

    .ia-timestamp {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.25rem;
        display: block;
    }
    .ia-message-user .ia-timestamp { text-align: right; }

    
    .ia-suggestions {
        list-style: none;
        padding: 0;
        margin: 0.5rem 0 0 0;
    }
    .ia-suggestions li {
        background: #f1f5f9;
        margin-bottom: 4px;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #475569;
        border-left: 3px solid #6366f1;
        cursor: pointer;
        transition: background 0.2s;
    }
    .ia-suggestions li:hover { background: #e2e8f0; }

    
    .ia-table-container {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-top: 0.5rem;
        background: white;
    }
    .ia-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .ia-table th {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }
    .ia-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .ia-table tr:last-child td { border-bottom: none; }
    .ia-table tr:hover { background: #f8fafc; }

    
    .ia-footer {
        padding: 1.25rem;
        background: white;
        border-top: 1px solid #f1f5f9;
    }

    .ia-input-wrapper {
        display: flex;
        gap: 0.75rem;
        background: #f8fafc;
        padding: 0.5rem;
        border-radius: 999px; 
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }

    .ia-input-wrapper:focus-within {
        background: white;
        border-color: var(--ia-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    #iaInput {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.5rem 1rem;
        outline: none;
        font-size: 0.95rem;
        color: #1e293b;
    }

    .ia-send-btn {
        background: var(--ia-primary);
        color: white;
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.2rem;
        transition: transform 0.2s, background 0.2s;
    }
    .ia-send-btn:hover {
        background: var(--ia-primary-light);
        transform: scale(1.05);
    }
    .ia-send-btn:active { transform: scale(0.95); }

    .ia-disclaimer {
        text-align: center;
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.5rem;
    }

    
    .ia-chart-wrapper {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-top: 0.5rem;
    }

    
    .ia-typing {
        display: flex;
        gap: 4px;
        padding: 4px;
        align-items: center;
        height: 24px;
    }
    .ia-dot {
        width: 6px;
        height: 6px;
        background: #94a3b8;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out both;
    }
    .ia-dot:nth-child(1) { animation-delay: -0.32s; }
    .ia-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    
    .ia-toggle-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 8px;
        margin-bottom: 10px;
        width: fit-content;
    }
    .ia-toggle-btn {
        padding: 4px 12px;
        border-radius: 6px;
        border: none;
        background: transparent;
        font-size: 0.8rem;
        color: #64748b;
        cursor: pointer;
    }
    .ia-toggle-btn.active {
        background: white;
        color: var(--ia-primary);
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        font-weight: 600;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){

        const chat = document.getElementById('iaChat');
        const input = document.getElementById('iaInput');
        const sendBtn = document.getElementById('iaSend');
        const clearBtn = document.getElementById('iaClear');

        function nowTime(){
            const d = new Date();
            let h = d.getHours();
            const m = ('0'+d.getMinutes()).slice(-2);
            const ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12; if (h === 0) h = 12;
            return `${h}:${m} ${ap}`;
        }

        function scrollToBottom(){
            setTimeout(() => {
                chat.scrollTop = chat.scrollHeight;
            }, 50);
        }

        function getHistory(){ try{ return JSON.parse(localStorage.getItem('iaChatHistory')||'[]'); }catch(e){ return []; } }
        function pushHistory(role, text){ try{ const h=getHistory(); h.push({role, text, ts: new Date().toISOString()}); localStorage.setItem('iaChatHistory', JSON.stringify(h)); }catch(e){} }

        function createMessageElement(role, contentHTML) {
            const wrap = document.createElement('div');
            wrap.className = `ia-message ${role === 'user' ? 'ia-message-user' : 'ia-message-ai'}`;
            
            const icon = role === 'user' ? 'solar:user-bold-duotone' : 'fluent:bot-sparkle-24-filled';

            wrap.innerHTML = `
                <div class="ia-message-avatar">
                    <iconify-icon icon="${icon}"></iconify-icon>
                </div>
                <div class="ia-message-content" style="max-width: 100%">
                    <div class="ia-bubble">
                        ${contentHTML}
                    </div>
                    <span class="ia-timestamp">${nowTime()}</span>
                </div>
            `;
            return wrap;
        }

        function addUserMessage(text) {
            const el = createMessageElement('user', text);
            chat.appendChild(el);
            scrollToBottom();
            pushHistory('user', text);
        }

        function addThinking() {
            const el = createMessageElement('ai', `
                <div class="ia-typing">
                    <div class="ia-dot"></div><div class="ia-dot"></div><div class="ia-dot"></div>
                </div>
                <span style="font-size:0.8em; margin-left:5px; color:#64748b;">Analizando...</span>
            `);
            el.id = 'iaThinking';
            chat.appendChild(el);
            scrollToBottom();
            return el;
        }

        function removeThinking(el) {
            if(el) el.remove();
            else {
                const existing = document.getElementById('iaThinking');
                if(existing) existing.remove();
            }
        }

        function formatRich(text) {
            let t = (text || '').trim();

            if(t.includes('•') || t.includes('- ')) {
                const lines = t.split(/\r?\n/);
                let html = '';
                let inList = false;
                lines.forEach(line => {
                    if(line.trim().startsWith('•') || line.trim().startsWith('- ')) {
                        if(!inList) { html += '<ul class="ia-suggestions">'; inList = true; }
                        html += `<li>${line.replace(/^[•-]\s*/, '')}</li>`;
                    } else {
                        if(inList) { html += '</ul>'; inList = false; }
                        if(line.trim()) html += `<p style="margin-bottom:0.5rem">${line}</p>`;
                    }
                });
                if(inList) html += '</ul>';
                return html;
            }
            return `<p>${t.replace(/\n/g, '<br>')}</p>`;
        }

        function addAiText(thinkingEl, text) {
            removeThinking(thinkingEl);
            const el = createMessageElement('ai', formatRich(text));
            chat.appendChild(el);
            scrollToBottom();
            pushHistory('ai', text);
        }

        function addAiList(thinkingEl, title, items, cols) {
            removeThinking(thinkingEl);
            
            let tableHtml = `<div style="font-weight:700; margin-bottom:0.8rem; color:#4f46e5">${title}</div>`;
            tableHtml += `<div class="ia-table-container"><table class="ia-table"><thead><tr>`;
            
            cols.forEach(col => tableHtml += `<th>${col}</th>`);
            tableHtml += `</tr></thead><tbody>`;

            if(!items || items.length === 0) {
                tableHtml += `<tr><td colspan="${cols.length}" style="text-align:center">No hay datos disponibles.</td></tr>`;
            } else {
                items.forEach(row => {
                    tableHtml += `<tr>`;
                    cols.forEach(col => {

                        let val = '';
                        const k = col.toLowerCase();

                        if(k.includes('prod')) val = row.nombre || row.producto || row.descripcion || '-';
                        else if(k.includes('stock')) val = row.stock || row.cantidad_disponible || row.existencias || '0';
                        else if(k.includes('uni')) val = row.unidades || row.unidad || row.qty || '0';
                        else if(k.includes('ven')) val = row.vence || row.fecha_vencimiento || row.vencimiento || '-';
                        else val = row[col] || row[k] || '-';
                        
                        tableHtml += `<td>${val}</td>`;
                    });
                    tableHtml += `</tr>`;
                });
            }
            tableHtml += `</tbody></table></div>`;

            const el = createMessageElement('ai', tableHtml);
            chat.appendChild(el);
            scrollToBottom();
            pushHistory('ai', title + ' (Tabla generada)');
        }

        function addAiChart(thinkingEl, title, forecastData) {
            removeThinking(thinkingEl);
            const chartId = 'chart_' + Date.now();
            
            const html = `
                <div style="font-weight:700; margin-bottom:0.5rem; color:#4f46e5">${title}</div>
                <div class="ia-chart-wrapper">
                    <canvas id="${chartId}" height="180"></canvas>
                </div>
            `;
            
            const el = createMessageElement('ai', html);
            chat.appendChild(el);
            scrollToBottom();
            pushHistory('ai', title + ' (Gráfico generado)');

            setTimeout(() => {
                const ctx = document.getElementById(chartId).getContext('2d');

                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
                gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: forecastData.map((_, i) => `Semana ${i+1}`),
                        datasets: [{
                            label: 'Predicción de Ventas',
                            data: forecastData,
                            borderColor: '#4f46e5',
                            backgroundColor: gradient,
                            borderWidth: 2,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#4f46e5',
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }, 100);
        }

        function addAiPrediction(thinkingEl, payload) {
            removeThinking(thinkingEl);
            const data = payload || {};

            if(data.plot_png_base64) {
                const imgHtml = `
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div class="ia-toggle-group">
                            <button class="ia-toggle-btn active" onclick="this.parentElement.nextElementSibling.style.display='block'; this.parentElement.nextElementSibling.nextElementSibling.style.display='none'; this.nextElementSibling.classList.remove('active'); this.classList.add('active');">Gráfico</button>
                            <button class="ia-toggle-btn" onclick="this.parentElement.nextElementSibling.style.display='none'; this.parentElement.nextElementSibling.nextElementSibling.style.display='block'; this.previousElementSibling.classList.remove('active'); this.classList.add('active');">Explicación</button>
                        </div>
                        <div class="ia-panel-graf">
                            <img src="data:image/png;base64,${data.plot_png_base64}" style="width:100%; border-radius:8px; border:1px solid #e2e8f0;">
                        </div>
                        <div class="ia-panel-text" style="display:none; font-size:0.9rem;">
                            ${data.text || 'Análisis completado.'}
                        </div>
                    </div>
                `;
                const el = createMessageElement('ai', imgHtml);
                chat.appendChild(el);
            } 

            else if(Array.isArray(data.forecast) && data.forecast.length) {
                addAiChart(null, data.text || 'Pronóstico de Ventas', data.forecast);
            } 

            else {
                addAiText(null, data.text || 'No se pudo generar la predicción.');
            }
            scrollToBottom();
        }

        function normalize(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
        
        function parsePeriodo(text){
            var t = normalize(text);
            if (t.includes('hoy')) return 1;
            if (t.includes('semana')) return 7;
            if (t.includes('mes')) return 30;
            var m = t.match(/(\d{1,3})\s*dias?/);
            if (m) return parseInt(m[1]);
            return 30;
        }

        function detectIntent(text){
            var t = normalize(text);

            if (t.includes('agotad')) return 'agotados';
            if (t.includes('critico') || t.includes('bajo stock')) return 'critico';
            if (t.includes('mas vendido') || t.includes('top')) return 'top';
            if (t.includes('rotacion')) return 'rotacion';
            if (t.includes('por vencer') || t.includes('vence')) return 'por_vencer';
            if (t.includes('cuantos') && t.includes('vendieron')) return 'ventas_resumen';
            if (t.includes('sin ventas')) return 'sin_ventas';
            if (t.includes('pronostic') || t.includes('predec') || t.includes('futuro')) return 'predict';
            if (t.includes('ayer')) return 'ventas_ayer';
            if (t.includes('cuantos') && t.includes('producto')) return 'count_products';

            const sqlish = ['lista','listar','total','suma','promedio'];
            if(sqlish.some(x => t.includes(x))) return 'nl_sql';
            return 'llm';
        }

        async function fetchJson(url, timeoutMs){
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeoutMs || 8000);
            try {
                const res = await fetch(url, { signal: controller.signal });
                clearTimeout(id);
                return await res.json();
            } catch(e) { return null; }
        }

        async function handleIntent(text) {
            const intent = detectIntent(text);
            const thinking = addThinking();
                if (intent === 'predict'){
                    const jp = await fetchJson('/api/ia/predict-sklearn?q='+encodeURIComponent(text), 10000);
                    addAiPrediction(thinking, jp?.data);
                    return;
                }

                if (intent === 'agotados'){
                    const j = await fetchJson('/api/ia/analytics/agotados?limit=8');
                    const data = j?.data?.slice(0,8) || [];
                    if(data.length > 0) addAiList(thinking, '⚠️ Productos Agotados', data, ['Producto','Stock']);
                    else addAiText(thinking, 'No encontré productos agotados en este momento. ¡Todo bien!');
                    return;
                }

                if (intent === 'critico'){
                    const j = await fetchJson('/api/ia/analytics/critico?limit=8');
                    const data = j?.data?.slice(0,8) || [];
                    if(data.length > 0) addAiList(thinking, '🔴 Stock Crítico (Pocos)', data, ['Producto','Stock']);
                    else addAiText(thinking, 'No hay productos en nivel crítico.');
                    return;
                }

                if (intent === 'top'){
                    const p = parsePeriodo(text);
                    const j = await fetchJson(`/api/ia/analytics/top-ventas?periodo=${p}&limit=8`);
                    const data = j?.data?.slice(0,8) || [];
                    if(data.length > 0) addAiList(thinking, `🔥 Top Ventas (${p} días)`, data, ['Producto','Unidades']);
                    else addAiText(thinking, 'No hay datos de ventas para este periodo.');
                    return;
                }

                if (intent === 'por_vencer'){
                    const p = parsePeriodo(text);
                    const j = await fetchJson(`/api/mobile/productos/por-vencer?dias=${p}`);
                    const data = j?.data?.slice(0,8) || [];
                    if(data.length > 0) addAiList(thinking, `📅 Por Vencer (en ${p} días)`, data, ['Producto','Vence','Stock']);
                    else addAiText(thinking, `No hay productos próximos a vencer en ${p} días.`);
                    return;
                }

                if (intent === 'nl_sql' || intent === 'llm'){

                    const ns = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent(text), 12000);
                    if(ns && (ns.text || ns.data?.text)) {
                        addAiText(thinking, ns.text || ns.data.text);
                    } else {

                        addAiText(thinking, "Lo siento, no pude obtener esa información exacta de la base de datos, pero estoy aprendiendo.");
                    }
                    return;
                }

                if(intent === 'count_products'){
                    const j = await fetchJson('/api/mobile/productos/count');
                    addAiText(thinking, `Tienes un total de **${j?.data?.count || 0}** productos registrados.`);
                    return;
                }

                addAiText(thinking, "No entendí muy bien tu consulta. Prueba preguntando por 'Agotados', 'Más vendidos' o 'Predicciones'.");

            } catch (error) {
                console.error(error);
                addAiText(thinking, "Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.");
            }
        }

        async function handleSend(){
            const text = input.value.trim();
            if(!text) return;
            
            addUserMessage(text);
            input.value = '';
            input.focus();

            await handleIntent(text);
        }

        sendBtn.addEventListener('click', handleSend);
        input.addEventListener('keydown', (e) => { if(e.key === 'Enter') handleSend(); });
        
        clearBtn.addEventListener('click', () => {
            if(confirm('¿Borrar historial?')){
                localStorage.removeItem('iaChatHistory');
                chat.innerHTML = '';
                const defaultMsg = chat.querySelector('.ia-message-ai');
                if(defaultMsg) defaultMsg.remove();
                
                h.forEach(m => {
                    if(m.role === 'user') addUserMessage(m.text);
                    else if(m.role === 'ai') {

                         createMessageElement('ai', formatRich(m.text)); 
                    }
                });
            }
        })();
    });
</script>

@endsection