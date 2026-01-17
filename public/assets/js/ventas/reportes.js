console.log('✅ Reportes - JavaScript cargado');

// Variables globales
let chartMetodos;
let chartTopProductos;
let apexChartIngresos;
let datosReporte = {};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando módulo de Reportes');
    
    // Configurar eventos adicionales
    configurarEventos();
    
    // Seleccionar comparación por defecto según período
    const periodoChartSelectEl = document.getElementById('periodoChartSelect');
    const periodoInicial = document.querySelector('select[name="periodo"]')?.value || 'mes';
    if (periodoChartSelectEl) {
        periodoChartSelectEl.value = periodoInicial;
    }
    const periodoChart = periodoChartSelectEl?.value || periodoInicial;
    const compararSelectInit = document.getElementById('compararSelect');
    const vistaLabelInit = document.getElementById('vistaMesLabel');
    const vistaSelectInit = document.getElementById('vistaMesSelect');
    if (vistaLabelInit && vistaSelectInit) {
        const esMes = periodoChart === 'mes';
        vistaLabelInit.style.display = esMes ? '' : 'none';
        vistaSelectInit.style.display = esMes ? '' : 'none';
        vistaSelectInit.value = 'semanal';
    }
    if (compararSelectInit) {
        const mapa = { hoy: 'ayer', ultimos7: 'semana_anterior', mes: 'mes_anterior', anual: 'anio_anterior' };
        compararSelectInit.value = mapa[periodoChart] || 'mes_anterior';
        try { actualizarOpcionesComparacion(periodoChart); } catch(e) {}
    }
    
    // Inicializar gráficos inmediatamente con datos del servidor
    setTimeout(() => {
        try {
            inicializarGraficos();
            console.log('✅ Gráficos inicializados correctamente');
        } catch(e) {
            console.error('Error inicializando gráficos:', e);
        }
    }, 100);
    
    console.log('✅ Reportes inicializado correctamente');
});

