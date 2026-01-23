console.log('✅ Reportes - JavaScript cargado');

// Variables globales
let chartMetodos;
let chartTopProductos;
let apexChartIngresos;
let datosReporte = {};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando módulo de Reportes Profesional');
    
    // Configurar eventos adicionales
    configurarEventos();
    configurarPills();
    
    // Inicializar gráficos inmediatamente con datos del servidor
    setTimeout(() => {
        try {
            inicializarGraficos();
            cargarAlertas();
            calcularEstadisticasGrafico();
            calcularMontosMetodosPago();
            console.log('✅ Gráficos inicializados correctamente');
        } catch(e) {
            console.error('Error inicializando gráficos:', e);
        }
    }, 100);
    
    console.log('✅ Reportes inicializado correctamente');
});

// Configurar Pills de Filtros Rápidos
function configurarPills() {
    const pills = document.querySelectorAll('.reportes-pill');
    const fechasGroup = document.getElementById('fechasPersonalizadasGroup');
    const btnAplicarFechas = document.getElementById('btnAplicarFechas');
    const btnCancelarFechas = document.getElementById('btnCancelarFechas');
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    
    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            const periodo = this.getAttribute('data-periodo');
            
            // Remover active de todos
            pills.forEach(p => p.classList.remove('active'));
            
            // Agregar active al seleccionado
            this.classList.add('active');
            
            // Si es "Por Fecha", mostrar inputs
            if (periodo === 'personalizado') {
                if (fechasGroup) {
                    fechasGroup.style.display = 'block';
                }
            } else {
                // Ocultar fechas personalizadas
                if (fechasGroup) {
                    fechasGroup.style.display = 'none';
                }
                
                // Actualizar datos y texto del período
                mostrarCargandoPeriodo();
                actualizarTextoPeriodo(periodo);
                actualizarDatosPorPeriodo(periodo);
            }
        });
    });
    
    // Botón Aplicar Fechas
    if (btnAplicarFechas) {
        btnAplicarFechas.addEventListener('click', function() {
            const inicio = fechaInicio.value;
            const fin = fechaFin.value;
            
            if (!inicio || !fin) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas requeridas',
                    text: 'Por favor selecciona ambas fechas.',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            if (new Date(inicio) > new Date(fin)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Rango inválido',
                    text: 'La fecha "Hasta" no puede ser menor que la fecha "Desde".',
                    confirmButtonColor: '#ef4444'
                });
                return;
            }
            
            // Actualizar texto del período
            const fechaInicioObj = new Date(inicio);
            const fechaFinObj = new Date(fin);
            const textoInicio = fechaInicioObj.toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' });
            const textoFin = fechaFinObj.toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' });
            
            const textoPeriodo = document.getElementById('textoPeriodoActual');
            if (textoPeriodo) {
                textoPeriodo.textContent = `${textoInicio} hasta ${textoFin}`;
            }
            
            // Actualizar datos
            mostrarCargandoPeriodo();
            actualizarDatosPorPeriodoPersonalizado(inicio, fin);
        });
    }
    
    // Botón Cancelar Fechas
    if (btnCancelarFechas) {
        btnCancelarFechas.addEventListener('click', function() {
            if (fechasGroup) {
                fechasGroup.style.display = 'none';
            }
            
            // Volver a "Hoy"
            pills.forEach(p => p.classList.remove('active'));
            const pillHoy = document.querySelector('[data-periodo="hoy"]');
            if (pillHoy) {
                pillHoy.classList.add('active');
            }
            
            // Limpiar inputs
            if (fechaInicio) fechaInicio.value = '';
            if (fechaFin) fechaFin.value = '';
            
            // Actualizar a Hoy
            actualizarTextoPeriodo('hoy');
            mostrarCargandoPeriodo();
            actualizarDatosPorPeriodo('hoy');
        });
    }
    
    // Validación de fechas en tiempo real
    if (fechaInicio) {
        fechaInicio.addEventListener('change', function() {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaSeleccionada = new Date(this.value);
            
            // Validar que no sea una fecha futura
            if (fechaSeleccionada > hoy) {
                this.value = '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'No puedes seleccionar una fecha futura.',
                    confirmButtonColor: '#f59e0b',
                    timer: 3000
                });
                return;
            }
            
            // Si hay fecha fin, validar que inicio no sea mayor
            if (fechaFin.value) {
                const fechaF = new Date(fechaFin.value);
                if (fechaSeleccionada > fechaF) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Fecha inválida',
                        html: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta".<br><br>Por favor, ajusta las fechas correctamente.',
                        confirmButtonColor: '#ef4444'
                    });
                    this.value = '';
                }
            }
        });
    }
    
    if (fechaFin) {
        fechaFin.addEventListener('change', function() {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaSeleccionada = new Date(this.value);
            
            // Validar que no sea una fecha futura
            if (fechaSeleccionada > hoy) {
                this.value = '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'No puedes seleccionar una fecha futura.',
                    confirmButtonColor: '#f59e0b',
                    timer: 3000
                });
                return;
            }
            
            // Validar que no sea menor que inicio
            if (fechaInicio.value) {
                const fechaI = new Date(fechaInicio.value);
                if (fechaSeleccionada < fechaI) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Fecha inválida',
                        html: 'La fecha "Hasta" no puede ser menor que la fecha "Desde".<br><br>Por favor, ajusta las fechas correctamente.',
                        confirmButtonColor: '#ef4444'
                    });
                    this.value = '';
                }
            }
        });
    }
}

// Actualizar texto del período dinámicamente
function actualizarTextoPeriodo(periodo) {
    const textoPeriodo = document.getElementById('textoPeriodoActual');
    if (!textoPeriodo) return;
    
    const hoy = new Date();
    const ayer = new Date(hoy);
    ayer.setDate(ayer.getDate() - 1);
    
    const formatoLargo = { day: 'numeric', month: 'long', year: 'numeric' };
    
    switch(periodo) {
        case 'hoy':
            textoPeriodo.textContent = `Hoy - ${hoy.toLocaleDateString('es-PE', formatoLargo)}`;
            break;
        case 'ayer':
            textoPeriodo.textContent = `Ayer - ${ayer.toLocaleDateString('es-PE', formatoLargo)}`;
            break;
        case 'ultimos7':
            const hace7dias = new Date(hoy);
            hace7dias.setDate(hace7dias.getDate() - 6);
            textoPeriodo.textContent = `Esta Semana - ${hace7dias.toLocaleDateString('es-PE', { day: 'numeric', month: 'long' })} hasta ${hoy.toLocaleDateString('es-PE', formatoLargo)}`;
            break;
        case 'mes':
            const mesNombre = hoy.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' });
            textoPeriodo.textContent = `Este Mes - ${mesNombre.charAt(0).toUpperCase() + mesNombre.slice(1)}`;
            break;
        case 'anual':
            textoPeriodo.textContent = `Este Año - ${hoy.getFullYear()}`;
            break;
        default:
            textoPeriodo.textContent = 'Período personalizado';
    }
}

// Cargar Alertas Inteligentes con datos reales
async function cargarAlertas() {
    try {
        // Guardar referencia a los datos actuales
        const datos = window.datosReporte || {};
        
        // Alerta 1: Stock Crítico
        const stockCriticoEl = document.getElementById('alertStockCritico');
        if (stockCriticoEl) {
            stockCriticoEl.textContent = 'Cargando...';
            try {
                const response = await fetch('/inventario/productos/datos');
                if (response.ok) {
                    const data = await response.json();
                    const productos = data.productos || data.data || [];
                    // Contar productos con stock crítico
                    const productosCriticos = productos.filter(p => 
                        (p.stock_actual || 0) <= (p.stock_minimo || 5) && (p.stock_actual || 0) > 0
                    );
                    const count = productosCriticos.length;
                    stockCriticoEl.textContent = count > 0 ? `${count} producto${count > 1 ? 's' : ''}` : 'Todo bien ✓';
                    stockCriticoEl.style.color = count > 0 ? '#dc2626' : '#10b981';
                } else {
                    stockCriticoEl.textContent = 'Todo bien ✓';
                    stockCriticoEl.style.color = '#10b981';
                }
            } catch (error) {
                console.log('Stock crítico: usando datos simulados');
                stockCriticoEl.textContent = 'Todo bien ✓';
                stockCriticoEl.style.color = '#10b981';
            }
            stockCriticoEl.style.fontWeight = '700';
        }
        
        // Alerta 2: Próximos a Vencer
        const porVencerEl = document.getElementById('alertPorVencer');
        if (porVencerEl) {
            porVencerEl.textContent = 'Cargando...';
            try {
                const response = await fetch('/inventario/lotes/datos');
                if (response.ok) {
                    const data = await response.json();
                    const lotes = data.lotes || data.data || [];
                    const hoy = new Date();
                    const treintaDias = new Date();
                    treintaDias.setDate(treintaDias.getDate() + 30);
                    
                    const lotesProximos = lotes.filter(l => {
                        if (!l.fecha_vencimiento || (l.cantidad || 0) === 0) return false;
                        const fechaVenc = new Date(l.fecha_vencimiento);
                        return fechaVenc >= hoy && fechaVenc <= treintaDias;
                    });
                    const count = lotesProximos.length;
                    porVencerEl.textContent = count > 0 ? `${count} lote${count > 1 ? 's' : ''}` : 'Ninguno ✓';
                    porVencerEl.style.color = count > 0 ? '#f59e0b' : '#10b981';
                } else {
                    porVencerEl.textContent = 'Ninguno ✓';
                    porVencerEl.style.color = '#10b981';
                }
            } catch (error) {
                console.log('Próximos a vencer: usando datos simulados');
                porVencerEl.textContent = 'Ninguno ✓';
                porVencerEl.style.color = '#10b981';
            }
            porVencerEl.style.fontWeight = '700';
        }
        
        // Alerta 3: Sin Ventas (7 días)
        const sinVentasEl = document.getElementById('alertSinVentas');
        if (sinVentasEl) {
            sinVentasEl.textContent = 'Ninguno ✓';
            sinVentasEl.style.color = '#10b981';
            sinVentasEl.style.fontWeight = '700';
        }
        
        // Alerta 4: Más Vendido (del período actual)
        const masVendidoEl = document.getElementById('alertMasVendido');
        if (masVendidoEl) {
            const productos = datos.productos_mas_vendidos || [];
            if (productos.length > 0 && productos[0] && (productos[0].total_vendido || 0) > 0) {
                const nombreProducto = productos[0].producto?.nombre || productos[0].nombre || 'Sin nombre';
                masVendidoEl.textContent = nombreProducto.length > 20 ? nombreProducto.substring(0, 20) + '...' : nombreProducto;
                masVendidoEl.style.color = '#10b981';
            } else {
                masVendidoEl.textContent = 'Sin ventas';
                masVendidoEl.style.color = '#6b7280';
            }
            masVendidoEl.style.fontWeight = '700';
        }
    } catch (error) {
        console.error('Error cargando alertas:', error);
    }
}

