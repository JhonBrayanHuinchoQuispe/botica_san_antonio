@extends('layout.layout')
@php
    $title = 'Inteligencia Artificial';
    $subTitle = 'Chat asistente con visualizaciones';
    $script = '';
@endphp

@section('content')

<div class="grid grid-cols-12 gap-6">
    

    <div class="col-span-12">
        <div class="ia-card">
            <div class="ia-convo-header">
                <div class="ia-convo-left">
                    <div class="ia-avatar ai big"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>
                    <div>
                        <div class="ia-convo-title">Asistente IA</div>
                        <div class="ia-convo-status"><span class="ia-dot"></span> Online</div>
                    </div>
                </div>
                <div class="ia-actions">
                    <button class="ia-btn open-tab" id="iaClear"><iconify-icon icon="ph:trash-duotone"></iconify-icon> Limpiar chat</button>
                </div>
            </div>

            <div class="ia-chat-wrap">
                <div class="ia-chat" id="iaChat">
                    <div class="ia-msg ia-msg-ai">
                        <div class="ia-avatar ai">
                            <iconify-icon icon="mdi:head-cog-outline"></iconify-icon>
                        </div>
                        <div>
                            <div class="ia-msg-author">IA</div>
                            <div class="ia-msg-text ia-bubble-ai">
                                Hola, soy tu asistente de IA. Pregúntame sobre ventas, stock y predicciones y te mostraré resultados con gráficos.
                            </div>
                            <div class="ia-time">Ahora</div>
                        </div>
                    </div>
                    <div class="ia-input">
                        <input type="text" id="iaInput" placeholder="Escribe tu pregunta…">
                        <button class="ia-btn ia-btn-primary" id="iaSend">
                            <iconify-icon icon="ph:paper-plane-tilt-duotone"></iconify-icon>
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<style>
.ia-header{background:linear-gradient(135deg,#e53e3e 0%,#f56565 100%);color:#fff;padding:1.25rem 1.5rem;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1)}
.ia-header h1{font-size:1.5rem;font-weight:800;margin:0}
.ia-header p{margin:.25rem 0 0 0;opacity:.95}
.ia-icon{background:#fff1f2;border-radius:10px;padding:.6rem;display:flex;align-items:center;justify-content:center}
.ia-icon iconify-icon{color:#fff}

.ia-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 12px 28px rgba(0,0,0,.08);overflow:hidden}
.ia-convo-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #fbe3e6;background:linear-gradient(135deg,#fff5f5 0%,#ffe4e6 100%)}
.ia-convo-left{display:flex;align-items:center;gap:.8rem}
.ia-actions{display:flex;align-items:center;gap:.6rem}
.ia-actions .open-tab{background:linear-gradient(135deg,#3b82f6 0%,#60a5fa 100%);color:#fff}
.ia-convo-title{font-weight:800;font-size:1rem;color:#0f172a}
.ia-convo-status{display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:#10b981}
.ia-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981}
.ia-chat-wrap{display:flex;justify-content:center;padding:0;background:#ffffff;border-radius:16px}
.ia-chat{width:100%;max-width:100%;min-height:65vh;overflow-y:auto;padding:1rem 1.25rem 0;display:flex;flex-direction:column;background:#ffffff;border-bottom-left-radius:16px;border-bottom-right-radius:16px}
.ia-msg{display:flex;gap:.9rem;margin-bottom:1.25rem}
.ia-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.ia-avatar.big{width:52px;height:52px}
.ia-avatar.user{background:#fee2e2;color:#dc2626}
.ia-avatar.ai{background:#fdecec;color:#e53e3e}
.ia-msg-author{font-size:.75rem;color:#6b7280;margin-bottom:.25rem}
.ia-msg{align-items:flex-start;width:100%}
.ia-msg-text{border-radius:16px;padding:1rem 1.2rem;position:relative;max-width:80%}
.ia-msg-ai .ia-msg-text{margin-right:auto}
.ia-msg-user .ia-msg-text{margin-left:auto}
.ia-msg-user{justify-content:flex-end}
.ia-msg-user .ia-avatar{order:2}
.ia-msg-user .ia-msg-author, .ia-msg-user .ia-time{text-align:right}
.ia-msg-user .ia-msg-text{background:linear-gradient(135deg,#f8fafc 0%,#f3f4f6 100%);color:#111827;border:1px solid #e5e7eb;border-radius:16px;min-width:140px}
.ia-msg-ai .ia-msg-text{background:linear-gradient(135deg,#fca5a5 0%,#fb7185 100%);color:#ffffff;border-radius:16px}
.ia-bubble-ai:after, .ia-msg-user .ia-msg-text:after{display:none}
.ia-time{margin-top:.35rem;font-size:.75rem;color:#6b7280}
.ia-thinking .ia-msg-text{background:#eef2ff;color:#1f2937}
.ia-dots{display:inline-block;min-width:24px}
@keyframes iaBlink{0%{opacity:.2}50%{opacity:1}100%{opacity:.2}}
.ia-dots span{animation:iaBlink 1.2s infinite;display:inline-block;margin-left:2px}
.ia-dots span:nth-child(2){animation-delay:.2s}
.ia-dots span:nth-child(3){animation-delay:.4s}
.ia-input{display:flex;gap:.7rem;margin-top:auto;position:sticky;bottom:0;background:#fff;padding:.8rem .9rem;border-top:1px solid #e5e7eb}
.ia-input input{flex:1;border:1px solid #d1d5db;border-radius:9999px;padding:.8rem 1rem;background:#ffffff;box-shadow:0 4px 10px rgba(0,0,0,.04)}
.ia-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-radius:9999px;font-weight:700;border:0}
.ia-btn-primary{background:linear-gradient(135deg,#e53e3e 0%,#f56565 100%);color:#fff}
.ia-chart-card{background:#fff;border:1px solid #ede9fe;border-radius:14px;margin-top:.8rem;padding:.9rem;width:100%;min-width:320px}
.ia-list{margin:.5rem 0 0 0;padding-left:1.2rem}
.ia-list li{margin:.2rem 0}
@media (min-width: 1280px){
  .ia-chat{min-height:72vh}
}
@media (max-width: 768px){
  .ia-chat{min-height:58vh;padding:.75rem .75rem 0}
  .ia-msg-text{max-width:95%}
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var chat = document.getElementById('iaChat');
            var input = document.getElementById('iaInput');
            var send = document.getElementById('iaSend');
            var btnClear = document.getElementById('iaClear');
            var statusEl = document.querySelector('.ia-convo-status');
            var dotEl = document.querySelector('.ia-dot');

            function getHistory(){ try{ return JSON.parse(localStorage.getItem('iaChatHistory')||'[]'); }catch(e){ return []; } }
            function setHistory(arr){ try{ localStorage.setItem('iaChatHistory', JSON.stringify(arr)); }catch(e){} }
            function pushHistory(role, text){ try{ var h=getHistory(); h.push({role:role, text:text, ts: new Date().toISOString()}); setHistory(h); }catch(e){} }
            function renderHistory(){
                try{
                    var h=getHistory();
                    for(var i=0;i<h.length;i++){
                        var m=h[i];
                        var wrap=document.createElement('div');
                        wrap.className='ia-msg '+(m.role==='user'?'ia-msg-user':'ia-msg-ai');
                        var icon=m.role==='user'?'solar:user-bold-duotone':'mdi:head-cog-outline';
                        var author=m.role==='user'?'Tú':'IA';
                        var content=m.role==='user'?('<div class="ia-msg-text">'+m.text+'</div>'):('<div class="ia-msg-text ia-bubble-ai">'+formatRich(m.text)+'</div>');
                        wrap.innerHTML='<div class="ia-avatar '+(m.role==='user'?'user':'ai')+'"><iconify-icon icon="'+icon+'"></iconify-icon></div>'+
                                    '<div><div class="ia-msg-author">'+author+'</div>'+content+'<div class="ia-time"></div></div>';
                        chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                    }
                }catch(e){}
            }

            function nowTime(){
                var d = new Date();
                var h = d.getHours();
                var m = ('0'+d.getMinutes()).slice(-2);
                var ap = h >= 12 ? 'PM' : 'AM';
                h = h % 12; if (h === 0) h = 12;
                return h + ':' + m + ' ' + ap;
            }

            function addUser(text){
                var wrap = document.createElement('div');
                wrap.className = 'ia-msg ia-msg-user';
                wrap.innerHTML = '<div class="ia-avatar user"><iconify-icon icon="solar:user-bold-duotone"></iconify-icon></div>'+
                                '<div><div class="ia-msg-author">Tú</div><div class="ia-msg-text">'+text+'</div><div class="ia-time">'+nowTime()+'</div></div>';
                chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                chat.scrollTop = chat.scrollHeight;
                pushHistory('user', text);
            }

            function addThinking(){
                var wrap=document.createElement('div');
                wrap.className='ia-msg ia-msg-ai ia-thinking';
                wrap.innerHTML = '<div class="ia-avatar ai"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>'+
                                '<div><div class="ia-msg-author">IA</div><div class="ia-msg-text ia-bubble-ai">Pensando<span class="ia-dots"><span>.</span><span>.</span><span>.</span></span></div></div>';
                chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                chat.scrollTop = chat.scrollHeight;
                return wrap;
            }

            function formatRich(text, focus){
                var t = (text||'').trim();
                var lines = t.split(/\r?\n/).filter(function(l){ return l.trim() !== ''; });
                var items = [];
                var paras = [];
                for (var i=0;i<lines.length;i++){
                    var l = lines[i].trim();
                    if (l.startsWith('•')){ items.push(l.replace(/^•\s*/, '')); }
                    else { paras.push(l); }
                }
                var tokens = [];
                if (focus){
                    var f = focus.toLowerCase();
                    tokens = f.split(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9]+/).filter(function(w){ return w && w.length >= 3; });
                }
                function applyBold(s){
                    var out = s;
                    for (var ii=0; ii<tokens.length; ii++){
                        var w = tokens[ii].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        var re = new RegExp('('+w+')', 'gi');
                        out = out.replace(re, '<strong>$1</strong>');
                    }
                    return out;
                }
                var html = '';
                for (var j=0;j<paras.length;j++){ html += '<p style="margin:.2rem 0">'+applyBold(paras[j])+'</p>'; }
                if (items.length){ html += '<ul class="ia-list">'; for (var k=0;k<items.length;k++){ html += '<li>'+applyBold(items[k])+'</li>'; } html += '</ul>'; }
                return html || t;
            }

            function addAiText(thinkingEl, text, focus){
                if (thinkingEl) thinkingEl.remove();
                var wrap = document.createElement('div');
                wrap.className = 'ia-msg ia-msg-ai';
                wrap.innerHTML = '<div class="ia-avatar ai"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>'+
                                '<div><div class="ia-msg-author">IA</div><div class="ia-msg-text ia-bubble-ai">'+formatRich(text, focus||'')+'</div><div class="ia-time">'+nowTime()+'</div></div>';
                chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                chat.scrollTop = chat.scrollHeight;
                pushHistory('ai', text);
            }

            function addAiChart(thinkingEl, title, forecast){
                if (thinkingEl) thinkingEl.remove();
                var id = 'chart_'+Date.now();
                var wrap = document.createElement('div');
                wrap.className = 'ia-msg ia-msg-ai';
                wrap.innerHTML = '<div class="ia-avatar ai"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>'+
                                '<div><div class="ia-msg-author">IA</div><div class="ia-msg-text ia-bubble-ai">'+
                                title+
                                '<div class="ia-chart-card"><canvas id="'+id+'" height="140"></canvas></div>'+
                                '</div><div class="ia-time">'+nowTime()+'</div></div>';
                chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                chat.scrollTop = chat.scrollHeight;
                pushHistory('ai', title+' '+(forecast||[]).join(', '));
                var canvas = document.getElementById(id);
                var ctx = canvas.getContext('2d');
                var gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
                gradient.addColorStop(0, 'rgba(248,113,113,0.25)');
                gradient.addColorStop(1, 'rgba(248,113,113,0.05)');
                var labels = [];
                for (var i=0;i<forecast.length;i++){ labels.push('Sem '+(i+1)); }
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Unidades estimadas',
                            data: forecast,
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: '#ef4444',
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }
                    }
                });
            }

            function addAiList(thinkingEl, title, items, cols){
                if (thinkingEl) thinkingEl.remove();
                var wrap = document.createElement('div');
                wrap.className = 'ia-msg ia-msg-ai';
                var html = '<div class="ia-avatar ai"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>'+
                        '<div><div class="ia-msg-author">IA</div><div class="ia-msg-text ia-bubble-ai">'+
                        '<div class="ia-chart-card ia-card-responsive">'+
                        '<div style="font-weight:700;margin-bottom:.5rem">'+title+'</div>'+
                        '<div class="ia-table-wrap"><table class="ia-table">';
                html += '<thead><tr>';
                for (var i=0;i<cols.length;i++){ html += '<th class="ia-th">'+cols[i]+'</th>'; }
                html += '</tr></thead><tbody>';
                var rows = Array.isArray(items) ? items : [];
                if (rows.length === 0){
                    html += '<tr><td class="ia-td" colspan="'+cols.length+'">Sin datos.</td></tr>';
                }
                function pick(){
                    var args = Array.prototype.slice.call(arguments);
                    for (var k=0;k<args.length;k++){ var v = args[k]; if (typeof v !== 'undefined' && v !== null && v !== '') return v; }
                    return '';
                }
                function cellValue(label, raw){
                    var l = (label||'').toLowerCase();
                    if (l.indexOf('producto') !== -1) return pick(raw.nombre, raw.Name, raw.producto, raw.descripcion);
                    if (l.indexOf('presentacion') !== -1) return pick(raw.presentacion, raw.concentracion, raw.nombre_presentacion, raw.presentacion_nombre);
                    if (l.indexOf('unidades') !== -1) return pick(raw.unidades, raw.Unidades, raw.unidad, raw.qty, raw.veces, raw.ventas);
                    if (l.indexOf('stock') !== -1) return pick(raw.stock, raw.existencias, raw.existencia, raw.cantidad_disponible, raw.qty_disponible);
                    if (l.indexOf('vence') !== -1) return pick(raw.vence, raw.fecha_vencimiento, raw.vencimiento, raw.caducidad, raw.expira);
                    if (l.indexOf('categor') !== -1) return pick(raw.categoria, raw.Categoria, raw.category);
                    if (l.indexOf('cliente') !== -1) return pick(raw.cliente, raw.Cliente, raw.nombre_cliente);
                    if (l.indexOf('vendedor') !== -1) return pick(raw.vendedor, raw.Vendedor, raw.nombre_vendedor);
                    if (l.indexOf('marca') !== -1) return pick(raw.marca, raw.Marca);
                    return pick(raw[label], raw[l]);
                }
                for (var j=0;j<rows.length;j++){
                    var raw = rows[j] || {};
                    html += '<tr>';
                    for (var c=0;c<cols.length;c++){
                        var val = cellValue(cols[c], raw);
                        html += '<td class="ia-td">'+(val||'')+'</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody></table></div></div></div><div class="ia-time">'+nowTime()+'</div></div>';
                wrap.innerHTML = html;
                chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                chat.scrollTop = chat.scrollHeight;
            }

            function detectDevice(){
                var ua = navigator.userAgent || '';
                var w = window.innerWidth || 1024;
                var isMob = /Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i.test(ua) || w < 640;
                return isMob ? 'mobile' : 'web';
            }
            var DEVICE = detectDevice();

            function addAiPrediction(thinkingEl, payload, focus){
                if (thinkingEl) thinkingEl.remove();
                var dd = payload || {};
                if (DEVICE === 'mobile'){
                    var txt = dd.text || 'Pronóstico';
                    return addAiText(null, txt, focus||txt);
                }
                if (dd.plot_png_base64){
                    var wrap = document.createElement('div');
                    wrap.className = 'ia-msg ia-msg-ai';
                    var html = '<div class="ia-avatar ai"><iconify-icon icon="mdi:head-cog-outline"></iconify-icon></div>'+
                        '<div><div class="ia-msg-author">IA</div><div class="ia-msg-text ia-bubble-ai">'+
                        '<div class="ia-card-responsive">'+
                        '<div class="ia-toggle"><button class="ia-btn ia-btn-small ia-btn-active" data-mode="graf">Gráfico</button><button class="ia-btn ia-btn-small" data-mode="texto">Texto</button></div>'+
                        '<div class="ia-panel ia-panel-graf"><img class="ia-img" src="data:image/png;base64,'+dd.plot_png_base64+'"/></div>'+
                        '<div class="ia-panel ia-panel-text" style="display:none">'+(dd.text||'Pronóstico')+'</div>'+
                        '</div></div><div class="ia-time">'+nowTime()+'</div></div>';
                    wrap.innerHTML = html;
                    chat.insertBefore(wrap, chat.querySelector('.ia-input'));
                    var buttons = wrap.querySelectorAll('.ia-toggle .ia-btn');
                    buttons.forEach(function(b){
                        b.addEventListener('click', function(){
                            buttons.forEach(function(x){ x.classList.remove('ia-btn-active'); });
                            b.classList.add('ia-btn-active');
                            var showGraf = b.getAttribute('data-mode') === 'graf';
                            wrap.querySelector('.ia-panel-graf').style.display = showGraf ? '' : 'none';
                            wrap.querySelector('.ia-panel-text').style.display = showGraf ? 'none' : '';
                        });
                    });
                    chat.scrollTop = chat.scrollHeight;
                    return;
                }
                if (Array.isArray(dd.forecast) && dd.forecast.length){
                    return addAiChart(null, dd.text || 'Pronóstico', dd.forecast);
                }
                return addAiText(null, dd.text || 'No hay suficientes datos para pronosticar.', focus||'');
            }

            var style = document.createElement('style');
            style.innerHTML = '.ia-card-responsive{max-width:820px;width:100%}.ia-table-wrap{overflow:auto}.ia-table{width:100%;border-collapse:collapse}.ia-th{text-align:left;font-size:.85rem;color:#334155;padding:.5rem .4rem}.ia-td{padding:.45rem .4rem;border-bottom:1px solid #f1f5f9}.ia-img{max-width:100%;border-radius:12px}.ia-toggle{display:flex;gap:.5rem;margin-bottom:.5rem}.ia-btn{background:#f1f5f9;border:none;padding:.35rem .6rem;border-radius:20px;font-size:.8rem;color:#334155}.ia-btn-active{background:#ef4444;color:#fff}.ia-msg{margin:14px 0}.ia-bubble-ai{max-width:92vw}@media(min-width:768px){.ia-bubble-ai{max-width:720px}}';
            document.head.appendChild(style);

            function normalize(s){
                return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            }

            async function fetchJson(url, timeoutMs){
                var ctrl = new AbortController();
                var id = setTimeout(function(){ try{ ctrl.abort(); }catch(e){} }, timeoutMs||5000);
                try{
                    var res = await fetch(url, { signal: ctrl.signal });
                    clearTimeout(id);
                    return await res.json();
                }catch(e){
                    clearTimeout(id);
                    return null;
                }
            }

            async function updateRagStatus(){
                try{
                    var j = await fetchJson('/api/ia/rag-health', 3000);
                    if (j && j.success){
                        if (statusEl) statusEl.innerHTML = '<span class="ia-dot"></span> Online';
                        if (dotEl) dotEl.style.background = '#10b981';
                    } else {
                        if (statusEl) statusEl.innerHTML = '<span class="ia-dot"></span> IA PHP';
                        if (dotEl) dotEl.style.background = '#f59e0b';
                    }
                }catch(e){
                    if (statusEl) statusEl.innerHTML = '<span class="ia-dot"></span> IA PHP';
                    if (dotEl) dotEl.style.background = '#f59e0b';
                }
            }

            function detectIntent(text){
                var t = normalize(text);
                if (t.includes('agotad')) return 'agotados';
                if (t.includes('critico') || t.includes('critica') || t.includes('bajo stock')) return 'critico';
                if ((t.includes('marca') || t.includes('marcas')) && (t.includes('mas vendidas') || t.includes('más vendidas') || t.includes('top'))) return 'nl_sql';
                if ((t.includes('cliente') || t.includes('clientes')) && (t.includes('compra mas') || t.includes('compra más') || t.includes('gasta mas') || t.includes('gasta más'))) return 'nl_sql';
                if (t.includes('mas vendido') || t.includes('top') || t.includes('populares')) return 'top';
                if (t.includes('rotacion')) return 'rotacion';
                if (t.includes('por vencer') || t.includes('vencer') || t.includes('vence') || t.includes('caduc')) return 'por_vencer';
                if ((t.includes('cuantos') || t.includes('cuántos')) && (t.includes('se vendieron') || t.includes('vendieron') || t.includes('unidades vendidas') || t.includes('ventas'))) return 'ventas_resumen';
                if ((t.includes('top') && (t.includes('categoria') || t.includes('categorias'))) || t.includes('categorias mas vendidas') || t.includes('categorías más vendidas')) return 'top_categorias';
                if (t.includes('ventas por categoria') || t.includes('ventas por categorías') || t.includes('resumen por categoria') || t.includes('categorias del mes')) return 'resumen_categorias';
                if (t.includes('top vendedores') || t.includes('mejores vendedores') || t.includes('vendedores')) return 'top_vendedores';
                if (t.includes('clientes frecuentes') || t.includes('mejores clientes') || (t.includes('clientes') && t.includes('top'))) return 'clientes_frecuentes';
                if ((t.includes('stock') && t.includes('critico') && (t.includes('categoria') || t.includes('categoría'))) || t.includes('bajo stock en categoria')) return 'critico_categoria';
                if (t.includes('sin ventas') || t.includes('no vendidos') || t.includes('nunca vendidos')) return 'sin_ventas';
                if (t.includes('sobre stock') || t.includes('exceso stock') || t.includes('stock alto')) return 'sobre_stock';
                var mentionProd = t.includes('producto') || t.includes('productos') || t.includes('prod') || t.includes('articulos') || t.includes('artículo');
                var askCount = t.includes('cuantos') || t.includes('cuántos') || t.includes('cuant') || t.includes('cantidad') || t.includes('total') || t.includes('numero') || t.includes('número') || t.includes('registrados') || (t.includes('tengo') && mentionProd);
                if (askCount && mentionProd) return 'count_products';
                if ((t.includes('venta') || t.includes('ventas')) && t.includes('ayer')) return 'ventas_ayer';
                if (t.includes('pronostic') || t.includes('predec') || t.includes('se vendera') || t.includes('se venderá') || t.includes('proximo mes') || t.includes('próximo mes')) return 'predict';
                var sqlish = ['cuenta','cuántos','listar','lista','total','promedio','media','suma','sumar','maximo','máximo','minimo','mínimo','top','mayor','menor'];
                for (var i=0;i<sqlish.length;i++){ if (t.includes(sqlish[i])) return 'nl_sql'; }
                return 'llm';
            }

            function parsePeriodo(text){
                var t = normalize(text);
                if (t.includes('hoy')) return 1;
                if (t.includes('semana')) return 7;
                if (t.includes('mes')) return 30;
                var m = t.match(/(\d{1,3})\s*dias?/);
                if (m) return parseInt(m[1]);
                return 30;
            }

            async function handleIntent(text){
                var intent = detectIntent(text);
                var thinking = addThinking();
                try{
                    if (intent === 'predict'){
                        var jp = await fetchJson('/api/ia/predict-sklearn?q='+encodeURIComponent(text), 8000);
                        var pd = (jp && jp.data) ? jp.data : {};
                        addAiPrediction(thinking, pd, text);
                        return;
                    }
                    if (intent === 'agotados'){
                        var j = await fetchJson('/api/ia/analytics/agotados?limit=8', 6000);
                        var data = (j && Array.isArray(j.data)) ? j.data.slice(0,8) : [];
                        if (data.length === 0){
                            var ns = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent('listar productos agotados'), 12000);
                            var nt = (ns && (ns.text || (ns.data && ns.data.text))) ? (ns.text || ns.data.text) : 'Sin datos.';
                            addAiText(thinking, nt, text);
                        } else {
                            addAiList(thinking, 'Productos agotados', data, ['Producto','Stock'], text);
                        }
                        return;
                    }
                    if (intent === 'count_products'){
                        var jcp = await fetchJson('/api/mobile/productos/count', 6000);
                        var c = (jcp && jcp.data && typeof jcp.data.count !== 'undefined') ? jcp.data.count : 0;
                        addAiText(thinking, 'Total de productos registrados: '+c+'.', text);
                        return;
                    }
                    if (intent === 'ventas_ayer'){
                        var jy = await fetchJson('/api/ia/analytics/ventas-ayer', 6000);
                        var total = (jy && jy.data && typeof jy.data.total_unidades !== 'undefined') ? jy.data.total_unidades : 0;
                        addAiText(thinking, 'Unidades vendidas ayer: '+total+'.', text);
                        return;
                    }
                    if (intent === 'critico'){
                        var j2 = await fetchJson('/api/ia/analytics/critico?limit=8', 6000);
                        var d2 = (j2 && Array.isArray(j2.data)) ? j2.data.slice(0,8) : [];
                        if (d2.length === 0){
                            var ns2 = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent('listar productos con stock crítico'), 12000);
                            var nt2 = (ns2 && (ns2.text || (ns2.data && ns2.data.text))) ? (ns2.text || ns2.data.text) : 'Sin datos.';
                            addAiText(thinking, nt2, text);
                        } else {
                            addAiList(thinking, 'Stock crítico', d2, ['Producto','Stock'], text);
                        }
                        return;
                    }
                    if (intent === 'por_vencer'){
                        var pd = parsePeriodo(text);
                        var jpv = await fetchJson('/api/mobile/productos/por-vencer?dias='+encodeURIComponent(pd), 6000);
                        var dpv = (jpv && Array.isArray(jpv.data)) ? jpv.data.slice(0,8) : [];
                        addAiList(thinking, 'Próximos a vencer ('+pd+' días)', dpv, ['Producto','Stock','Vence'], text);
                        return;
                    }
                    if (intent === 'top'){
                        var p = parsePeriodo(text);
                        var j3 = await fetchJson('/api/ia/analytics/top-ventas?periodo='+encodeURIComponent(p)+'&limit=8', 6000);
                        var d3 = (j3 && Array.isArray(j3.data)) ? j3.data.slice(0,8) : [];
                        if (d3.length === 0){
                            var ns3 = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent('top productos más vendidos en '+p+' días'), 12000);
                            var nt3 = (ns3 && (ns3.text || (ns3.data && ns3.data.text))) ? (ns3.text || ns3.data.text) : 'Sin datos.';
                            addAiText(thinking, nt3, text);
                        } else {
                            addAiList(thinking, 'Más vendidos ('+p+' días)', d3, ['Producto','Unidades'], text);
                        }
                        return;
                    }
                    if (intent === 'rotacion'){
                        var p2 = parsePeriodo(text);
                        var j4 = await fetchJson('/api/ia/analytics/rotacion-minima?periodo='+encodeURIComponent(p2)+'&limit=8', 6000);
                        var d4 = (j4 && Array.isArray(j4.data)) ? j4.data.slice(0,8) : [];
                        if (d4.length === 0){
                            var ns4 = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent('productos con menor rotación en '+p2+' días'), 12000);
                            var nt4 = (ns4 && (ns4.text || (ns4.data && ns4.data.text))) ? (ns4.text || ns4.data.text) : 'Sin datos.';
                            addAiText(thinking, nt4, text);
                        } else {
                            addAiList(thinking, 'Menor rotación ('+p2+' días)', d4, ['Producto','Unidades'], text);
                        }
                        return;
                    }
                    if (intent === 'ventas_resumen'){
                        var pr = parsePeriodo(text);
                        var jr = await fetchJson('/api/mobile/ventas/resumen?periodo='+encodeURIComponent(pr), 6000);
                        var total = (jr && jr.data && typeof jr.data.total_unidades !== 'undefined') ? jr.data.total_unidades : 0;
                        var ir = (jr && jr.data && Array.isArray(jr.data.items)) ? jr.data.items.slice(0,8) : [];
                        addAiText(thinking, 'Unidades vendidas ('+pr+' días): '+total+'.', text);
                        addAiList(null, 'Detalle de ventas', ir, ['Producto','Unidades'], text);
                        return;
                    }
                    if (intent === 'sin_ventas'){
                        var ps = parsePeriodo(text);
                        var js = await fetchJson('/api/mobile/productos/sin-ventas?dias='+encodeURIComponent(ps), 6000);
                        var ds = (js && Array.isArray(js.data)) ? js.data.slice(0,8) : [];
                        addAiList(thinking, 'Sin ventas ('+ps+' días)', ds, ['Producto','Stock'], text);
                        return;
                    }
                    if (intent === 'sobre_stock'){
                        var jf = await fetchJson('/api/mobile/stock/sobre?factor=3', 6000);
                        var df = (jf && Array.isArray(jf.data)) ? jf.data.slice(0,8) : [];
                        addAiList(thinking, 'Sobre stock (≥3× mínimo)', df, ['Producto','Stock'], text);
                        return;
                    }
                    if (intent === 'top_categorias'){
                        var pc = parsePeriodo(text);
                        var jc = await fetchJson('/api/mobile/ventas/categorias/top?periodo='+encodeURIComponent(pc), 6000);
                        var ic = (jc && Array.isArray(jc.data)) ? jc.data.slice(0,8) : [];
                        addAiList(thinking, 'Top categorías ('+pc+' días)', ic, ['Categoría','Unidades'], text);
                        return;
                    }
                    if (intent === 'resumen_categorias'){
                        var prc = parsePeriodo(text);
                        var jrc = await fetchJson('/api/mobile/ventas/categorias/resumen?periodo='+encodeURIComponent(prc), 6000);
                        var trc = (jrc && jrc.data && typeof jrc.data.total_unidades !== 'undefined') ? jrc.data.total_unidades : 0;
                        var irc = (jrc && jrc.data && Array.isArray(jrc.data.items)) ? jrc.data.items.slice(0,8) : [];
                        addAiText(thinking, 'Unidades vendidas por categorías ('+prc+' días): '+trc+'.', text);
                        addAiList(null, 'Detalle por categorías', irc, ['Categoría','Unidades'], text);
                        return;
                    }
                    if (intent === 'top_vendedores'){
                        var pv = parsePeriodo(text);
                        var jv = await fetchJson('/api/mobile/ventas/vendedores/top?periodo='+encodeURIComponent(pv), 6000);
                        var iv = (jv && Array.isArray(jv.data)) ? jv.data.slice(0,8) : [];
                        addAiList(thinking, 'Top vendedores ('+pv+' días)', iv, ['Vendedor','Unidades'], text);
                        return;
                    }
                    if (intent === 'clientes_frecuentes'){
                        var pcl = parsePeriodo(text);
                        var jcl = await fetchJson('/api/mobile/ventas/clientes/top?periodo='+encodeURIComponent(pcl), 6000);
                        var icl = (jcl && Array.isArray(jcl.data)) ? jcl.data.slice(0,8) : [];
                        addAiList(thinking, 'Clientes frecuentes ('+pcl+' días)', icl, ['Cliente','Unidades'], text);
                        return;
                    }
                    if (intent === 'critico_categoria'){
                        var m = text.match(/categoria\s+([^\d\n]+)/i);
                        var cat = m && m[1] ? m[1].trim() : '';
                        var jcc = await fetchJson('/api/mobile/stock/critico-categoria?categoria='+encodeURIComponent(cat), 6000);
                        var dcc = (jcc && Array.isArray(jcc.data)) ? jcc.data.slice(0,8) : [];
                        addAiList(thinking, 'Stock crítico por categoría'+(cat?' ('+cat+')':''), dcc, ['Producto','Stock'], text);
                        return;
                    }
                    if (intent === 'nl_sql'){
                        var ns = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent(text), 12000);
                        if (ns && ns.success && Array.isArray(ns.data) && ns.data.length){
                            var cols = Object.keys(ns.data[0] || {});
                            addAiList(thinking, (ns.text||'Resultados'), ns.data.slice(0,8), cols);
                            return;
                        }
                        if (ns && (ns.text || (ns.data && ns.data.text))){
                            var tx = ns.text || ns.data.text;
                            addAiText(thinking, tx, text);
                            return;
                        }
                    }
                    var j5 = await fetchJson('/api/ia/rag-chat?q='+encodeURIComponent(text), 9000);
                    var dd = (j5 && j5.data) ? (j5.data.text ? j5.data : (j5.data.data || {})) : {};
                    if (dd && dd.text){ addAiText(thinking, dd.text, text); } else { addAiText(thinking, 'No disponible.', text); }
                    }catch(e){
                    addAiText(thinking, 'Error consultando datos.');
                }
            }

            async function handleSend(){
                var text = (input.value || '').trim();
                if (!text) return;
                addUser(text);
                input.value='';
                var intent = detectIntent(text);
                if (intent === 'llm'){
                    var thinking = addThinking();
                    var ns = await fetchJson('/api/ia/nl-sql?q='+encodeURIComponent(text), 12000);
                    if (ns && ns.success && Array.isArray(ns.data) && ns.data.length){
                        var cols = Object.keys(ns.data[0] || {});
                        addAiList(thinking, (ns.text||'Resultados'), ns.data.slice(0,8), cols);
                    } else if (ns && (ns.text || (ns.data && ns.data.text))){
                        var nt = ns.text || ns.data.text;
                        addAiText(thinking, nt, text);
                    } else {
                        var rj = await fetchJson('/api/ia/rag-chat?q='+encodeURIComponent(text), 9000);
                        if (rj && rj.data && (rj.data.text || (rj.data.data && rj.data.data.text))){
                            var rt = rj.data.text || rj.data.data.text;
                            addAiText(thinking, rt, text);
                        } else {
                            var lj = await fetchJson('/api/ia/chat-llm?q='+encodeURIComponent(text), 8000);
                            if (lj && lj.data && lj.data.text){ addAiText(thinking, lj.data.text, text); } else { addAiText(thinking, 'No disponible.', text); }
                        }
                    }
                } else {
                    await handleIntent(text);
                }
            }

            send.addEventListener('click', handleSend);
            input.addEventListener('keydown', function(e){ if (e.key==='Enter'){ e.preventDefault(); handleSend(); } });
            if (btnClear) btnClear.addEventListener('click', function(){ try{ localStorage.removeItem('iaChatHistory'); }catch(e){} var msgs = chat.querySelectorAll('.ia-msg'); for (var i=0;i<msgs.length;i++){ var el=msgs[i]; if (i===0) continue; el.remove(); } });
            renderHistory();
            updateRagStatus();
            setInterval(updateRagStatus, 20000);
        });
    </script>
