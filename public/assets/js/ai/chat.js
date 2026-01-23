/**
 * BOTICA SAN ANTONIO - Chat de IA
 * Interacción con el motor de Gemini AI
 */

class AIChat {
    constructor() {
        this.messagesContainer = document.getElementById('aiMessages');
        this.input = document.getElementById('aiInput');
        this.sendBtn = document.getElementById('aiSendBtn');
        this.suggestionsContainer = document.getElementById('aiSuggestions');
        this.clearHistoryBtn = document.getElementById('btnClearHistory');
        
        this.isProcessing = false;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.init();
    }
    
    init() {
        // Event listeners
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Suggestions click
        this.suggestionsContainer?.querySelectorAll('.ai-suggestion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const suggestion = btn.dataset.suggestion;
                this.input.value = suggestion;
                this.sendMessage();
            });
        });
        
        // Clear history
        this.clearHistoryBtn?.addEventListener('click', () => this.clearHistory());
        
        // Prediction buttons - Removido para evitar duplicados
        // Los botones se manejan en predictions-init.js
        
        // Focus input
        this.input.focus();
        
        // Scroll to bottom
        this.scrollToBottom();
        
        // Check connection status
        this.checkConnectionStatus();
        
        // Check status every 30 seconds
        setInterval(() => this.checkConnectionStatus(), 30000);
    }
    
    async sendMessage() {
        const message = this.input.value.trim();
        
        if (!message || this.isProcessing) return;
        
        this.isProcessing = true;
        this.input.value = '';
        this.sendBtn.disabled = true;

        // Guardar la última pregunta para generar sugerencias contextuales en fallbacks
        this.lastUserMessage = message;
        
        // Add user message
        this.addMessage(message, true);
        
        // Show typing indicator
        this.showTyping();
        
        try {
            const response = await fetch('/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ message })
            });
            
            // Remove typing indicator
            this.hideTyping();
            
            if (response.status === 429) {
                this.addMessage('⏳ Has alcanzado el límite de consultas. Espera un momento antes de intentar de nuevo.', false, true);
                return;
            }
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Limpiar respuesta de cualquier formato JSON
                let cleanResponse = this.cleanResponse(data.response);
                
                // Limpieza adicional para JSON persistente
                if (cleanResponse.includes('"response":') || cleanResponse.includes('needs_sql')) {
                    // Extraer solo el texto después de "response":
                    const responseMatch = cleanResponse.match(/"response":\s*"([^"]+)"/);
                    if (responseMatch) {
                        cleanResponse = responseMatch[1];
                    } else {
                        // Si no encuentra el patrón, eliminar todo JSON
                        cleanResponse = cleanResponse
                            .replace(/.*"response":\s*"/g, '')
                            .replace(/",?\s*".*$/g, '')
                            .replace(/[{}]/g, '')
                            .replace(/needs_sql.*$/g, '')
                            .trim();
                    }
                }

                cleanResponse = this.replaceGenericHelpIfNeeded(cleanResponse, this.lastUserMessage, data.is_data);
                
                // Si hay un gráfico, agregarlo a la respuesta
                if (data.chart_image) {
                    const chartHtml = `
                        <div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; text-align: center;">
                            <img src="${data.chart_image}" alt="Gráfico de Predicción" 
                                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); border: 2px solid #fff;">
                        </div>
                    `;
                    cleanResponse = cleanResponse + "\n\n" + chartHtml;
                }
                
                this.addMessage(cleanResponse, false, false, data.sql_query);
            } else {
                this.addMessage(data.response || 'Lo siento, ocurrió un error.', false, true);
            }
            
        } catch (error) {
            console.error('Error en chat:', error);
            this.hideTyping();
            this.addMessage('❌ Error de conexión. Por favor, intenta de nuevo.', false, true);
        } finally {
            this.isProcessing = false;
            this.sendBtn.disabled = false;
            this.input.focus();
        }
    }
    
    addMessage(text, isUser = false, isError = false, sqlQuery = null) {
        console.log('📝 Agregando mensaje:', { isUser, hasImages: text.includes('data:image'), textLength: text.length });
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${isUser ? 'ai-message-user' : 'ai-message-bot'}`;
        
        const now = new Date();
        const timeStr = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
        
        // Procesar imágenes si existe ImageHandler
        if (window.ImageHandler && !isUser) {
            text = window.ImageHandler.processResponse(text);
        }
        
        // Debug: verificar si el texto contiene PRESERVED
        if (text.includes('PRESERVED')) {
            console.warn('⚠️ Texto contiene PRESERVED elements:', text.substring(0, 200) + '...');
        }
        
        // Format text with markdown-like parsing
        let formattedText = this.formatText(text);
        
        // Debug: verificar el resultado del formateo
        if (formattedText.includes('PRESERVED')) {
            console.error('❌ PRESERVED elements no fueron restaurados correctamente');
            console.log('Original text:', text.substring(0, 500));
            console.log('Formatted text:', formattedText.substring(0, 500));
        }
        
        // Si aún hay PRESERVED elements, intentar arreglar manualmente
        if (formattedText.includes('PRESERVEDELEMENT')) {
            console.log('🔧 Intentando arreglar PRESERVED elements manualmente...');
            // Buscar el patrón original en el texto
            const imgPattern = /<div[^>]*>[\s\S]*?<img[^>]*src="data:image[^>]*>[\s\S]*?<\/div>/g;
            const imgMatch = text.match(imgPattern);
            if (imgMatch && imgMatch.length > 0) {
                console.log('✅ Encontrada imagen original, reemplazando...');
                formattedText = formattedText.replace(/PRESERVEDELEMENT\d+/g, imgMatch[0]);
            }
        }
        
        // NO mostrar SQL al usuario - solo respuesta natural
        
        messageDiv.innerHTML = `
            <div class="ai-avatar">
                <iconify-icon icon="${isUser ? 'solar:user-bold-duotone' : 'solar:magic-stick-3-bold-duotone'}"></iconify-icon>
            </div>
            <div class="ai-message-content">
                <div class="ai-message-text ${isError ? 'ai-error' : ''}">
                    ${formattedText}
                </div>
                <div class="ai-message-time">${timeStr}</div>
            </div>
        `;
        
        this.messagesContainer.appendChild(messageDiv);

        if (!isUser) {
            this.bindSuggestionButtons(messageDiv);
        }
        
        // Mejorar visualización de imágenes si existe ImageHandler
        if (window.ImageHandler && !isUser) {
            window.ImageHandler.enhanceImageDisplay(messageDiv);
        }
        
        this.scrollToBottom();
    }

    replaceGenericHelpIfNeeded(text, userMessage = '', isData = false) {
        if (!text || typeof text !== 'string') return text;
        
        // Si el servidor indica que es data real o la respuesta es larga, no reemplazar
        if (isData || text.length > 250) return text;

        const t = text.toLowerCase();
        
        // Solo considerar genérico si es el mensaje de ayuda específico o es muy corto y tiene palabras de ayuda
        const isHelpMessage = 
            (t.includes('prueba pregunt') && t.includes('ayudarte')) || 
            (t.includes('puedo ayudarte') && t.includes('botones') && t.includes('predicciones'));
            
        const looksGeneric = isHelpMessage || (text.length < 100 && (t.includes('prueba pregunt') || t.includes('puedo ayudarte con consultas')));

        if (!looksGeneric) return text;

        const suggestions = this.getContextualSuggestions(userMessage);

        const buttonsHtml = suggestions
            .map(s => `<button class="ai-suggestion-btn" type="button" data-suggestion="${this.escapeAttribute(s)}">${this.escapeHtml(s)}</button>`)
            .join('');

        // Devolver HTML crudo con prefijo para que el renderer no lo escape
        return `__RAW_HTML__
            <div style="margin-bottom:8px;"><strong>No entendí bien tu consulta</strong>. ¿Te refieres a algo como esto?</div>
            <div class="ai-suggestions" style="display:flex; flex-wrap:wrap; gap:8px;">${buttonsHtml}</div>
            <div style="margin-top:10px; color:#6b7280; font-size:0.9rem;">Puedes escribirlo diferente, por ejemplo: <em>"por vencer"</em>, <em>"vencen pronto"</em> o <em>"caducan"</em>.</div>
        `;
    }

    getContextualSuggestions(userMessage = '') {
        const m = String(userMessage || '').toLowerCase();

        const isExpiry =
            m.includes('venc') ||
            m.includes('caduc') ||
            m.includes('por vencer') ||
            m.includes('vencimiento') ||
            m.includes('lote');

        const isStock =
            m.includes('stock') ||
            m.includes('agot') ||
            m.includes('invent') ||
            m.includes('dispon') ||
            m.includes('cantidad');

        const isSales =
            m.includes('vend') ||
            m.includes('venta') ||
            m.includes('ingres') ||
            m.includes('ticket') ||
            m.includes('gan');

        if (isExpiry) {
            return [
                '⏰ ¿Qué productos están por vencer?',
                '⏰ ¿Qué lotes vencen pronto?',
                '⏰ ¿Qué productos vencen en 30 días?',
                '⏰ ¿Qué productos ya están vencidos?',
                '📦 ¿Qué productos están agotados?'
            ];
        }

        if (isStock) {
            return [
                '📦 ¿Qué productos están agotados?',
                '📦 ¿Qué productos tienen stock bajo?',
                '📦 ¿Cuánto stock tengo de amoxicilina 500mg?',
                '📦 Lista productos por categoría',
                '⏰ ¿Qué lotes vencen pronto?'
            ];
        }

        if (isSales) {
            return [
                '💰 ¿Cuánto vendí hoy?',
                '💰 ¿Cuánto vendí ayer?',
                '🔥 ¿Cuáles son los más vendidos?',
                '📊 Compara ventas de hoy vs ayer',
                '📊 Ventas de este mes'
            ];
        }

        return [
            '💰 ¿Cuánto vendí hoy?',
            '📦 ¿Qué productos tienen stock bajo?',
            '⏰ ¿Qué lotes vencen pronto?',
            '🔥 ¿Cuáles son los más vendidos?',
            '📋 Lista productos por categoría'
        ];
    }

    bindSuggestionButtons(container) {
        if (!container) return;

        const buttons = container.querySelectorAll('.ai-suggestion-btn[data-suggestion]');
        if (!buttons || buttons.length === 0) return;

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const suggestion = btn.dataset.suggestion || btn.textContent || '';
                if (!suggestion) return;
                this.input.value = suggestion;
                this.sendMessage();
            });
        });
    }

    escapeAttribute(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    
    cleanResponse(text) {
        // Si contiene JSON, extraer solo la respuesta
        if (text.includes('```json') || text.includes('"needs_sql"') || text.includes('"response"')) {
            try {
                // Buscar patrón "response": "texto" con escape de caracteres
                const match = text.match(/"response":\s*"((?:[^"\\]|\\.)*)"/);
                if (match) {
                    // Decodificar caracteres escapados
                    let response = match[1];
                    response = response.replace(/\\n/g, '\n');
                    response = response.replace(/\\"/g, '"');
                    response = response.replace(/\\\\/g, '\\');
                    return response;
                }
                
                // Buscar JSON completo
                const jsonMatch = text.match(/\{[\s\S]*?\}/);
                if (jsonMatch) {
                    const parsed = JSON.parse(jsonMatch[0]);
                    if (parsed.response) {
                        let response = parsed.response;
                        // Decodificar caracteres escapados
                        response = response.replace(/\\n/g, '\n');
                        response = response.replace(/\\"/g, '"');
                        response = response.replace(/\\\\/g, '\\');
                        return response;
                    }
                }
            } catch (e) {
                console.warn('Error parsing JSON response:', e);
            }
        }
        
        // Remover TODOS los bloques de código SQL
        text = text.replace(/```sql[\s\S]*?```/gi, '');
        text = text.replace(/```[\s\S]*?```/g, '');
        
        // Remover líneas que contienen "CONSULTA SQL EJECUTADA"
        text = text.replace(/CONSULTA SQL EJECUTADA:[\s\S]*?(?=\n|$)/gi, '');
        text = text.replace(/Consulta SQL ejecutada:[\s\S]*?(?=\n|$)/gi, '');
        
        // Remover fragmentos de SQL completos
        text = text.replace(/SELECT[\s\S]*?;/gi, '');
        text = text.replace(/SELECT[\s\S]*?(?=\n|$)/gi, '');
        
        // Remover cualquier mención de SQL
        text = text.replace(/SQL:[\s\S]*?(?=\n|$)/gi, '');
        
        // Remover palabras clave SQL sueltas
        text = text.replace(/\b(SELECT|FROM|WHERE|JOIN|GROUP BY|ORDER BY|LIMIT|COUNT|SUM|COALESCE)\b[\s\S]*?(?=\n|$)/gi, '');
        
        // Remover texto técnico específico
        text = text.replace(/CONSULTA\s+SQL\s+EJECUTADA:?/gi, '');
        text = text.replace(/total[a-z_]*:\s*[S\/\.\d,]+/gi, '');
        text = text.replace(/[a-z_]*productos[a-z_]*:\s*\d+/gi, '');
        
        // Remover patrones técnicos
        text = text.replace(/\b[a-z_]+\.[a-z_]+\b/gi, ''); // tabla.columna
        text = text.replace(/\b(venta_detalles|productos|ventas)\b/gi, ''); // nombres de tablas
        
        // Si aún contiene JSON, intentar extraer solo el texto
        if (text.includes('{') && text.includes('}')) {
            try {
                const parsed = JSON.parse(text);
                if (parsed.response) {
                    return parsed.response;
                }
            } catch (e) {
                // Si no se puede parsear, devolver el texto original limpio
                return text.replace(/[{}]/g, '').replace(/"/g, '').trim();
            }
        }
        
        // Limpiar espacios horizontales extra pero PRESERVAR saltos de línea (\n)
        return text.replace(/[ \t]+/g, ' ').trim();
    }

    formatText(text) {
        // Permitir HTML crudo para mensajes generados por el UI (fallback contextual)
        if (typeof text === 'string' && text.startsWith('__RAW_HTML__')) {
            return text.replace(/^__RAW_HTML__\s*/, '');
        }

        // Si el texto contiene HTML con imágenes base64, no hacer escape HTML
        if (text.includes('<div') && text.includes('data:image')) {
            console.log('🖼️ Detectado contenido con imágenes, procesando sin escape HTML');
            
            // Solo aplicar formateo básico sin escape HTML
            let formatted = text;
            
            // Bold: **text** or __text__ (solo fuera de tags HTML)
            formatted = formatted.replace(/\*\*([^<]*?)\*\*/g, '<strong>$1</strong>');
            formatted = formatted.replace(/__([^<]*?)__/g, '<strong>$1</strong>');
            
            // Line breaks (solo fuera de tags HTML)
            formatted = formatted.replace(/\n(?![^<]*>)/g, '<br>');
            
            // Lists (solo fuera de tags HTML) - Soporta "-" y "•"
            formatted = formatted.replace(/^[•-] ([^<].*?)(<br>|$)/gm, '<li>$1</li>');
            formatted = formatted.replace(/(<li>.*<\/li>)+/g, '<ul>$&</ul>');
            
            // Numbered lists (solo fuera de tags HTML)
            formatted = formatted.replace(/^\d+\. ([^<].*?)(<br>|$)/gm, '<li>$1</li>');
            
            return formatted;
        }
        
        // Para texto sin HTML, usar el proceso normal con escape
        let formatted = this.escapeHtml(text);
        
        // Bold: **text** or __text__
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/__(.*?)__/g, '<strong>$1</strong>');
        
        // Italic: *text* or _text_
        formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/_(.*?)_/g, '<em>$1</em>');
        
        // Line breaks
        formatted = formatted.replace(/\n/g, '<br>');
        
        // Lists - Soporta "-" y "•"
        formatted = formatted.replace(/^[•-] (.*?)(<br>|$)/gm, '<li>$1</li>');
        formatted = formatted.replace(/(<li>.*<\/li>)+/g, '<ul>$&</ul>');
        
        // Numbered lists
        formatted = formatted.replace(/^\d+\. (.*?)(<br>|$)/gm, '<li>$1</li>');
        
        return formatted;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-message ai-message-bot';
        typingDiv.id = 'ai-typing-indicator';
        
        typingDiv.innerHTML = `
            <div class="ai-avatar">
                <iconify-icon icon="solar:magic-stick-3-bold-duotone"></iconify-icon>
            </div>
            <div class="ai-message-content">
                <div class="ai-message-text">
                    <div class="ai-typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        
        this.messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTyping() {
        const typingIndicator = document.getElementById('ai-typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    async clearHistory() {
        if (!confirm('¿Estás seguro de que quieres limpiar todo el historial?')) {
            return;
        }
        
        try {
            const response = await fetch('/ai/history', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            if (response.ok) {
                // Clear messages container, keeping only welcome message
                const messages = this.messagesContainer.querySelectorAll('.ai-message');
                messages.forEach((msg, index) => {
                    if (index > 0) msg.remove();
                });
                
                // Show notification
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Historial limpiado',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        } catch (error) {
            console.error('Error clearing history:', error);
        }
    }
    
    async checkConnectionStatus() {
        try {
            const response = await fetch('/ai/health', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            const statusElement = document.querySelector('.ai-status');
            if (statusElement) {
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'healthy' || data.gemini_status === 'connected') {
                        statusElement.innerHTML = '<iconify-icon icon="solar:check-circle-bold"></iconify-icon> Conectado';
                        statusElement.className = 'ai-status ai-status-connected';
                    } else {
                        statusElement.innerHTML = '<iconify-icon icon="solar:close-circle-bold"></iconify-icon> Degradado';
                        statusElement.className = 'ai-status ai-status-degraded';
                    }
                } else {
                    statusElement.innerHTML = '<iconify-icon icon="solar:close-circle-bold"></iconify-icon> Desconectado';
                    statusElement.className = 'ai-status ai-status-disconnected';
                }
            }
        } catch (error) {
            console.error('Error checking status:', error);
            const statusElement = document.querySelector('.ai-status');
            if (statusElement) {
                statusElement.innerHTML = '<iconify-icon icon="solar:close-circle-bold"></iconify-icon> Desconectado';
                statusElement.className = 'ai-status ai-status-disconnected';
            }
        }
    }

    // Prediction methods
    async showSalesPrediction() {
        this.addMessage('Generar predicciones de ventas futuras con Machine Learning', true);
        this.showTyping();
        
        try {
            const response = await fetch('/ai/predict/sales?days_ahead=7', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            this.hideTyping();
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayPredictionResults(data, 'ventas');
            } else {
                this.addMessage(`❌ Error: ${data.error}`, false, true);
            }
            
        } catch (error) {
            this.hideTyping();
            this.addMessage('❌ Error al generar predicciones de ventas. Inténtalo de nuevo.', false, true);
            console.error('Error:', error);
        }
    }

    async showStockAnalysis() {
        this.addMessage('Analizar productos con stock crítico y necesidades de reabastecimiento', true);
        this.showTyping();
        
        try {
            const response = await fetch('/ai/predict/stock', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            this.hideTyping();
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayStockAnalysis(data);
            } else {
                this.addMessage(`❌ Error: ${data.error}`, false, true);
            }
            
        } catch (error) {
            this.hideTyping();
            this.addMessage('❌ Error al analizar stock. Inténtalo de nuevo.', false, true);
            console.error('Error:', error);
        }
    }

    async showTrendsAnalysis() {
        this.addMessage('Generar análisis de tendencias de ventas con gráficos', true);
        this.showTyping();
        
        try {
            const response = await fetch('/ai/analytics/trends', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });
            
            this.hideTyping();
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayTrendsAnalysis(data);
            } else {
                this.addMessage(`❌ Error: ${data.error}`, false, true);
            }
            
        } catch (error) {
            this.hideTyping();
            this.addMessage('❌ Error al analizar tendencias. Inténtalo de nuevo.', false, true);
            console.error('Error:', error);
        }
    }

    displayPredictionResults(data, type) {
        let response = '';
        
        if (type === 'ventas') {
            response = `📊 **Predicción de Ventas - Próximos 7 días**\n\n`;
            
            // Mostrar gráfico generado por Python o crear con Chart.js
            let chartHtml = '';
            if (data.chart_image) {
                // Usar imagen generada por matplotlib
                chartHtml = `<div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; text-align: center;">
                    <img src="${data.chart_image}" alt="Gráfico de Predicción de Ventas" 
                         style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); border: 2px solid #fff;">
                </div>\n\n`;
            } else {
                // Fallback a Chart.js
                const chartId = 'salesChart_' + Date.now();
                chartHtml = `<div style="margin: 20px 0;"><canvas id="${chartId}" width="400" height="250"></canvas></div>\n\n`;
            }
            response += chartHtml;
            
            response += `💰 **Resumen Ejecutivo:**\n`;
            response += `• **Total Proyectado:** S/. ${data.summary.total_predicho.toLocaleString()}\n`;
            response += `• **Promedio Histórico:** S/. ${data.summary.promedio_diario_historico.toLocaleString()}\n`;
            response += `• **Tendencia:** ${data.summary.tendencia}\n`;
            response += `• **Días Analizados:** ${data.summary.dias_analizados}\n\n`;
            
            response += `📅 **Predicciones Detalladas por Día:**\n`;
            
            data.predictions.forEach((pred, index) => {
                const emoji = pred.es_fin_semana ? '🏖️' : 
                             index < 2 ? '🔥' : 
                             index < 4 ? '📊' : '📈';
                const dayType = pred.es_fin_semana ? ' (Fin de semana)' : '';
                response += `${emoji} **${pred.dia_semana}${dayType}** (${pred.fecha})\n`;
                response += `   💰 S/. ${pred.ingresos_predichos.toLocaleString()} - Confianza: ${pred.confianza}\n`;
            });
            
            response += `\n💡 **Insights Inteligentes:**\n`;
            response += `• 📊 Análisis basado en ${data.summary.dias_analizados} días de datos reales\n`;
            response += `• 🏖️ Los fines de semana suelen tener ventas 20-30% menores\n`;
            response += `• 🎯 Predicciones más precisas para los primeros 3 días\n`;
            response += `• 📈 Tendencia actual: ${data.summary.tendencia.toLowerCase()}\n`;
            
            if (data.summary.tendencia === 'Creciente') {
                response += `• 🚀 ¡Excelente! Las ventas están en crecimiento\n`;
            } else if (data.summary.tendencia === 'Decreciente') {
                response += `• ⚠️ Considera estrategias para incrementar ventas\n`;
            }
            
            // Agregar mensaje
            this.addMessage(response);
            
            // Solo crear gráfico Chart.js si no hay imagen de matplotlib
            if (!data.chart_image && chartHtml.includes('canvas')) {
                setTimeout(() => {
                    console.log('🎯 Creando gráfico Chart.js como fallback...');
                    const chartId = chartHtml.match(/id="([^"]+)"/)?.[1];
                    if (chartId) {
                        const canvas = document.getElementById(chartId);
                        if (canvas) {
                            console.log('✅ Canvas encontrado, creando gráfico');
                            this.createSalesChart(chartId, data.predictions);
                        }
                    }
                }, 200);
            } else if (data.chart_image) {
                console.log('✅ Usando gráfico generado por matplotlib');
            }
            
            return; // No llamar addMessage de nuevo
        }
        
        this.addMessage(response);
    }

    createSalesChart(chartId, predictions) {
        const ctx = document.getElementById(chartId);
        if (!ctx) {
            console.error('❌ No se encontró el canvas para el gráfico:', chartId);
            return;
        }

        console.log('📊 Creando gráfico de ventas:', chartId);
        console.log('📊 Datos de predicciones:', predictions);

        // Usar los días en español directamente
        const labels = predictions.map(p => p.dia_semana);
        const data = predictions.map(p => p.ingresos_predichos);
        
        console.log('📊 Labels:', labels);
        console.log('📊 Data:', data);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos Predichos (S/.)',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Predicción de Ventas - Próximos 7 días',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        color: '#1e293b'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: S/. ${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Ingresos (S/.)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'S/. ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Días de la Semana'
                        }
                    }
                }
            }
        });
        
        console.log('✅ Gráfico de ventas creado exitosamente');
    }

    displayStockAnalysis(data) {
        let response = `📦 **Análisis de Stock Crítico**\n\n`;
        
        if (data.productos_criticos.length === 0) {
            response += `✅ **¡Excelente!** No hay productos con stock crítico en este momento.\n`;
            response += `Tu inventario está bien abastecido.`;
        } else {
            response += `⚠️ **${data.estadisticas.total_productos_criticos} productos** necesitan atención:\n\n`;
            
            // Mostrar gráfico generado por Python o crear con Chart.js
            let chartHtml = '';
            if (data.chart_image) {
                // Usar imagen generada por matplotlib
                chartHtml = `<div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); border-radius: 12px; text-align: center;">
                    <img src="${data.chart_image}" alt="Gráfico de Análisis de Stock Crítico" 
                         style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 6px 20px rgba(239,68,68,0.2); border: 2px solid #fff;">
                </div>\n\n`;
            } else {
                // Fallback a Chart.js
                const chartId = 'stockChart_' + Date.now();
                chartHtml = `<div style="margin: 20px 0; text-align: center;"><canvas id="${chartId}" width="400" height="300"></canvas></div>\n\n`;
            }
            response += chartHtml;
            
            // Estadísticas detalladas
            if (data.estadisticas.productos_agotados > 0) {
                response += `🚨 **${data.estadisticas.productos_agotados} AGOTADOS**\n`;
            }
            if (data.estadisticas.productos_criticos > 0) {
                response += `⚠️ **${data.estadisticas.productos_criticos} CRÍTICOS**\n`;
            }
            if (data.estadisticas.productos_stock_bajo > 0) {
                response += `📦 **${data.estadisticas.productos_stock_bajo} STOCK BAJO**\n`;
            }
            if (data.estadisticas.productos_muy_bajo > 0) {
                response += `⚡ **${data.estadisticas.productos_muy_bajo} MUY BAJO**\n`;
            }
            
            response += `\n**Productos que Necesitan Atención:**\n`;
            
            data.productos_criticos.slice(0, 12).forEach((producto, index) => {
                const emoji = producto.nivel_stock === 'AGOTADO' ? '🚨' : 
                             producto.nivel_stock === 'CRÍTICO' ? '⚠️' : 
                             producto.nivel_stock === 'MUY BAJO' ? '⚡' : '📦';
                
                response += `${emoji} **${producto.nombre}** ${producto.concentracion}\n`;
                response += `   Stock: ${producto.stock_actual} (Mín: ${producto.stock_minimo}) - ${producto.nivel_stock}\n`;
                if (producto.cantidad_recomendada) {
                    response += `   💡 Recomendado: ${producto.cantidad_recomendada} unidades\n`;
                }
                response += `\n`;
            });
            
            response += `💡 **Recomendaciones:**\n`;
            if (data.recomendaciones) {
                data.recomendaciones.filter(r => r).forEach(rec => {
                    response += `• ${rec}\n`;
                });
            }
            
            // Agregar mensaje
            this.addMessage(response);
            
            // Solo crear gráfico Chart.js si no hay imagen de matplotlib
            if (!data.chart_image && chartHtml.includes('canvas')) {
                setTimeout(() => {
                    console.log('🎯 Creando gráfico de stock Chart.js como fallback...');
                    const chartId = chartHtml.match(/id="([^"]+)"/)?.[1];
                    if (chartId) {
                        const canvas = document.getElementById(chartId);
                        if (canvas) {
                            this.createStockChart(chartId, data);
                        }
                    }
                }, 100);
            } else if (data.chart_image) {
                console.log('✅ Usando gráfico de stock generado por matplotlib');
            }
            
            return;
        }
        
        this.addMessage(response);
    }

    createStockChart(chartId, data) {
        const ctx = document.getElementById(chartId);
        if (!ctx) return;

        const stats = data.estadisticas;
        const chartData = data.chart_data;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData ? chartData.labels : ['Agotados', 'Críticos', 'Stock Bajo', 'Muy Bajo'],
                datasets: [{
                    data: chartData ? chartData.data : [
                        stats.productos_agotados || 0,
                        stats.productos_criticos || 0,
                        stats.productos_stock_bajo || 0,
                        stats.productos_muy_bajo || 0
                    ],
                    backgroundColor: chartData ? chartData.colors : [
                        '#ef4444', // Rojo para agotados
                        '#f97316', // Naranja para críticos
                        '#eab308', // Amarillo para stock bajo
                        '#f59e0b'  // Amarillo oscuro para muy bajo
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución de Stock Crítico - Botica San Antonio',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        color: '#1e293b'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} productos (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    displayTrendsAnalysis(data) {
        let response = `📈 **Análisis de Tendencias de Ventas**\n\n`;
        
        // Mostrar gráfico generado por Python
        if (data.chart_image) {
            // Usar imagen generada por matplotlib
            const chartHtml = `<div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 100%); border-radius: 12px; text-align: center;">
                <img src="${data.chart_image}" alt="Gráfico de Análisis de Tendencias" 
                     style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 6px 20px rgba(59,130,246,0.2); border: 2px solid #fff;">
            </div>\n\n`;
            response += chartHtml;
            console.log('✅ Usando gráfico de tendencias generado por matplotlib');
        } else {
            console.warn('⚠️ No se generó gráfico de tendencias');
        }
        
        // Insights principales
        if (data.insights && data.insights.length > 0) {
            response += `💡 **Insights Principales:**\n`;
            data.insights.forEach(insight => {
                response += `• ${insight}\n`;
            });
        }
        
        // Mejor día de la semana (con días en español)
        if (data.tendencias_semanales && data.tendencias_semanales.length > 0) {
            response += `\n📅 **Rendimiento por Día de la Semana:**\n`;
            data.tendencias_semanales.forEach(dia => {
                const dayName = dia.dia_semana_es || dia.dia_semana;
                const ingresos = parseFloat(dia.total_ingresos || dia.ingresos_totales || 0);
                response += `• **${dayName}**: ${dia.num_ventas} ventas - S/. ${ingresos.toLocaleString()}\n`;
            });
        }
        
        // Top productos
        if (data.productos_top && data.productos_top.length > 0) {
            response += `\n🔥 **Top 5 Productos Más Vendidos:**\n`;
            data.productos_top.slice(0, 5).forEach((producto, index) => {
                const ingresos = parseFloat(producto.ingresos_generados || producto.total_vendido * 5 || 0); // Estimación si no hay ingresos
                response += `${index + 1}. **${producto.nombre}** ${producto.concentracion || ''}\n`;
                response += `   ${producto.total_vendido} unidades - S/. ${ingresos.toLocaleString()}\n`;
            });
        }
        
        // Ventas recientes
        if (data.ventas_diarias && data.ventas_diarias.length > 0) {
            response += `\n📊 **Últimas Ventas (${data.ventas_diarias.length} días):**\n`;
            data.ventas_diarias.slice(0, 7).forEach(venta => {
                const ingresos = parseFloat(venta.ingresos || 0);
                response += `• **${venta.fecha}**: ${venta.num_ventas} ventas - S/. ${ingresos.toLocaleString()}\n`;
            });
        }
        
        // Agregar mensaje
        this.addMessage(response);
    }

    createTrendsCharts(weeklyChartId, productsChartId, data) {
        // Gráfico semanal
        const weeklyCtx = document.getElementById(weeklyChartId);
        if (weeklyCtx && data.chart_data && data.chart_data.weekday_chart) {
            new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: data.chart_data.weekday_chart.labels,
                    datasets: [{
                        label: 'Ventas por Día',
                        data: data.chart_data.weekday_chart.ventas,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: '#6366f1',
                        borderWidth: 2
                    }, {
                        label: 'Ingresos (S/.)',
                        data: data.chart_data.weekday_chart.ingresos,
                        type: 'line',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: '#10b981',
                        borderWidth: 3,
                        fill: false,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Rendimiento por Día de la Semana',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Número de Ventas'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Ingresos (S/.)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Gráfico de productos más vendidos
        const productsCtx = document.getElementById(productsChartId);
        if (productsCtx && data.chart_data && data.chart_data.top_products_chart) {
            new Chart(productsCtx, {
                type: 'horizontalBar',
                data: {
                    labels: data.chart_data.top_products_chart.labels,
                    datasets: [{
                        label: 'Unidades Vendidas',
                        data: data.chart_data.top_products_chart.ventas,
                        backgroundColor: [
                            '#ef4444', '#f97316', '#eab308', 
                            '#22c55e', '#3b82f6', '#8b5cf6'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top 5 Productos Más Vendidos',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Unidades Vendidas'
                            }
                        }
                    }
                }
            });
        }
    }
}

// Función para verificar estado de la IA
async function checkAIStatus() {
    try {
        const response = await fetch('/ai/health', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        const statusElement = document.getElementById('aiStatus');
        
        if (statusElement) {
            const statusDot = statusElement.querySelector('.status-dot');
            const statusText = statusElement.querySelector('span');
            
            if (data.status === 'healthy') {
                statusElement.className = 'ai-status ai-status-connected';
                if (statusDot) statusDot.className = 'status-dot online';
                if (statusText) statusText.textContent = 'Conectado';
            } else if (data.status === 'degraded') {
                statusElement.className = 'ai-status ai-status-degraded';
                if (statusDot) statusDot.className = 'status-dot offline';
                if (statusText) statusText.textContent = 'Limitado';
            } else {
                statusElement.className = 'ai-status ai-status-disconnected';
                if (statusDot) statusDot.className = 'status-dot offline';
                if (statusText) statusText.textContent = 'Desconectado';
            }
        }
    } catch (error) {
        console.error('Error verificando estado de IA:', error);
        const statusElement = document.getElementById('aiStatus');
        if (statusElement) {
            statusElement.className = 'ai-status ai-status-disconnected';
            const statusText = statusElement.querySelector('span');
            if (statusText) statusText.textContent = 'Error';
        }
    }
}

// Función para inicializar todo
function initializeAIChat() {
    try {
        // Verificar que todos los elementos existan
        if (!document.getElementById('aiMessages') || !document.getElementById('aiInput')) {
            console.log('Elementos del chat no encontrados, reintentando...');
            setTimeout(initializeAIChat, 100);
            return;
        }
        
        // Inicializar chat
        if (!window.aiChat) {
            window.aiChat = new AIChat();
            console.log('Chat AI inicializado correctamente');
        }
        
        // Verificar estado de la IA
        checkAIStatus();
        
        // Verificar cada 30 segundos
        if (!window.aiStatusInterval) {
            window.aiStatusInterval = setInterval(checkAIStatus, 30000);
        }
        
        // Inicializar predicciones si existe el manager
        if (window.PredictionsManager && !window.predictionsManager) {
            window.predictionsManager = new PredictionsManager();
            console.log('Predictions Manager inicializado');
        }
        
    } catch (error) {
        console.error('Error inicializando AI Chat:', error);
        setTimeout(initializeAIChat, 500);
    }
}

// Múltiples puntos de inicialización para asegurar que funcione
document.addEventListener('DOMContentLoaded', initializeAIChat);
window.addEventListener('load', initializeAIChat);

// Fallback si los eventos no funcionan
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAIChat);
} else {
    initializeAIChat();
}