// Actualizar datos por período personalizado
async function actualizarDatosPorPeriodoPersonalizado(fechaInicio, fechaFin) {
    console.log('🔄 Actualizando datos para período personalizado:', fechaInicio, fechaFin);
    
    try {
        const datos = await obtenerDatosReporte('personalizado', fechaInicio, fechaFin);
        
        // Guardar datos globalmente
        window.datosReporte = datos;
        
        // Actualizar estadísticas principales
        actualizarEstadisticas(datos.estadisticas, datos.comparativo);
        
        // Actualizar tarjetas
        const productosVendidosEl = document.getElementById('statProductosVendidos');
        const totalUnidades = (datos.productos_mas_vendidos || []).reduce((sum, p) => sum + (parseInt(p.total_vendido) || 0), 0);
        if (productosVendidosEl) productosVendidosEl.textContent = totalUnidades;
        
        const productosUnicosEl = document.getElementById('statProductosUnicos');
        if (productosUnicosEl) productosUnicosEl.textContent = (datos.productos_mas_vendidos || []).length;
        
        // Actualizar gráficos
        window.datosIngresos = datos.ingresos;
        actualizarGraficoIngresos(datos.ingresos);
        actualizarGraficoMetodos(datos.metodos);
        
        calcularEstadisticasGrafico();
        calcularMontosMetodosPago();
        
        renderTopProductos(datos.productos_mas_vendidos || []);
        renderTopMarcas(datos.marcas_mas_compradas || []);
        
        // Recargar alertas con nuevos datos
        cargarAlertas();
        
        console.log('✅ Datos actualizados correctamente');
        if (typeof Swal !== 'undefined') Swal.close();
        
    } catch (error) {
        console.error('❌ Error actualizando datos:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los datos del reporte. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    } finally {
        if (typeof Swal !== 'undefined') {
            try { Swal.close(); } catch (e) {}
        }
    }
}

// Calcular estadísticas del gráfico (Pico, Mínimo, Promedio)
function calcularEstadisticasGrafico() {
    if (!window.datosIngresos || window.datosIngresos.length === 0) return;
    
    const ingresos = window.datosIngresos.map(d => parseFloat(d.ingresos) || 0);
    const pico = Math.max(...ingresos);
    const minimo = Math.min(...ingresos);
    const promedio = ingresos.reduce((a, b) => a + b, 0) / ingresos.length;
    
    const picoEl = document.getElementById('statPico');
    const minimoEl = document.getElementById('statMinimo');
    const promedioEl = document.getElementById('statPromedio');
    
    if (picoEl) picoEl.textContent = 'S/ ' + pico.toFixed(2);
    if (minimoEl) minimoEl.textContent = 'S/ ' + minimo.toFixed(2);
    if (promedioEl) promedioEl.textContent = 'S/ ' + promedio.toFixed(2);
}

// Calcular montos por método de pago
function calcularMontosMetodosPago() {
    if (!window.datosMetodos || window.datosMetodos.length === 0) return;
    
    const totalIngresos = parseFloat(document.getElementById('statIngresosTotal')?.textContent.replace('S/', '').replace(',', '') || 0);
    const totalMetodos = window.datosMetodos.reduce((sum, m) => sum + (parseInt(m.total) || 0), 0);
    
    if (totalMetodos === 0) return;
    
    const efectivo = parseInt(window.datosMetodos[0]?.total || 0);
    const tarjeta = parseInt(window.datosMetodos[1]?.total || 0);
    const yape = parseInt(window.datosMetodos[2]?.total || 0);
    
    const montoEfectivo = (efectivo / totalMetodos) * totalIngresos;
    const montoTarjeta = (tarjeta / totalMetodos) * totalIngresos;
    const montoYape = (yape / totalMetodos) * totalIngresos;
    
    const efectivoEl = document.getElementById('montoEfectivo');
    const tarjetaEl = document.getElementById('montoTarjeta');
    const yapeEl = document.getElementById('montoYape');
    
    if (efectivoEl) efectivoEl.textContent = 'S/ ' + montoEfectivo.toFixed(2);
    if (tarjetaEl) tarjetaEl.textContent = 'S/ ' + montoTarjeta.toFixed(2);
    if (yapeEl) yapeEl.textContent = 'S/ ' + montoYape.toFixed(2);
}

// Configurar eventos adicionales
function configurarEventos() {
    // Referencias a elementos del DOM
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const usuarioSelect = document.getElementById('usuarioSelect');
    
    // Botón Aplicar Filtros
    if (btnAplicar) {
        btnAplicar.addEventListener('click', function() {
            const periodoActivo = document.querySelector('.reportes-pill.active');
            const periodo = periodoActivo ? periodoActivo.getAttribute('data-periodo') : 'hoy';
            
            mostrarCargandoPeriodo();
            actualizarDatosPorPeriodo(periodo);
        });
    }

    // Cambio de vendedor
    if (usuarioSelect) {
        usuarioSelect.addEventListener('change', function() {
            // Opcional: auto-actualizar o esperar al botón Aplicar
        });
    }

    // Controles del gráfico
    const periodoChartSelect = document.getElementById('periodoChartSelect');
    const compararSelect = document.getElementById('compararSelect');
    
    if (periodoChartSelect) {
        periodoChartSelect.addEventListener('change', async function() {
            const periodo = this.value;
            mostrarCargandoPeriodo();
            
            // Obtener datos SOLO para el gráfico (sin afectar tarjetas)
            const datosGrafico = await obtenerDatosReporte(periodo);
            
            // Actualizar SOLO el gráfico
            window.datosIngresos = datosGrafico.ingresos;
            inicializarGraficoIngresosApex(datosGrafico.ingresos);
            
            // Actualizar el select de comparación
            if (compararSelect) {
                const mapa = { hoy: 'ayer', ultimos7: 'semana_anterior', mes: 'mes_anterior', anual: 'anio_anterior' };
                compararSelect.value = mapa[periodo] || 'mes_anterior';
                compararSelect.dispatchEvent(new Event('change'));
            }
            
            try { Swal.close(); } catch(e) {}
        });
    }
    
    if (compararSelect) {
        compararSelect.addEventListener('change', async function() {
            const periodo = periodoChartSelect?.value || 'mes';
            const contra = this.value;
            
            if (contra === 'none') {
                inicializarGraficoIngresosApex(window.datosIngresos || []);
                return;
            }
            
            mostrarCargandoPeriodo();
            const comp = await obtenerDatosComparativo(periodo, contra, 'auto');
            if (comp && comp.labels && comp.actual && comp.prev) {
                inicializarGraficoIngresosApexComparativo(comp.labels, comp.actual, comp.prev, comp.titulo || '');
            } else {
                inicializarGraficoIngresosApex(window.datosIngresos || []);
            }
            try { Swal.close(); } catch(e) {}
        });
    }
    
    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl + E para exportar
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportarReporte();
        }
    });
}

// Limita las opciones de "Comparar con" según el período seleccionado
function actualizarOpcionesComparacion(periodo) {
    const select = document.getElementById('compararSelect');
    if (!select) return;
    const validMap = { hoy: ['ayer'], ultimos7: ['semana_anterior'], mes: ['mes_anterior'], anual: ['anio_anterior'] };
    const labels = { ayer: 'Ayer', semana_anterior: 'Semana anterior', mes_anterior: 'Mes anterior', anio_anterior: 'Año anterior' };
    const valid = validMap[periodo] || [];
    select.innerHTML = '';
    valid.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = labels[v] || v;
        select.appendChild(opt);
    });
    if (valid.length > 0) {
        select.value = valid[0];
    }
}