// Configurar eventos adicionales
function configurarEventos() {
    // Referencias a elementos del DOM
    const selectPeriodo = document.getElementById('periodoSelect');
    const fechasPersonalizadas = document.getElementById('fechasPersonalizadas');
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const usuarioSelect = document.getElementById('usuarioSelect');
    
    // Cambio de período principal
    if (selectPeriodo) {
        selectPeriodo.addEventListener('change', function() {
            const periodo = this.value;
            
            // Mostrar/ocultar inputs de fecha personalizada
            if (periodo === 'personalizado') {
                if (fechasPersonalizadas) fechasPersonalizadas.style.display = 'flex';
                // No actualizamos automáticamente, esperamos al botón Aplicar
            } else {
                if (fechasPersonalizadas) fechasPersonalizadas.style.display = 'none';
                mostrarCargandoPeriodo();
                actualizarDatosPorPeriodo(periodo);
            }
        });
    }

    // Botón Aplicar Filtros (para personalizado y vendedor)
    if (btnAplicar) {
        btnAplicar.addEventListener('click', function() {
            const periodo = selectPeriodo ? selectPeriodo.value : 'mes';
            
            if (periodo === 'personalizado') {
                const inicio = document.getElementById('fechaInicio').value;
                const fin = document.getElementById('fechaFin').value;
                
                if (!inicio || !fin) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fechas requeridas',
                        text: 'Por favor selecciona una fecha de inicio y fin.'
                    });
                    return;
                }
                
                if (new Date(inicio) > new Date(fin)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rango inválido',
                        text: 'La fecha de inicio no puede ser mayor a la fecha fin.'
                    });
                    return;
                }
            }
            
            mostrarCargandoPeriodo();
            actualizarDatosPorPeriodo(periodo);
        });
    }

    // Cambio de vendedor (opcional: auto-actualizar o esperar a botón)
    // Dejamos que espere al botón Aplicar para no saturar si cambia varios filtros

    // ... (resto de eventos existentes para gráficos) ...
    const periodoChartSelect = document.getElementById('periodoChartSelect');
    const compararSelect = document.getElementById('compararSelect');
    const vistaMesLabel = document.getElementById('vistaMesLabel');
    const vistaMesSelect = document.getElementById('vistaMesSelect');
    if (periodoChartSelect) {
        periodoChartSelect.addEventListener('change', async function() {
            const periodo = this.value;
            mostrarCargandoPeriodo();
            await actualizarDatosPorPeriodo(periodo); // Este select es del gráfico específico
            // Ajustar comparación por defecto acorde al período
            if (compararSelect) {
                const mapa = { hoy: 'ayer', ultimos7: 'semana_anterior', mes: 'mes_anterior', anual: 'anio_anterior' };
                compararSelect.value = mapa[periodo] || 'mes_anterior';
                // Limitar opciones de comparación a la granularidad del período
                try { actualizarOpcionesComparacion(periodo); } catch(e) {}
                compararSelect.dispatchEvent(new Event('change'));
            }
            if (vistaMesLabel && vistaMesSelect) {
                const esMes = periodo === 'mes';
                vistaMesLabel.style.display = esMes ? '' : 'none';
                vistaMesSelect.style.display = esMes ? '' : 'none';
                if (esMes) vistaMesSelect.value = 'semanal';
            }
        });
    }
    if (compararSelect) {
        compararSelect.addEventListener('change', async function() {
            const periodo = periodoChartSelect?.value || (document.querySelector('select[name="periodo"]')?.value || 'mes');
            const contra = this.value;
            const agrup = (vistaMesSelect && vistaMesSelect.style.display !== 'none') ? (vistaMesSelect.value || 'auto') : 'auto';
            if (contra === 'none') {
                inicializarGraficoIngresosApex(window.datosIngresos || []);
                return;
            }
            mostrarCargandoPeriodo();
            const comp = await obtenerDatosComparativo(periodo, contra, agrup);
            if (comp && comp.labels && comp.actual && comp.prev) {
                inicializarGraficoIngresosApexComparativo(comp.labels, comp.actual, comp.prev, comp.titulo || '');
                actualizarMiniDeltaDesdeComparativo(comp, contra);
            } else {
                inicializarGraficoIngresosApex(window.datosIngresos || []);
            }
            try { Swal.close(); } catch(e) {}
        });
    }
    if (vistaMesSelect) {
        vistaMesSelect.addEventListener('change', function() {
            compararSelect?.dispatchEvent(new Event('change'));
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
    const periodo = document.querySelector('select[name="periodo"]').value;
    
    Swal.fire({
        title: '📊 Exportar Reporte',
        html: `
            <div style="text-align: left;">
                <p><strong>Período seleccionado:</strong> ${obtenerNombrePeriodo(periodo)}</p>
                <p>¿En qué formato deseas exportar el reporte?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: false,
        showDenyButton: true,
        confirmButtonText: '📊 Excel',
        denyButtonText: '📄 PDF',
        confirmButtonColor: '#059669',
        denyButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            exportarExcel(periodo);
        } else if (result.isDenied) {
            exportarPDF(periodo);
        }
    });
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

    const labels = ventasData.map(i => i.fecha);
    const data = ventasData.map(i => parseFloat(i.ingresos) || 0);

    const options = {
        series: [{ name: 'Período seleccionado', data }],
        chart: { height: 380, type: 'line', toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', colors: ['#3b82f6'], width: 3 },
        markers: { size: 0, strokeWidth: 3, hover: { size: 8 } },
        tooltip: { y: { formatter: (v) => 'S/. ' + Number(v).toFixed(2) } },
        grid: { show: true, borderColor: '#E5E7EB', strokeDashArray: 4, padding: { left: 24, right: 8 } },
        yaxis: { labels: { formatter: (v) => 'S/. ' + Number(v).toFixed(0), offsetX: 6, style: { fontSize: '12px' } } },
        xaxis: { categories: labels, labels: { rotate: labels.length > 8 ? -45 : 0, style: { fontSize: '12px' } }, tooltip: { enabled: false } }
    };

    container.innerHTML = '';
    apexChartIngresos = new ApexCharts(container, options);
    apexChartIngresos.render().then(() => { if (loading) loading.style.display = 'none'; });
}

function inicializarGraficoIngresosApexComparativo(labels, actual, prev, tituloComp) {
    const container = document.getElementById('ventas-chart-reportes');
    const loading = document.getElementById('chart-loading-ventas');
    const tituloEl = document.getElementById('reportes-titulo-periodo');
    if (!container) return;
    if (loading) loading.style.display = 'flex';
    if (tituloEl && tituloComp) tituloEl.textContent = tituloComp;

    const options = {
        series: [
            { name: 'Período seleccionado', data: actual },
            { name: 'Período anterior', data: prev }
        ],
        chart: { height: 380, type: 'line', toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#3b82f6', '#f59e0b'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 0, strokeWidth: 3, hover: { size: 8 } },
        tooltip: { y: { formatter: (v) => 'S/. ' + Number(v).toFixed(2) } },
        grid: { show: true, borderColor: '#E5E7EB', strokeDashArray: 4, padding: { left: 24, right: 8 } },
        yaxis: { labels: { formatter: (v) => 'S/. ' + Number(v).toFixed(0), offsetX: 6, style: { fontSize: '12px' } } },
        xaxis: { categories: labels, labels: { rotate: labels.length > 8 ? -45 : 0, style: { fontSize: '12px' } }, tooltip: { enabled: false } },
        legend: { position: 'bottom' }
    };

    container.innerHTML = '';
    apexChartIngresos = new ApexCharts(container, options);
    apexChartIngresos.render().then(() => { if (loading) loading.style.display = 'none'; });
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
async function obtenerDatosReporte(periodo) {
    try {
        console.log('🔄 Obteniendo datos reales del backend para período:', periodo);
        
        let url = `/ventas/reportes/datos?periodo=${periodo}`;
        
        // Agregar parámetros adicionales si es personalizado o hay vendedor
        if (periodo === 'personalizado') {
            const inicio = document.getElementById('fechaInicio')?.value;
            const fin = document.getElementById('fechaFin')?.value;
            if (inicio && fin) {
                url += `&fecha_inicio=${inicio}&fecha_fin=${fin}`;
            }
        }
        
        const usuarioId = document.getElementById('usuarioSelect')?.value;
        if (usuarioId) {
            url += `&usuario_id=${usuarioId}`;
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
    // Mostrar indicador de carga ya se realiza antes de llamar esta función
        
        // Obtener datos del período desde el backend
        const datos = await obtenerDatosReporte(periodo);
        
        // Actualizar estadísticas principales
        actualizarEstadisticas(datos.estadisticas);
        actualizarComparativo(datos.comparativo);
        const inicio = datos.fecha_inicio ? new Date(datos.fecha_inicio).toLocaleDateString('es-PE') : '';
        const fin = datos.fecha_fin ? new Date(datos.fecha_fin).toLocaleDateString('es-PE') : '';
        actualizarTituloPeriodo(inicio && fin ? `${inicio} - ${fin}` : datos.tituloPeriodo);
        
        // Actualizar gráficos
        window.datosIngresos = datos.ingresos;
        actualizarGraficoIngresos(datos.ingresos);
        actualizarGraficoMetodos(datos.metodos);
        inicializarGraficoTopProductos(datos.productos_mas_vendidos || []);

        // Actualizar tablas por período
        renderTopProductos(datos.productos_mas_vendidos || []);
        renderTopMarcas(datos.marcas_mas_compradas || []);
        
        console.log('✅ Datos actualizados correctamente');
        if (typeof Swal !== 'undefined') Swal.close();
        
    } catch (error) {
        console.error('❌ Error actualizando datos:', error);
        
        // Mostrar mensaje de error al usuario
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los datos del reporte. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    } finally {
        // Asegurar cierre del modal de carga
        if (typeof Swal !== 'undefined') {
            try { Swal.close(); } catch (e) {}
        }
    }
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
    items.slice(0,10).forEach((it, idx)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><div class="reportes-rank-badge reportes-rank-${idx < 3 ? 'top' : 'normal'}">${idx+1}</div></td>
            <td>
                <div class="reportes-producto">
                    <div class="reportes-producto-nombre">${it.producto?.nombre || it.nombre || 'Producto'}</div>
                    <div class="reportes-producto-detalle">${it.producto?.concentracion || it.concentracion || ''}</div>
                </div>
            </td>
            <td class="text-center"><span class="reportes-cantidad">${Number(it.total_vendido || it.cantidad || it.unidades || 0)}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

function renderTopMarcas(items){
    const tbody = document.getElementById('topMarcasBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    items.slice(0,10).forEach((m, idx)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><div class="reportes-rank-badge reportes-rank-${idx < 3 ? 'top' : 'normal'}">${idx+1}</div></td>
            <td><div class="reportes-producto"><div class="reportes-producto-nombre">${m.marca || 'Sin marca'}</div></div></td>
            <td class="text-center"><span class="reportes-cantidad">${Number(m.unidades || 0)}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

// Actualizar estadísticas principales
function actualizarEstadisticas(estadisticas) {
    const totalEl = document.querySelector('.estadisticas-total');
    const ventasEl = document.querySelector('.estadisticas-ventas');
    const promEl = document.querySelector('.estadisticas-promedio');
    if (totalEl) totalEl.textContent = 'S/. ' + (Number(estadisticas.total_ingresos || 0)).toFixed(2);
    if (ventasEl) ventasEl.textContent = `${Number(estadisticas.total_ventas || 0)} ventas`;
    if (promEl) promEl.textContent = '+ S/. ' + (Number(estadisticas.promedio || 0)).toFixed(2) + ' Por día';
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