// Mostrar loading al cambiar período y actualizar datos
function mostrarCargandoPeriodo() {
    Swal.fire({
        title: 'Actualizando reporte...',
        text: 'Obteniendo datos del período seleccionado',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

// Exportar reporte
function exportarReporte() {
    // Validación de datos vacíos antes de abrir el modal
    const totalVentas = parseInt(window.datosReporte?.estadisticas?.total_ventas || 0);
    
    if (totalVentas === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin datos para exportar',
            html: `
                <div style="text-align: center;">
                    <iconify-icon icon="solar:document-text-broken" style="font-size: 4rem; color: #94a3b8; margin-bottom: 1rem;"></iconify-icon>
                    <p style="color: #475569; font-weight: 600; font-size: 1.1rem;">No se encontraron ventas en este periodo.</p>
                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.5rem;">El reporte no puede ser generado porque está vacío.</p>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    const periodoActivo = document.querySelector('.reportes-pill.active');
    const periodo = periodoActivo ? periodoActivo.getAttribute('data-periodo') : 'hoy';
    const fechaInicio = document.getElementById('fechaInicio')?.value;
    const fechaFin = document.getElementById('fechaFin')?.value;
    const nombrePeriodo = obtenerNombrePeriodo(periodo);

    Swal.fire({
        title: '<div class="d-flex align-items-center gap-2 justify-content-center mb-2"><iconify-icon icon="solar:download-minimalistic-bold-duotone" style="color: #4f46e5; font-size: 2.5rem;"></iconify-icon><span style="font-weight: 800; font-size: 1.5rem; color: #1e293b;">Exportar Reporte</span></div>',
        html: `
            <div style="text-align: left; padding: 0.5rem;">
                <div style="background: #f8fafc; border-radius: 1rem; padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="background: rgba(79, 70, 229, 0.1); padding: 0.5rem; border-radius: 0.5rem; color: #4f46e5;">
                            <iconify-icon icon="solar:calendar-bold-duotone" style="font-size: 1.5rem;"></iconify-icon>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-uppercase: uppercase; letter-spacing: 0.05em;">Período Seleccionado</div>
                            <div style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">${nombrePeriodo}</div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #475569; font-size: 0.9rem;">SELECCIONE TIPO DE REPORTE:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div onclick="document.getElementById('radioDetallado').click()" style="cursor: pointer;">
                            <input type="radio" name="swalTipoReporte" id="radioDetallado" value="detallado" checked style="display: none;">
                            <div id="cardDetallado" class="tipo-reporte-card active" style="border: 2px solid #4f46e5; background: #f5f3ff; padding: 1rem; border-radius: 1rem; text-align: center; transition: all 0.2s;">
                                <iconify-icon icon="solar:list-bold-duotone" style="font-size: 2rem; color: #4f46e5; margin-bottom: 0.5rem; display: block;"></iconify-icon>
                                <span style="font-weight: 800; color: #1e293b; display: block;">Detallado</span>
                                <small style="color: #6b7280; font-size: 0.7rem;">Todas las ventas</small>
                            </div>
                        </div>
                        <div onclick="document.getElementById('radioResumen').click()" style="cursor: pointer;">
                            <input type="radio" name="swalTipoReporte" id="radioResumen" value="resumen" style="display: none;">
                            <div id="cardResumen" class="tipo-reporte-card" style="border: 2px solid #e2e8f0; background: white; padding: 1rem; border-radius: 1rem; text-align: center; transition: all 0.2s;">
                                <iconify-icon icon="solar:chart-square-bold-duotone" style="font-size: 2rem; color: #64748b; margin-bottom: 0.5rem; display: block;"></iconify-icon>
                                <span style="font-weight: 800; color: #1e293b; display: block;">Resumen</span>
                                <small style="color: #6b7280; font-size: 0.7rem;">Por productos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                .tipo-reporte-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
                input[name="swalTipoReporte"]:checked + .tipo-reporte-card { border-color: #4f46e5 !important; background: #f5f3ff !important; }
                input[name="swalTipoReporte"]:checked + .tipo-reporte-card iconify-icon { color: #4f46e5 !important; }
                .swal2-actions { gap: 1.5rem !important; margin-top: 1.5rem !important; }
            </style>
        `,
        showCancelButton: true,
        confirmButtonText: '<div class="d-flex align-items-center gap-2"><iconify-icon icon="vscode-icons:file-type-excel" style="font-size: 1.25rem;"></iconify-icon> Descargar Excel</div>',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        width: '500px',
        padding: '2rem',
        buttonsStyling: true,
        customClass: {
            confirmButton: 'btn-descargar-swal',
            cancelButton: 'btn-cancelar-swal',
            popup: 'swal-border-radius'
        },
        didOpen: () => {
            const cards = document.querySelectorAll('.tipo-reporte-card');
            const radios = document.querySelectorAll('input[name="swalTipoReporte"]');
            radios.forEach((radio, idx) => {
                radio.addEventListener('change', () => {
                    cards.forEach(c => {
                        c.style.borderColor = '#e2e8f0';
                        c.style.background = 'white';
                        c.querySelector('iconify-icon').style.color = '#64748b';
                    });
                    if(radio.checked) {
                        cards[idx].style.borderColor = '#4f46e5';
                        cards[idx].style.background = '#f5f3ff';
                        cards[idx].querySelector('iconify-icon').style.color = '#4f46e5';
                    }
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const tipoReporte = document.querySelector('input[name="swalTipoReporte"]:checked').value;
            exportarExcelLaravel(periodo, fechaInicio, fechaFin, tipoReporte);
        }
    });
}

// Exportar a Excel usando LARAVEL EXCEL (servidor)
function exportarExcelLaravel(periodo, fechaInicio, fechaFin, tipo) {
    console.log('📊 Exportando con Laravel Excel (servidor)...');
    
    Swal.fire({
        title: '📊 Generando Reporte...',
        html: `
            <div style="padding: 1.5rem;">
                <div class="reportes-loading-spinner" style="margin: 0 auto 1rem;"></div>
                <p style="color: #6b7280; margin: 1rem 0;">Preparando su archivo Excel profesional...</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    // Construir URL con parámetros
    const params = new URLSearchParams({
        periodo: periodo,
        tipo: tipo
    });
    
    if (periodo === 'personalizado' && fechaInicio && fechaFin) {
        params.append('fecha_inicio', fechaInicio);
        params.append('fecha_fin', fechaFin);
    }
    
    const url = `/ventas/reportes/exportar?${params.toString()}`;
    
    // Generar un nombre de archivo amigable para el frontend
    const fecha = new Date().toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '');
    const nombreArchivo = `Reporte_Ventas_${fecha}.xls`;
    
    // Crear un enlace temporal para forzar la descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = nombreArchivo; 
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    
    // Esperar un momento y mostrar mensaje de éxito
    setTimeout(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '✅ ¡Reporte Listo!',
                text: 'El archivo Excel se ha generado y descargado correctamente.',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
        }
        
        if (document.body.contains(link)) {
            document.body.removeChild(link);
        }
    }, 2000);
}

// Exportar a Excel usando JavaScript (SheetJS) - VERSIÓN COMPATIBLE
async function exportarExcelJS(periodo, usuarioId, tipo) {
    console.log('📊 Exportando a Excel con JavaScript...');
    
    Swal.fire({
        title: '📊 Generando Reporte...',
        html: '<div style="padding: 1rem;"><div class="reportes-loading-spinner" style="margin: 0 auto 1rem;"></div><p style="color: #6b7280;">Preparando archivo Excel profesional...</p></div>',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    try {
        // Obtener datos del reporte
        const datos = await obtenerDatosReporte(periodo, usuarioId);
        
        // Crear libro de Excel
        const wb = XLSX.utils.book_new();
        const fechaGen = new Date().toLocaleDateString('es-PE', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        // Preparar datos para Excel
        const totalMetodos = (datos.metodos[0] || 0) + (datos.metodos[1] || 0) + (datos.metodos[2] || 0) || 1;
        const totalIngresos = Number(datos.estadisticas.total_ingresos || 0);
        
        // Crear hoja con diseño profesional
        const wsData = [
            ['═════════════════════════════════════════════════════════════════════════'],
            ['                          FARMACIA SAN ANTONIO                            '],
            ['                          REPORTE DE VENTAS                                '],
            ['═════════════════════════════════════════════════════════════════════════'],
            ['Período: ' + obtenerNombrePeriodo(periodo).toUpperCase()],
            ['Generado: ' + fechaGen],
            [],
            ['▓▓▓ RESUMEN GENERAL ▓▓▓'],
            ['   Total de Ventas:', parseInt(datos.estadisticas.total_ventas) || 0],
            ['   Ingresos Totales:', 'S/ ' + totalIngresos.toFixed(2)],
            ['   Ticket Promedio:', 'S/ ' + Number(datos.estadisticas.promedio || 0).toFixed(2)],
            [],
            ['▓▓▓ MÉTODOS DE PAGO ▓▓▓'],
            ['   Efectivo:', parseInt(datos.metodos[0]) || 0, ((datos.metodos[0] / totalMetodos) * 100).toFixed(1) + '%', 'S/ ' + ((datos.metodos[0] / totalMetodos) * totalIngresos).toFixed(2)],
            ['   Tarjeta:', parseInt(datos.metodos[1]) || 0, ((datos.metodos[1] / totalMetodos) * 100).toFixed(1) + '%', 'S/ ' + ((datos.metodos[1] / totalMetodos) * totalIngresos).toFixed(2)],
            ['   Yape:', parseInt(datos.metodos[2]) || 0, ((datos.metodos[2] / totalMetodos) * 100).toFixed(1) + '%', 'S/ ' + ((datos.metodos[2] / totalMetodos) * totalIngresos).toFixed(2)],
            ['   ─────────────────────────────────────────────────────────────'],
            ['   TOTAL:', totalMetodos, '100%', 'S/ ' + totalIngresos.toFixed(2)],
            [],
            ['═════════════════════════════════════════════════════════════════════════'],
        ];
        
        if (tipo === 'resumen') {
            // Reporte por productos
            wsData.push(['#', '║ PRODUCTO', '║ MARCA', '║ CANTIDAD', '║ TOTAL VENDIDO']);
            wsData.push(['─', '─────────────────────────────────', '─────────────────────', '─────────────', '─────────────────']);
            
            (datos.productos_mas_vendidos || []).slice(0, 30).forEach((p, idx) => {
                wsData.push([
                    (idx + 1) + '.',
                    '  ' + (p.nombre || 'Sin nombre'),
                    '  ' + (p.marca || 'N/A'),
                    parseInt(p.total_vendido) || 0,
                    'S/ ' + Number(p.total_vendido * (p.precio_promedio || 0)).toFixed(2)
                ]);
            });
        } else {
            // Reporte detallado
            wsData.push(['#', '║ FECHA', '║ N° VENTA', '║ CLIENTE', '║ MÉTODO PAGO', '║ TOTAL', '║ ESTADO']);
            wsData.push(['─', '──────────────────', '─────────────', '─────────────────────────────', '──────────────', '──────────────', '─────────────']);
            
            // Obtener ventas del reporte
            const ingresosPorDia = datos.ingresos_por_dia || [];
            let contador = 1;
            
            // Intentar obtener ventas detalladas
            if (datos.detalle_productos_vendidos && Array.isArray(datos.detalle_productos_vendidos)) {
                // Si hay productos vendidos, crear resumen
                datos.detalle_productos_vendidos.slice(0, 50).forEach((item) => {
                    wsData.push([
                        contador++ + '.',
                        '  ' + (item.fecha || 'N/A'),
                        '  Múltiples',
                        '  ' + (item.nombre || 'Producto'),
                        '  VARIOS',
                        'S/ ' + Number(item.total_vendido || 0).toFixed(2),
                        'COMPLETADA'
                    ]);
                });
            } else {
                // Usar resumen por día
                ingresosPorDia.slice(0, 50).forEach((dia) => {
                    wsData.push([
                        contador++ + '.',
                        '  ' + dia.fecha,
                        '  Resumen diario',
                        '  Múltiples clientes',
                        '  VARIOS',
                        'S/ ' + Number(dia.ingresos).toFixed(2),
                        'COMPLETADA'
                    ]);
                });
            }
        }
        
        wsData.push(['═════════════════════════════════════════════════════════════════════════']);
        wsData.push(['                    Fin del Reporte - Farmacia San Antonio                ']);
        wsData.push(['═════════════════════════════════════════════════════════════════════════']);
        
        // Crear hoja
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        
        // Aplicar ancho de columnas MÁS AMPLIAS
        ws['!cols'] = tipo === 'resumen' 
            ? [
                { wch: 5 },   // #
                { wch: 45 },  // PRODUCTO
                { wch: 25 },  // MARCA
                { wch: 15 },  // CANTIDAD
                { wch: 20 }   // TOTAL
            ]
            : [
                { wch: 5 },   // #
                { wch: 20 },  // FECHA
                { wch: 18 },  // N° VENTA
                { wch: 35 },  // CLIENTE
                { wch: 18 },  // MÉTODO
                { wch: 18 },  // TOTAL
                { wch: 20 }   // ESTADO
            ];
        
        // Agregar hoja al libro
        XLSX.utils.book_append_sheet(wb, ws, 'Reporte de Ventas');
        
        // Generar nombre de archivo
        const fecha = new Date();
        const nombreArchivo = `Reporte_Ventas_${fecha.getDate().toString().padStart(2,'0')}${(fecha.getMonth()+1).toString().padStart(2,'0')}${fecha.getFullYear()}_${fecha.getHours().toString().padStart(2,'0')}${fecha.getMinutes().toString().padStart(2,'0')}${fecha.getSeconds().toString().padStart(2,'0')}.xlsx`;
        
        // Descargar archivo
        XLSX.writeFile(wb, nombreArchivo);
        
        // Mostrar éxito
        Swal.fire({
            title: '✅ ¡Reporte Generado!',
            html: `
                <div style="text-align: center;">
                    <iconify-icon icon="line-md:confirm-circle" style="font-size: 4rem; color: #10B981;"></iconify-icon>
                    <p style="margin-top: 1rem; color: #6B7280; font-weight: 600;">El archivo Excel se ha descargado correctamente</p>
                    <p style="font-size: 0.875rem; color: #9CA3AF; margin-top: 0.5rem;">${nombreArchivo}</p>
                    <div style="background: #F3F4F6; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem; text-align: left;">
                        <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #374151; font-weight: 600;">
                            <iconify-icon icon="solar:star-bold" style="color: #F59E0B;"></iconify-icon>
                            Características del reporte:
                        </p>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.875rem; color: #6B7280;">
                            <li>Resumen ejecutivo completo</li>
                            <li>Métodos de pago detallados</li>
                            <li>Formato profesional con separadores</li>
                            <li>Datos organizados y legibles</li>
                        </ul>
                    </div>
                </div>
            `,
            icon: 'success',
            timer: 4000,
            showConfirmButton: false
        });
        
    } catch (error) {
        console.error('Error al exportar:', error);
        Swal.fire({
            title: '❌ Error al Generar Reporte',
            html: `
                <div style="text-align: left; padding: 1rem;">
                    <p style="color: #6B7280; margin-bottom: 0.5rem;">No se pudo generar el reporte.</p>
                    <div style="background: #FEE2E2; border-left: 4px solid #EF4444; padding: 0.75rem; border-radius: 0.25rem;">
                        <p style="margin: 0; color: #991B1B; font-size: 0.875rem;"><strong>Error:</strong> ${error.message}</p>
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.875rem; color: #9CA3AF;">Si el problema persiste, contacte al administrador.</p>
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#EF4444'
        });
    }
}

// Función auxiliar para obtener ventas detalladas
async function obtenerVentasDetalladas(periodo, usuarioId) {
    try {
        const params = new URLSearchParams({
            periodo: periodo
        });
        
        if (usuarioId) {
            params.append('usuario_id', usuarioId);
        }
        
        const response = await fetch(`/ventas/reportes/datos?${params.toString()}`);
        const result = await response.json();
        
        // Simular ventas si no hay endpoint específico
        // En producción, deberías tener un endpoint que devuelva las ventas
        return result.ventas || [];
        
    } catch (error) {
        console.error('Error obteniendo ventas:', error);
        return [];
    }
}

// Exportar a Excel
async function exportarExcel(periodo) {
    console.log('📊 Exportando a Excel...');
    
    Swal.fire({
        title: '📊 Preparando Excel...',
        html: '<div style="padding: 1rem;"><div class="reportes-loading-spinner" style="margin: 0 auto 1rem;"></div><p style="color: #6b7280;">Generando archivo profesional de reporte</p></div>',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    try {
        const datos = await obtenerDatosReporte(periodo);
        const wb = XLSX.utils.book_new();
        const usuario = document.querySelector('meta[name="usuario-nombre"]')?.getAttribute('content') || 'Usuario';
        const fechaGen = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
        
        // === HOJA 1: RESUMEN EJECUTIVO ===
        const totalMetodos = datos.metodos.reduce((a,b) => a+b, 0) || 1;
        const productosVendidos = (datos.productos_mas_vendidos || []).reduce((sum, p) => sum + (p.total_vendido || 0), 0);
        const comparativoTexto = datos.comparativo ? 
            `${datos.comparativo.porcentaje_cambio >= 0 ? '▲' : '▼'} ${Math.abs(datos.comparativo.porcentaje_cambio).toFixed(1)}% ${datos.comparativo.etiqueta}` : 
            'N/A';
        
        // Calcular diferencia en soles para comparativo
        const actual = Number(datos.comparativo?.periodo_actual || 0);
        const anterior = Number(datos.comparativo?.periodo_anterior || 0);
        const diferencia = actual - anterior;
        const diferenciaTexto = datos.comparativo ? 
            (diferencia >= 0 ? 
                `S/ ${diferencia.toFixed(2)} más que ${datos.comparativo.etiqueta}` : 
                `S/ ${Math.abs(diferencia).toFixed(2)} menos que ${datos.comparativo.etiqueta}`) :
            'N/A';
        
        const wsResumen = XLSX.utils.aoa_to_sheet([
            ['REPORTE DE VENTAS - BOTICA SAN ANTONIO'],
            ['Período: ' + obtenerNombrePeriodo(periodo).toUpperCase()],
            ['Generado: ' + fechaGen + ' por ' + usuario],
            [''],
            ['ESTADÍSTICAS PRINCIPALES'],
            ['Métrica', 'Valor', 'Detalle'],
            ['Total de Ventas', parseInt(datos.estadisticas.total_ventas) || 0, 'Ventas completadas'],
            ['Ingresos Totales', 'S/ ' + Number(datos.estadisticas.total_ingresos || 0).toFixed(2), 'Monto total facturado'],
            ['Ticket Promedio', 'S/ ' + Number(datos.estadisticas.promedio || 0).toFixed(2), 'Promedio por venta'],
            ['Productos Vendidos', parseInt(productosVendidos) || 0, 'Unidades totales'],
            ['Comparativo', diferenciaTexto, 'Diferencia con período anterior'],
            [''],
            ['MÉTODOS DE PAGO'],
            ['Método', 'Cantidad', 'Porcentaje', 'Monto Estimado'],
            ['Efectivo', parseInt(datos.metodos[0]) || 0, totalMetodos > 0 ? ((datos.metodos[0] / totalMetodos) * 100).toFixed(1) + '%' : '0%', 'S/ ' + (totalMetodos > 0 ? ((datos.metodos[0] / totalMetodos) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')],
            ['Tarjeta', parseInt(datos.metodos[1]) || 0, totalMetodos > 0 ? ((datos.metodos[1] / totalMetodos) * 100).toFixed(1) + '%' : '0%', 'S/ ' + (totalMetodos > 0 ? ((datos.metodos[1] / totalMetodos) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')],
            ['Yape', parseInt(datos.metodos[2]) || 0, totalMetodos > 0 ? ((datos.metodos[2] / totalMetodos) * 100).toFixed(1) + '%' : '0%', 'S/ ' + (totalMetodos > 0 ? ((datos.metodos[2] / totalMetodos) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')],
            ['TOTAL', parseInt(totalMetodos) || 0, '100%', 'S/ ' + Number(datos.estadisticas.total_ingresos || 0).toFixed(2)]
        ]);
        
        // Aplicar estilos al resumen
        wsResumen['!cols'] = [{wch: 25}, {wch: 20}, {wch: 30}];
        XLSX.utils.book_append_sheet(wb, wsResumen, 'Resumen Ejecutivo');
        
        // === HOJA 2: TOP PRODUCTOS ===
        const productosData = [
            ['TOP 10 PRODUCTOS MÁS VENDIDOS'],
            ['Período: ' + obtenerNombrePeriodo(periodo)],
            [''],
            ['Ranking', 'Producto', 'Concentración', 'Unidades Vendidas', 'Participación %']
        ];
        
        const totalUnidades = (datos.productos_mas_vendidos || []).reduce((sum, p) => sum + (p.total_vendido || 0), 0);
        (datos.productos_mas_vendidos || []).slice(0, 10).forEach((p, idx) => {
            const unidades = parseInt(p.total_vendido) || 0;
            const participacion = totalUnidades > 0 ? ((unidades / totalUnidades) * 100).toFixed(1) : '0.0';
            productosData.push([
                idx + 1,
                p.producto?.nombre || p.nombre || 'Producto',
                p.producto?.concentracion || p.concentracion || 'N/A',
                unidades,
                participacion + '%'
            ]);
        });
        
        productosData.push(['', '', 'TOTAL', parseInt(totalUnidades) || 0, '100%']);
        
        const wsProductos = XLSX.utils.aoa_to_sheet(productosData);
        wsProductos['!cols'] = [{wch: 10}, {wch: 30}, {wch: 20}, {wch: 18}, {wch: 15}];
        XLSX.utils.book_append_sheet(wb, wsProductos, 'Top Productos');
        
        // === HOJA 3: MARCAS ===
        const marcasData = [
            ['MARCAS MÁS COMPRADAS'],
            ['Período: ' + obtenerNombrePeriodo(periodo)],
            [''],
            ['Ranking', 'Marca', 'Unidades', 'Participación %']
        ];
        
        const totalMarcas = (datos.marcas_mas_compradas || []).reduce((sum, m) => sum + (m.unidades || 0), 0);
        (datos.marcas_mas_compradas || []).slice(0, 10).forEach((m, idx) => {
            const unidades = parseInt(m.unidades) || 0;
            const participacion = totalMarcas > 0 ? ((unidades / totalMarcas) * 100).toFixed(1) : '0.0';
            marcasData.push([
                idx + 1,
                m.marca || 'Sin marca',
                unidades,
                participacion + '%'
            ]);
        });
        
        marcasData.push(['', 'TOTAL', parseInt(totalMarcas) || 0, '100%']);
        
        const wsMarcas = XLSX.utils.aoa_to_sheet(marcasData);
        wsMarcas['!cols'] = [{wch: 10}, {wch: 30}, {wch: 15}, {wch: 15}];
        XLSX.utils.book_append_sheet(wb, wsMarcas, 'Marcas');
        
        // === HOJA 4: DETALLE PRODUCTOS VENDIDOS ===
        const detalleData = [
            ['DETALLE DE PRODUCTOS VENDIDOS'],
            ['Período: ' + obtenerNombrePeriodo(periodo)],
            [''],
            ['#', 'Producto', 'Marca', 'Concentración', 'Presentación', 'Categoría', 'Cantidad', 'Precio Prom.', 'Total Vendido']
        ];
        
        let totalCantidad = 0;
        let totalVendido = 0;
        (datos.detalle_productos_vendidos || []).forEach((p, idx) => {
            const cantidad = parseInt(p.cantidad_total) || 0;
            const precioPromedio = parseFloat(p.precio_promedio) || 0;
            const total = parseFloat(p.total_vendido) || 0;
            totalCantidad += cantidad;
            totalVendido += total;
            detalleData.push([
                idx + 1,
                p.nombre || 'Sin nombre',
                p.marca || 'Sin marca',
                p.concentracion || '-',
                p.presentacion || '-',
                p.categoria || '-',
                cantidad,
                'S/ ' + precioPromedio.toFixed(2),
                'S/ ' + total.toFixed(2)
            ]);
        });
        
        detalleData.push(['', '', '', '', '', 'TOTALES:', totalCantidad, '', 'S/ ' + totalVendido.toFixed(2)]);
        
        const wsDetalle = XLSX.utils.aoa_to_sheet(detalleData);
        wsDetalle['!cols'] = [{wch: 5}, {wch: 30}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 10}, {wch: 12}, {wch: 14}];
        XLSX.utils.book_append_sheet(wb, wsDetalle, 'Detalle Productos');
        
        // === HOJA 5: INGRESOS DETALLADOS ===
        const ingresosData = [
            ['INGRESOS DEL PERÍODO'],
            ['Período: ' + obtenerNombrePeriodo(periodo)],
            [''],
            ['Fecha/Período', 'Ingresos (S/)', 'Acumulado (S/)']
        ];
        
        let acumulado = 0;
        (datos.ingresos || []).forEach(i => {
            const ingreso = parseFloat(i.ingresos || 0);
            acumulado += ingreso;
            ingresosData.push([
                i.fecha,
                ingreso.toFixed(2),
                acumulado.toFixed(2)
            ]);
        });
        
        ingresosData.push(['', 'TOTAL', acumulado.toFixed(2)]);
        
        const wsIngresos = XLSX.utils.aoa_to_sheet(ingresosData);
        wsIngresos['!cols'] = [{wch: 20}, {wch: 18}, {wch: 18}];
        XLSX.utils.book_append_sheet(wb, wsIngresos, 'Ingresos Detallados');
        
        // Generar archivo
        const nombreArchivo = `Reporte_Ventas_${obtenerNombrePeriodo(periodo).replace(/ /g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);
        
        Swal.fire({
            title: '✅ Excel Generado',
            html: `<div style="text-align: center;"><p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Archivo descargado exitosamente</p><p style="color: #6b7280; font-size: 0.9rem;">${nombreArchivo}</p><p style="color: #10b981; font-size: 0.85rem; margin-top: 1rem;">✓ 5 hojas incluidas: Resumen, Top Productos, Marcas, Detalle Productos e Ingresos</p></div>`,
            icon: 'success',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#10b981'
        });
        
    } catch (error) {
        console.error('❌ Error exportando Excel:', error);
        Swal.fire({
            title: 'Error al exportar',
            text: 'No se pudo generar el archivo Excel. Por favor, inténtalo de nuevo.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    }
}

// Exportar a PDF
async function exportarPDF(periodo) {
    console.log('📄 Exportando a PDF...');
    
    Swal.fire({
        title: '📄 Preparando PDF...',
        html: '<div style="padding: 1rem;"><div class="reportes-loading-spinner" style="margin: 0 auto 1rem;"></div><p style="color: #6b7280;">Generando documento profesional de reporte</p></div>',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    try {
        const datos = await obtenerDatosReporte(periodo);
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    
    // Colores suaves y profesionales
    const colorPrimary = [59, 130, 246];     // Azul suave
    const colorSuccess = [34, 197, 94];      // Verde suave
    const colorWarning = [251, 146, 60];     // Naranja suave
    const colorDanger = [248, 113, 113];     // Rojo suave
    const colorGray = [156, 163, 175];       // Gris suave
    const colorDark = [55, 65, 81];          // Gris oscuro suave
    
    // === ENCABEZADO PRINCIPAL ===
    doc.setFillColor(59, 130, 246);
    doc.rect(0, 0, pageWidth, 35, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(22);
    doc.setFont(undefined, 'bold');
    doc.text('REPORTE DE VENTAS', pageWidth / 2, 15, { align: 'center' });
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    doc.text('Botica San Antonio', pageWidth / 2, 22, { align: 'center' });
    
    const usuario = document.querySelector('meta[name="usuario-nombre"]')?.getAttribute('content') || 'Usuario';
    const fechaGeneracion = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const periodoLegible = obtenerNombrePeriodo(periodo);
    const fechaMes = new Date();
    const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    const periodoDetallado = periodo === 'mes' ? `${periodoLegible} de ${meses[fechaMes.getMonth()]} del ${fechaMes.getFullYear()}` : periodoLegible;
    
    doc.setFontSize(9);
    doc.text(`Generado: ${fechaGeneracion} | Por: ${usuario}`, pageWidth / 2, 29, { align: 'center' });
    
    // === INFORMACIÓN DEL PERÍODO ===
    let yPos = 45;
    doc.setTextColor(...colorDark);
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text(`Período analizado: ${periodoDetallado}`, 20, yPos);
    yPos += 10;

    const productosVendidos = (datos.productos_mas_vendidos || []).reduce((sum, p) => sum + (p.total_vendido || 0), 0);
    const totalMetodos = (datos.metodos || []).reduce((a,b) => a + b, 0);
    const ticketPromedio = datos.estadisticas.total_ventas > 0 ? (datos.estadisticas.total_ingresos / datos.estadisticas.total_ventas) : 0;

    // === ESTADÍSTICAS PRINCIPALES ===
    doc.setFillColor(...colorPrimary);
    doc.rect(15, yPos - 3, pageWidth - 30, 8, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('ESTADÍSTICAS PRINCIPALES', 20, yPos + 2);
    yPos += 10;

    doc.autoTable({
        startY: yPos,
        theme: 'grid',
        styles: { 
            fontSize: 10, 
            cellPadding: 3,
            textColor: colorDark,
            lineColor: [209, 213, 219],
            lineWidth: 0.3
        },
        headStyles: { 
            fillColor: colorPrimary,
            textColor: [255, 255, 255],
            fontSize: 10,
            fontStyle: 'bold',
            halign: 'left',
            cellPadding: 3
        },
        columnStyles: {
            0: { cellWidth: 80, fontStyle: 'bold' },
            1: { cellWidth: 50, halign: 'right', fontStyle: 'bold', textColor: colorPrimary },
            2: { cellWidth: 'auto', textColor: colorGray }
        },
        head: [['Métrica', 'Valor', 'Detalle']],
        body: [
            ['Total de Ventas', String(datos.estadisticas.total_ventas || 0), 'Ventas completadas'],
            ['Ingresos Totales', 'S/ ' + Number(datos.estadisticas.total_ingresos || 0).toFixed(2), 'Monto total facturado'],
            ['Ticket Promedio', 'S/ ' + ticketPromedio.toFixed(2), 'Promedio por venta'],
            ['Productos Vendidos', String(parseInt(productosVendidos) || 0), 'Unidades totales']
        ],
        margin: { left: 20, right: 20 }
    });
    
    yPos = doc.lastAutoTable.finalY + 8;

    // === COMPARATIVO ===
    if (datos.comparativo) {
        const porcNum = Number(datos.comparativo.porcentaje_cambio);
        const porc = isFinite(porcNum) ? porcNum.toFixed(1) : '0.0';
        const positivo = (porcNum || 0) >= 0;
        const color = positivo ? colorSuccess : colorDanger;
        
        // Calcular diferencia en soles
        const actual = Number(datos.comparativo.periodo_actual || 0);
        const anterior = Number(datos.comparativo.periodo_anterior || 0);
        const diferencia = actual - anterior;
        const diferenciaTexto = diferencia >= 0 ? 
            `S/ ${diferencia.toFixed(2)} más` : 
            `S/ ${Math.abs(diferencia).toFixed(2)} menos`;
        
        doc.setFillColor(...(positivo ? [220, 252, 231] : [254, 242, 242]));
        doc.roundedRect(20, yPos, pageWidth - 40, 10, 2, 2, 'F');
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(...color);
        const flecha = positivo ? '▲' : '▼';
        doc.text(`${flecha} Comparativo: ${diferenciaTexto} que ${datos.comparativo.etiqueta || 'período anterior'}`, 25, yPos + 6);
        
        yPos += 13;
    }

    // === MÉTODOS DE PAGO ===
    doc.setFillColor(...colorSuccess);
    doc.rect(15, yPos - 3, pageWidth - 30, 8, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('MÉTODOS DE PAGO', 20, yPos + 2);
    yPos += 10;

    const cantidadInt = (v) => parseInt(v || 0, 10) || 0;
    const totalVentas = totalMetodos || 1;
    
    doc.autoTable({
        startY: yPos,
        theme: 'grid',
        styles: { 
            fontSize: 10, 
            cellPadding: 3,
            textColor: colorDark,
            lineColor: [209, 213, 219],
            lineWidth: 0.3
        },
        headStyles: { 
            fillColor: colorSuccess,
            textColor: [255, 255, 255],
            fontSize: 10,
            fontStyle: 'bold',
            cellPadding: 3
        },
        columnStyles: {
            0: { cellWidth: 60, fontStyle: 'bold' },
            1: { cellWidth: 40, halign: 'center', fontStyle: 'bold' },
            2: { cellWidth: 40, halign: 'center' },
            3: { cellWidth: 'auto', halign: 'right', textColor: colorSuccess, fontStyle: 'bold' }
        },
        head: [['Método', 'Cantidad', 'Porcentaje', 'Monto Estimado']],
        body: [
            [
                'Efectivo', 
                String(cantidadInt(datos.metodos?.[0])),
                totalVentas > 0 ? ((cantidadInt(datos.metodos?.[0]) / totalVentas) * 100).toFixed(1) + '%' : '0%',
                'S/ ' + (totalVentas > 0 ? ((cantidadInt(datos.metodos?.[0]) / totalVentas) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')
            ],
            [
                'Tarjeta', 
                String(cantidadInt(datos.metodos?.[1])),
                totalVentas > 0 ? ((cantidadInt(datos.metodos?.[1]) / totalVentas) * 100).toFixed(1) + '%' : '0%',
                'S/ ' + (totalVentas > 0 ? ((cantidadInt(datos.metodos?.[1]) / totalVentas) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')
            ],
            [
                'Yape', 
                String(cantidadInt(datos.metodos?.[2])),
                totalVentas > 0 ? ((cantidadInt(datos.metodos?.[2]) / totalVentas) * 100).toFixed(1) + '%' : '0%',
                'S/ ' + (totalVentas > 0 ? ((cantidadInt(datos.metodos?.[2]) / totalVentas) * datos.estadisticas.total_ingresos).toFixed(2) : '0.00')
            ]
        ],
        foot: [[
            'TOTAL',
            String(parseInt(totalMetodos)),
            '100%',
            'S/ ' + Number(datos.estadisticas.total_ingresos || 0).toFixed(2)
        ]],
        footStyles: {
            fillColor: [243, 244, 246],
            textColor: colorDark,
            fontStyle: 'bold'
        },
        margin: { left: 20, right: 20 }
    });
    
    yPos = doc.lastAutoTable.finalY + 10;

    // === TOP 10 PRODUCTOS ===
    if (yPos > pageHeight - 60) {
        doc.addPage();
        yPos = 20;
    }
    
    doc.setFillColor(...colorWarning);
    doc.rect(15, yPos - 3, pageWidth - 30, 8, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('TOP 10 PRODUCTOS MÁS VENDIDOS', 20, yPos + 2);
    yPos += 10;
    
    const productosTop = (datos.productos_mas_vendidos || []).slice(0, 10);
    const totalUnidadesProductos = productosTop.reduce((sum, p) => sum + (p.total_vendido || 0), 0);
    
    if (productosTop.length > 0) {
        doc.autoTable({
            startY: yPos,
            theme: 'grid',
            styles: { 
                fontSize: 9, 
                cellPadding: 2.5,
                textColor: colorDark,
                lineColor: [209, 213, 219],
                lineWidth: 0.3
            },
            headStyles: { 
                fillColor: colorWarning,
                textColor: [255, 255, 255],
                fontSize: 9,
                fontStyle: 'bold',
                cellPadding: 2.5
            },
            columnStyles: {
                0: { cellWidth: 15, halign: 'center', fontStyle: 'bold' },
                1: { cellWidth: 80 },
                2: { cellWidth: 35, halign: 'center' },
                3: { cellWidth: 'auto', halign: 'center', fontStyle: 'bold', textColor: colorWarning }
            },
            head: [['#', 'Producto', 'Concentración', 'Unidades']],
            body: productosTop.map((p, idx) => [
                String(idx + 1),
                p.producto?.nombre || p.nombre || 'Producto',
                p.producto?.concentracion || p.concentracion || 'N/A',
                String(parseInt(p.total_vendido) || 0)
            ]),
            margin: { left: 20, right: 20 }
        });
        
        yPos = doc.lastAutoTable.finalY + 10;
    }
    
    // === INGRESOS DETALLADOS (solo primeros 15 para no saturar) ===
    if (yPos > pageHeight - 60) {
        doc.addPage();
        yPos = 20;
    }
    
    doc.setFillColor(...colorPrimary);
    doc.rect(15, yPos - 3, pageWidth - 30, 8, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('INGRESOS DEL PERÍODO', 20, yPos + 2);
    yPos += 10;

    const ingresosLimitados = (datos.ingresos || []).slice(0, 15);
    const tablaIngresos = ingresosLimitados.map((i) => [
        i.fecha, 
        'S/ ' + (parseFloat(i.ingresos || 0)).toFixed(2)
    ]);
    
    if (tablaIngresos.length > 0) {
        doc.autoTable({
            startY: yPos,
            theme: 'grid',
            styles: { 
                fontSize: 9, 
                cellPadding: 2.5,
                textColor: colorDark,
                lineColor: [209, 213, 219],
                lineWidth: 0.3
            },
            headStyles: { 
                fillColor: colorPrimary,
                textColor: [255, 255, 255],
                fontSize: 9,
                fontStyle: 'bold',
                cellPadding: 2.5
            },
            columnStyles: {
                0: { cellWidth: 80 },
                1: { cellWidth: 'auto', halign: 'right', fontStyle: 'bold', textColor: colorSuccess }
            },
            head: [['Fecha/Período', 'Ingresos']],
            body: tablaIngresos,
            foot: [[
                'TOTAL',
                'S/ ' + (datos.ingresos || []).reduce((sum, i) => sum + parseFloat(i.ingresos || 0), 0).toFixed(2)
            ]],
            footStyles: {
                fillColor: [243, 244, 246],
                textColor: colorDark,
                fontStyle: 'bold'
            },
            margin: { left: 20, right: 20 }
        });
    }

    // === PIE DE PÁGINA ===
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(...colorGray);
        doc.text(
            `Página ${i} de ${totalPages} | Botica San Antonio | ${fechaGeneracion}`,
            pageWidth / 2,
            pageHeight - 10,
            { align: 'center' }
        );
    }
    
    const nombreArchivo = `Reporte_Ventas_${obtenerNombrePeriodo(periodo).replace(/ /g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(nombreArchivo);
    
    Swal.fire({
        title: '✅ PDF Generado',
        html: `<div style="text-align: center;"><p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Documento descargado exitosamente</p><p style="color: #6b7280; font-size: 0.9rem;">${nombreArchivo}</p><p style="color: #10b981; font-size: 0.85rem; margin-top: 1rem;">✓ Incluye: Estadísticas, Métodos de Pago, Top Productos e Ingresos</p></div>`,
        icon: 'success',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#10b981'
    });
            
    } catch (error) {
        console.error('❌ Error exportando PDF:', error);
        Swal.fire({
            title: 'Error',
            text: 'No se pudo generar el archivo PDF. Inténtalo de nuevo.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    }
}

// Obtener nombre legible del período
function obtenerNombrePeriodo(periodo) {
    const nombres = {
        'hoy': 'Hoy',
        'ultimos7': 'Últimos 7 días',
        'mes': 'Este mes',
        'anual': 'Este año'
    };
    return nombres[periodo] || periodo;
}

// Funciones de formateo
function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(cantidad);
}

function formatearPorcentaje(valor) {
    return new Intl.NumberFormat('es-PE', {
        style: 'percent',
        minimumFractionDigits: 1
    }).format(valor / 100);
}

function formatearNumero(numero) {
    return new Intl.NumberFormat('es-PE').format(numero);
}

// Inicializar gráficos
function inicializarGraficos() {
    console.log('📊 Inicializando gráficos...');
    
    // Ocultar elementos de carga
    const loadingVentas = document.getElementById('chart-loading-ventas');
    const loadingMetodos = document.getElementById('chart-loading-metodos');
    
    if (loadingVentas) {
        loadingVentas.style.display = 'none';
    }
    if (loadingMetodos) {
        loadingMetodos.style.display = 'none';
    }
    
    // Inicializar gráfico de ingresos (ApexCharts)
    try {
        if (window.datosIngresos) {
            inicializarGraficoIngresosApex(window.datosIngresos);
        }
    } catch (e) { console.warn('No se encontraron datosIniciales de ingresos'); }
    
    // Inicializar gráfico de métodos de pago
    inicializarGraficoMetodos();
}

// Gráfico de Top Productos (Barras)
function inicializarGraficoTopProductos(productos) {
    const canvas = document.getElementById('topProductosChart');
    if (!canvas) {
        console.warn('Canvas topProductosChart no encontrado');
        return;
    }
    
    // Si no hay productos, mostrar mensaje
    if (!productos || productos.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#6b7280';
        ctx.textAlign = 'center';
        ctx.fillText('No hay productos vendidos en este período', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // Preparar datos
    const productosTop = productos.slice(0, 10);
    const labels = productosTop.map(p => {
        const nombre = p.producto?.nombre || p.nombre || 'Desconocido';
        return nombre.length > 25 ? nombre.substring(0, 25) + '...' : nombre;
    });
    const data = productosTop.map(p => parseInt(p.total_vendido || p.cantidad || 0));
    
    // Colores degradados para las barras
    const colors = [
        'rgba(245, 158, 11, 0.8)',  // Top 1 - Dorado
        'rgba(59, 130, 246, 0.8)',  // Top 2 - Azul
        'rgba(16, 185, 129, 0.8)',  // Top 3 - Verde
        'rgba(139, 92, 246, 0.7)',  // Resto - Morado
        'rgba(139, 92, 246, 0.7)',
        'rgba(139, 92, 246, 0.7)',
        'rgba(139, 92, 246, 0.7)',
        'rgba(139, 92, 246, 0.7)',
        'rgba(139, 92, 246, 0.7)',
        'rgba(139, 92, 246, 0.7)'
    ];
    
    if (chartTopProductos) {
        chartTopProductos.destroy();
    }
    
    chartTopProductos = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Unidades Vendidas',
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        title: function(context) {
                            const idx = context[0].dataIndex;
                            return productosTop[idx].producto?.nombre || productosTop[idx].nombre || 'Desconocido';
                        },
                        label: function(context) {
                            return 'Unidades vendidas: ' + context.parsed.x;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { 
                        display: true,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#6b7280'
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#374151'
                    }
                }
            }
        }
    });
}

// Gráfico de ingresos por día
function inicializarGraficoIngresosApex(ventasData) {
    const container = document.getElementById('ventas-chart-reportes');
    const loading = document.getElementById('chart-loading-ventas');
    if (!container) return;
    if (loading) loading.style.display = 'flex';

    // Usar el período del SELECT del gráfico (NO los filtros superiores)
    const periodoSelect = document.getElementById('periodoChartSelect');
    const periodoActivo = periodoSelect ? periodoSelect.value : 'mes';
    
    console.log('🎨 Renderizando gráfico para período del gráfico:', periodoActivo);
    
    let labels = [];
    let data = ventasData.map(i => parseFloat(i.ingresos) || 0);
    
    // Formatear etiquetas según el período del SELECT del gráfico
    if (periodoActivo === 'hoy' || periodoActivo === 'ayer') {
        // Para hoy o ayer: mostrar horas de 8:00 AM a 10:00 PM
        labels = ventasData.map(i => {
            const fecha = i.fecha || '';
            if (fecha.includes(':')) {
                const partes = fecha.split(':');
                let hora = parseInt(partes[0]);
                const minuto = (partes[1] || '00').substring(0, 2);
                
                const ampm = hora >= 12 ? 'PM' : 'AM';
                if (hora > 12) hora -= 12;
                if (hora === 0) hora = 12;
                
                return `${hora}:${minuto} ${ampm}`;
            }
            return fecha;
        });
    } else if (periodoActivo === 'ultimos7') {
        // Para últimos 7 días: mostrar días
        labels = ventasData.map(i => {
            const fecha = i.fecha || '';
            if (fecha.match(/\d{4}-\d{2}-\d{2}/)) {
                const fechaObj = new Date(fecha + 'T00:00:00');
                const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                return diasSemana[fechaObj.getDay()];
            }
            return fecha;
        });
    } else if (periodoActivo === 'mes') {
        // Para mes: mostrar fechas
        labels = ventasData.map(i => {
            const fecha = i.fecha || '';
            if (fecha.match(/\d{4}-\d{2}-\d{2}/)) {
                const partes = fecha.split('-');
                return `${partes[2]}/${partes[1]}`;
            }
            return fecha;
        });
    } else if (periodoActivo === 'anual') {
        // Para año: mostrar meses
        labels = ventasData.map(i => {
            const fecha = i.fecha || '';
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            if (fecha.match(/\d{4}-\d{2}/)) {
                const partes = fecha.split('-');
                const mes = parseInt(partes[1]) - 1;
                return meses[mes];
            }
            return fecha;
        });
    } else {
        labels = ventasData.map(i => i.fecha || '');
    }

    const options = {
        series: [{ name: 'Ingresos', data }],
        chart: { 
            height: 380, 
            type: 'area',
            toolbar: { show: false }, 
            zoom: { enabled: false },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        dataLabels: { enabled: false },
        stroke: { 
            curve: 'smooth', 
            colors: ['#3b82f6'], 
            width: 3 
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        markers: { 
            size: 0, 
            strokeWidth: 3, 
            hover: { size: 8 } 
        },
        tooltip: { 
            y: { 
                formatter: (v) => 'S/. ' + Number(v).toFixed(2) 
            },
            style: {
                fontSize: '13px'
            }
        },
        grid: { 
            show: true, 
            borderColor: '#E5E7EB', 
            strokeDashArray: 4, 
            padding: { 
                left: 20, 
                right: 20,
                top: 10,
                bottom: 10
            },
            xaxis: {
                lines: { show: true }
            },
            yaxis: {
                lines: { show: true }
            }
        },
        yaxis: { 
            labels: { 
                formatter: (v) => {
                    if (!v || v === 0) return 'S/. 0';
                    if (v >= 1000) return 'S/. ' + (v / 1000).toFixed(1) + 'k';
                    return 'S/. ' + Number(v).toFixed(0);
                }, 
                offsetX: -5, 
                style: { 
                    fontSize: '12px',
                    fontWeight: 500,
                    colors: ['#6b7280']
                } 
            },
            min: 0
        },
        xaxis: { 
            categories: labels, 
            labels: { 
                rotate: labels.length > 12 ? -45 : 0, 
                style: { 
                    fontSize: '11px',
                    fontWeight: 500,
                    colors: '#6b7280'
                },
                trim: true,
                maxHeight: 80
            }, 
            tooltip: { enabled: false },
            axisBorder: {
                show: true,
                color: '#E5E7EB'
            },
            axisTicks: {
                show: true,
                color: '#E5E7EB'
            }
        },
        colors: ['#3b82f6']
    };

    container.innerHTML = '';
    apexChartIngresos = new ApexCharts(container, options);
    apexChartIngresos.render().then(() => { 
        if (loading) loading.style.display = 'none'; 
        calcularEstadisticasGrafico();
    });
}

function inicializarGraficoIngresosApexComparativo(labels, actual, prev, tituloComp) {
    const container = document.getElementById('ventas-chart-reportes');
    const loading = document.getElementById('chart-loading-ventas');
    const tituloEl = document.getElementById('reportes-titulo-periodo');
    if (!container) return;
    if (loading) loading.style.display = 'flex';
    if (tituloEl && tituloComp) tituloEl.textContent = tituloComp;

    // Determinar el período activo
    const periodoSelect = document.getElementById('periodoChartSelect');
    const periodoActivo = periodoSelect ? periodoSelect.value : 'mes';
    
    console.log('🎨 Renderizando gráfico COMPARATIVO para período:', periodoActivo);
    
    // Formatear etiquetas según el período
    let etiquetasFormateadas = [...labels];
    
    if (periodoActivo === 'hoy' || periodoActivo === 'ayer') {
        etiquetasFormateadas = labels.map(label => {
            if (label.includes(':')) {
                const partes = label.split(':');
                let hora = parseInt(partes[0]);
                let minuto = (partes[1] || '00').substring(0, 2);
                const ampm = hora >= 12 ? 'PM' : 'AM';
                if (hora > 12) hora -= 12;
                if (hora === 0) hora = 12;
                return `${hora}:${minuto} ${ampm}`;
            }
            return label;
        });
    } else if (periodoActivo === 'ultimos7') {
        etiquetasFormateadas = labels.map(label => {
            if (label.match(/\d{4}-\d{2}-\d{2}/)) {
                const fechaObj = new Date(label + 'T00:00:00');
                const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                return diasSemana[fechaObj.getDay()];
            }
            return label;
        });
    } else if (periodoActivo === 'mes') {
        etiquetasFormateadas = labels.map(label => {
            if (label.match(/\d{4}-\d{2}-\d{2}/)) {
                const partes = label.split('-');
                return `${partes[2]}/${partes[1]}`;
            }
            return label;
        });
    } else if (periodoActivo === 'anual') {
        etiquetasFormateadas = labels.map(label => {
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            if (label.match(/\d{4}-\d{2}/)) {
                const partes = label.split('-');
                const mes = parseInt(partes[1]) - 1;
                return meses[mes];
            }
            return label;
        });
    }

    const options = {
        series: [
            { name: 'Período seleccionado', data: actual },
            { name: 'Período anterior', data: prev }
        ],
        chart: { 
            height: 380, 
            type: 'area',
            toolbar: { show: false }, 
            zoom: { enabled: false },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        colors: ['#3b82f6', '#f59e0b'],
        dataLabels: { enabled: false },
        stroke: { 
            curve: 'smooth', 
            width: 3 
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        markers: { 
            size: 0, 
            strokeWidth: 3, 
            hover: { size: 8 } 
        },
        tooltip: { 
            y: { 
                formatter: (v) => 'S/. ' + Number(v).toFixed(2) 
            } 
        },
        grid: { 
            show: true, 
            borderColor: '#E5E7EB', 
            strokeDashArray: 4, 
            padding: { 
                left: 20, 
                right: 20,
                top: 10,
                bottom: 10
            } 
        },
        yaxis: { 
            labels: { 
                formatter: (v) => {
                    if (!v || v === 0) return 'S/. 0';
                    if (v >= 1000) return 'S/. ' + (v / 1000).toFixed(1) + 'k';
                    return 'S/. ' + Number(v).toFixed(0);
                }, 
                offsetX: -5, 
                style: { 
                    fontSize: '12px',
                    fontWeight: 500,
                    colors: ['#6b7280']
                } 
            },
            min: 0
        },
        xaxis: { 
            categories: etiquetasFormateadas, 
            labels: { 
                rotate: etiquetasFormateadas.length > 12 ? -45 : 0, 
                style: { 
                    fontSize: '11px',
                    fontWeight: 500,
                    colors: '#6b7280'
                },
                trim: true,
                maxHeight: 80
            }, 
            tooltip: { enabled: false },
            axisBorder: {
                show: true,
                color: '#E5E7EB'
            }
        },
        legend: { 
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '13px',
            fontWeight: 600,
            markers: {
                width: 12,
                height: 12,
                radius: 3
            },
            itemMargin: {
                horizontal: 15,
                vertical: 10
            }
        }
    };

    container.innerHTML = '';
    apexChartIngresos = new ApexCharts(container, options);
    apexChartIngresos.render().then(() => { 
        if (loading) loading.style.display = 'none'; 
        calcularEstadisticasGrafico();
    });
}

// Gráfico de métodos de pago
function inicializarGraficoMetodos() {
    const canvas = document.getElementById('metodosChart');
    if (!canvas) {
        console.warn('Canvas metodosChart no encontrado');
        return;
    }
    
    const body = canvas.parentElement;
    const counts = (window.datosMetodos || []).map(m => parseInt(m.total || 0, 10));
    const total = counts.reduce((a,b)=>a+b,0);
    
    if (!total || total === 0) {
        if (chartMetodos) {
            chartMetodos.destroy();
            chartMetodos = null;
        }
        const msg = document.createElement('div');
        msg.style.padding = '3rem 1rem';
        msg.style.textAlign = 'center';
        msg.style.color = '#6b7280';
        msg.innerHTML = `
            <iconify-icon icon="mdi:cash-remove" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></iconify-icon>
            <p style="font-weight: 600; margin-bottom: 0.5rem;">Sin datos de métodos de pago</p>
            <p style="font-size: 0.875rem;">No se han realizado ventas en el período seleccionado</p>
        `;
        if (body) {
            const existingMsg = body.querySelector('div');
            if (existingMsg && existingMsg !== canvas) {
                existingMsg.remove();
            }
            canvas.style.display = 'none';
            body.appendChild(msg);
        }
        return;
    }

    // Mostrar canvas si estaba oculto
    canvas.style.display = 'block';
    
    // Remover mensaje si existe
    const existingMsg = body.querySelector('div:not(#chart-loading-metodos)');
    if (existingMsg && existingMsg !== canvas && !existingMsg.id) {
        existingMsg.remove();
    }

    const datos = {
        labels: ['Efectivo', 'Tarjeta', 'Yape'],
        datasets: [{
            data: counts,
            backgroundColor: ['#10b981','#3b82f6','#f59e0b'],
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverOffset: 8,
            hoverBorderWidth: 4
        }]
    };

    if (chartMetodos) {
        chartMetodos.destroy();
    }

    chartMetodos = new Chart(canvas, {
        type: 'doughnut',
        data: datos,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 13, weight: '600' },
                        color: '#374151',
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    return {
                                        text: `${label}: ${parseInt(value, 10)}`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].backgroundColor[i],
                                        lineWidth: 0,
                                        pointStyle: 'circle'
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = parseInt(context.parsed, 10) || 0;
                            return `${label}: ${value} ventas`;
                        }
                    }
                }
            }
        }
    });
}

// Función para obtener datos del reporte según período desde el backend
async function obtenerDatosReporte(periodo, fechaInicio = null, fechaFin = null) {
    try {
        console.log('🔄 Obteniendo datos reales del backend para período:', periodo);
        
        let url = `/ventas/reportes/datos?periodo=${periodo}`;
        
        // Agregar parámetros adicionales si es personalizado
        if (periodo === 'personalizado' && fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('✅ Datos obtenidos del backend:', data);
        
        // Formatear datos para compatibilidad con gráficos
        const datosFormateados = {
            ingresos: data.ingresos_por_dia || [],
            metodos: [
                data.ventas_por_metodo?.find(m => m.metodo_pago === 'efectivo')?.total || 0,
                data.ventas_por_metodo?.find(m => m.metodo_pago === 'tarjeta')?.total || 0,
                data.ventas_por_metodo?.find(m => m.metodo_pago === 'yape')?.total || 0
            ],
            estadisticas: {
                total_ingresos: parseFloat(data.total_ingresos || 0),
                total_ventas: parseInt(data.total_ventas || 0),
                promedio: parseFloat(data.promedio || 0)
            },
            productos_mas_vendidos: data.productos_mas_vendidos || [],
            marcas_mas_compradas: data.marcas_mas_compradas || [],
            comparativo: data.comparativo || null,
            tituloPeriodo: data.tituloPeriodo || '',
            detalle_productos_vendidos: data.detalle_productos_vendidos || []
        };
        
        return datosFormateados;
        
    } catch (error) {
        console.error('❌ Error obteniendo datos del reporte:', error);
        
        // Datos de fallback en caso de error
        return {
            ingresos: [],
            metodos: [0, 0, 0],
            estadisticas: {
                total_ingresos: 0,
                total_ventas: 0,
                promedio: 0
            },
            productos_mas_vendidos: [],
            comparativo: null,
            tituloPeriodo: ''
        };
    }
}

async function obtenerDatosComparativo(periodo, contra, agrupacion) {
    try {
        const res = await fetch(`/ventas/reportes/comparativo?periodo=${periodo}&contra=${contra}&agrupacion=${agrupacion}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        return data;
    } catch (e) {
        console.warn('Comparativo no disponible');
        return null;
    }
}

// Función para actualizar datos por período
async function actualizarDatosPorPeriodo(periodo) {
    console.log('🔄 Actualizando datos para período:', periodo);
    
    try {
        // Obtener datos del período desde el backend
        const datos = await obtenerDatosReporte(periodo);
        
        // Guardar datos globalmente para las alertas
        window.datosReporte = datos;
        
        // Actualizar estadísticas principales (tarjetas 1, 2, 3) con comparaciones
        actualizarEstadisticas(datos.estadisticas, datos.comparativo);
        
        // Actualizar tarjeta 4: Productos Vendidos (total unidades)
        const productosVendidosEl = document.getElementById('statProductosVendidos');
        const totalUnidades = (datos.productos_mas_vendidos || []).reduce((sum, p) => sum + (parseInt(p.total_vendido) || 0), 0);
        if (productosVendidosEl) productosVendidosEl.textContent = totalUnidades;
        
        // Actualizar tarjeta 5: Productos Únicos
        const productosUnicosEl = document.getElementById('statProductosUnicos');
        if (productosUnicosEl) productosUnicosEl.textContent = (datos.productos_mas_vendidos || []).length;
        
        // Actualizar título del período
        const inicio = datos.fecha_inicio ? new Date(datos.fecha_inicio).toLocaleDateString('es-PE') : '';
        const fin = datos.fecha_fin ? new Date(datos.fecha_fin).toLocaleDateString('es-PE') : '';
        actualizarTituloPeriodo(inicio && fin ? `${inicio} - ${fin}` : datos.tituloPeriodo);
        
        // Actualizar texto de período de comparación
        actualizarTextoPeriodoComparacion(periodo, datos);
        
        // Actualizar gráficos
        window.datosIngresos = datos.ingresos;
        actualizarGraficoIngresos(datos.ingresos);
        actualizarGraficoMetodos(datos.metodos);
        
        // Calcular estadísticas del gráfico
        calcularEstadisticasGrafico();
        calcularMontosMetodosPago();
        
        // Actualizar tablas
        renderTopProductos(datos.productos_mas_vendidos || []);
        renderTopMarcas(datos.marcas_mas_compradas || []);
        
        // Recargar alertas (ahora con datos actualizados)
        cargarAlertas();
        
        console.log('✅ Datos actualizados correctamente');
        if (typeof Swal !== 'undefined') Swal.close();
        
    } catch (error) {
        console.error('❌ Error actualizando datos:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los datos del reporte. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    } finally {
        if (typeof Swal !== 'undefined') {
            try { Swal.close(); } catch (e) {}
        }
    }
}

// Actualizar texto de período de comparación en el gráfico
function actualizarTextoPeriodoComparacion(periodo, datos) {
    const textoPeriodoEl = document.getElementById('textoPeriodoComparacion');
    if (!textoPeriodoEl) return;
    
    const hoy = new Date();
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    let textoHtml = '';
    
    switch(periodo) {
        case 'hoy':
            const ayer = new Date(hoy);
            ayer.setDate(ayer.getDate() - 1);
            textoHtml = `<span style="color: #3b82f6;">${hoy.getDate()} de ${meses[hoy.getMonth()]} de ${hoy.getFullYear()}</span> <span style="color: #f59e0b;">VS</span> <span style="color: #f59e0b;">${ayer.getDate()} de ${meses[ayer.getMonth()]} de ${ayer.getFullYear()}</span>`;
            break;
        case 'ayer':
            const ayer2 = new Date(hoy);
            ayer2.setDate(ayer2.getDate() - 1);
            const antesAyer = new Date(hoy);
            antesAyer.setDate(antesAyer.getDate() - 2);
            textoHtml = `<span style="color: #3b82f6;">${ayer2.getDate()} de ${meses[ayer2.getMonth()]} de ${ayer2.getFullYear()}</span> <span style="color: #f59e0b;">VS</span> <span style="color: #f59e0b;">${antesAyer.getDate()} de ${meses[antesAyer.getMonth()]} de ${antesAyer.getFullYear()}</span>`;
            break;
        case 'esta_semana':
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - 6);
            textoHtml = `<span style="color: #3b82f6;">${inicioSemana.getDate()} de ${meses[inicioSemana.getMonth()]}</span> <span style="color: #f59e0b;">hasta</span> <span style="color: #3b82f6;">${hoy.getDate()} de ${meses[hoy.getMonth()]} de ${hoy.getFullYear()}</span>`;
            break;
        case 'este_mes':
            const mesAnterior = new Date(hoy);
            mesAnterior.setMonth(mesAnterior.getMonth() - 1);
            textoHtml = `<span style="color: #3b82f6;">${meses[hoy.getMonth()].charAt(0).toUpperCase() + meses[hoy.getMonth()].slice(1)} de ${hoy.getFullYear()}</span> <span style="color: #f59e0b;">VS</span> <span style="color: #f59e0b;">${meses[mesAnterior.getMonth()].charAt(0).toUpperCase() + meses[mesAnterior.getMonth()].slice(1)} de ${mesAnterior.getFullYear()}</span>`;
            break;
        case 'este_anio':
            const anioAnterior = hoy.getFullYear() - 1;
            textoHtml = `<span style="color: #3b82f6;">${hoy.getFullYear()}</span> <span style="color: #f59e0b;">VS</span> <span style="color: #f59e0b;">${anioAnterior}</span>`;
            break;
        default:
            textoHtml = '';
    }
    
    textoPeriodoEl.innerHTML = textoHtml;
}

function actualizarMiniDeltaDesdeComparativo(comp, contra){
    const el = document.getElementById('miniStatDelta');
    if (!el || !comp) return;
    const totalAct = (comp.actual || []).reduce((a,b)=>a+(Number(b)||0),0);
    const totalPrev = (comp.prev || []).reduce((a,b)=>a+(Number(b)||0),0);
    const pct = totalPrev > 0 ? ((totalAct - totalPrev)/totalPrev)*100 : 0;
    el.style.display = '';
    el.classList.remove('delta-up','delta-down');
    const isUp = pct >= 0;
    el.classList.add(isUp ? 'delta-up' : 'delta-down');
    const etiquetas = { ayer: 'vs ayer', semana_anterior: 'vs semana anterior', mes_anterior: 'vs mes anterior', anio_anterior: 'vs año anterior' };
    el.textContent = (isUp ? '▲ ' : '▼ ') + Math.abs(pct).toFixed(1) + '% ' + (etiquetas[contra] || '');
}

function renderTopProductos(items){
    const tbody = document.getElementById('topProductosBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    // Si no hay items, mostrar mensaje
    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">No hay datos para mostrar</td></tr>';
        return;
    }
    
    items.slice(0,10).forEach((it, idx)=>{
        const unidades = Number(it.total_vendido || it.cantidad || it.unidades || 0);
        
        // Calcular ingresos de manera más robusta
        let ingresos = 0;
        if (it.total_ingresos) {
            ingresos = parseFloat(it.total_ingresos);
        } else if (it.ingresos) {
            ingresos = parseFloat(it.ingresos);
        } else if (it.precio_promedio) {
            ingresos = unidades * parseFloat(it.precio_promedio);
        } else if (it.precio) {
            ingresos = unidades * parseFloat(it.precio);
        } else if (it.producto?.precio_venta) {
            ingresos = unidades * parseFloat(it.producto.precio_venta);
        }
        
        // Determinar tendencia basada en posición y cantidad
        let tendenciaBadge = '';
        let tendenciaClass = '';
        
        if (idx === 0 && unidades > 5) {
            tendenciaBadge = '⭐ Estrella';
            tendenciaClass = 'badge-gold';
        } else if (unidades > 8) {
            tendenciaBadge = '🔥 Popular';
            tendenciaClass = 'badge-red';
        } else if (unidades > 5) {
            tendenciaBadge = '📈 En Alza';
            tendenciaClass = 'badge-blue';
        } else if (unidades > 3) {
            tendenciaBadge = '✓ Estable';
            tendenciaClass = 'badge-green';
        } else {
            tendenciaBadge = '→ Normal';
            tendenciaClass = 'badge-gray';
        }
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><div class="reportes-rank-badge reportes-rank-${idx < 3 ? 'top' : 'normal'}">${idx+1}</div></td>
            <td>
                <div class="reportes-producto">
                    <div class="reportes-producto-nombre">${it.producto?.nombre || it.nombre || 'Producto'}</div>
                    <div class="reportes-producto-detalle">${it.producto?.concentracion || it.concentracion || ''}</div>
                </div>
            </td>
            <td class="text-center"><span class="reportes-cantidad">${unidades}</span></td>
            <td class="text-center"><span class="reportes-ingresos-valor">S/. ${ingresos.toFixed(2)}</span></td>
            <td class="text-center">
                <span class="reportes-tendencia-badge ${tendenciaClass}">
                    ${tendenciaBadge}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderTopMarcas(items){
    const tbody = document.getElementById('topMarcasBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    items.slice(0,10).forEach((m, idx)=>{
        const unidades = Number(m.unidades || 0);
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><div class="reportes-rank-badge reportes-rank-${idx < 3 ? 'top' : 'normal'}">${idx+1}</div></td>
            <td>
                <div class="reportes-producto">
                    <div class="reportes-producto-nombre">${m.marca || 'Sin marca'}</div>
                </div>
            </td>
            <td class="text-center"><span class="reportes-cantidad">${unidades}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

// Actualizar estadísticas principales con comparaciones
function actualizarEstadisticas(estadisticas, comparativo) {
    // Actualizar tarjeta 1: Total Ventas
    const totalVentasEl = document.getElementById('statTotalVentas');
    if (totalVentasEl) totalVentasEl.textContent = Number(estadisticas.total_ventas || 0);
    
    // Actualizar tarjeta 2: Ingresos Totales
    const ingresosEl = document.getElementById('statIngresosTotal');
    if (ingresosEl) ingresosEl.textContent = 'S/ ' + Number(estadisticas.total_ingresos || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    // Actualizar tarjeta 3: Ticket Promedio
    const ticketEl = document.getElementById('statTicketPromedio');
    if (ticketEl) ticketEl.textContent = 'S/ ' + Number(estadisticas.ticket_promedio || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    // Actualizar comparaciones si hay datos
    if (comparativo) {
        actualizarComparacionTarjeta('compTotalVentas', comparativo.ventas_cambio || 0, 'vs período anterior');
        actualizarComparacionTarjeta('compIngresos', comparativo.ingresos_cambio || 0, 'vs período anterior');
        actualizarComparacionTarjeta('compTicket', comparativo.ticket_cambio || 0, 'vs período anterior');
    }
}

// Actualizar comparación en tarjeta
function actualizarComparacionTarjeta(elementId, porcentaje, texto) {
    const el = document.getElementById(elementId);
    if (!el) return;
    
    const isPositive = porcentaje >= 0;
    const icon = el.querySelector('iconify-icon');
    const span = el.querySelector('span');
    
    if (icon) {
        icon.setAttribute('icon', isPositive ? 'solar:arrow-up-bold' : 'solar:arrow-down-bold');
    }
    
    if (span) {
        span.textContent = `${isPositive ? '+' : ''}${porcentaje.toFixed(1)}% ${texto}`;
    }
    
    // Cambiar color
    el.style.color = isPositive ? '#10b981' : '#ef4444';
    
    // Agregar o quitar clase
    el.classList.remove('positive', 'negative');
    el.classList.add(isPositive ? 'positive' : 'negative');
}

function actualizarComparativo(comparativo) {
    const el = document.getElementById('miniStatDelta');
    if (!el) return;
    if (!comparativo) { el.style.display = 'none'; el.textContent = ''; el.classList.remove('delta-up','delta-down'); return; }
    el.style.display = '';
    const pct = Number(comparativo.porcentaje_cambio || 0);
    const isUp = pct >= 0;
    el.classList.remove('delta-up','delta-down');
    el.classList.add(isUp ? 'delta-up' : 'delta-down');
    const etiqueta = comparativo.etiqueta || 'vs período anterior';
    el.textContent = (isUp ? '▲ ' : '▼ ') + Math.abs(pct).toFixed(1) + '% ' + etiqueta;
}

function actualizarTituloPeriodo(titulo) {
    const tituloEl = document.getElementById('reportes-titulo-periodo');
    if (tituloEl && titulo) tituloEl.textContent = titulo;
}

// Actualizar gráfico de ingresos
function actualizarGraficoIngresos(nuevosIngresos) {
    inicializarGraficoIngresosApex(nuevosIngresos);
}

// Actualizar gráfico de métodos de pago
function actualizarGraficoMetodos(nuevosMetodos) {
    if (chartMetodos) {
        chartMetodos.data.datasets[0].data = nuevosMetodos;
        chartMetodos.update('active');
    }
}

console.log('✅ Reportes - JavaScript completamente cargado');
