document.addEventListener('DOMContentLoaded', function() {
    initVencimientoChart();
    initTopValorizacionChart();
});

function initVencimientoChart() {
    const ctx = document.getElementById('vencimientoRiesgoChart');
    if (!ctx) return;

    const data = window.vencimientoData || {
        vencidos: 0,
        proximos_3: 0,
        proximos_6: 0,
        proximos_12: 0
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Vencidos', 'Próx. 3 meses', '3 - 6 meses', '6 - 12 meses'],
            datasets: [{
                label: 'Cantidad de Lotes',
                data: [
                    data.vencidos,
                    data.proximos_3,
                    data.proximos_6,
                    data.proximos_12
                ],
                backgroundColor: [
                    '#ef4444', // Rojo
                    '#f59e0b', // Naranja
                    '#fbbf24', // Amarillo
                    '#3b82f6'  // Azul
                ],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function initTopValorizacionChart() {
    const ctx = document.getElementById('topValorizacionChart');
    if (!ctx) return;

    const rawData = window.topValorizacionData || [];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: rawData.map(d => d.nombre.length > 20 ? d.nombre.substring(0, 20) + '...' : d.nombre),
            datasets: [{
                data: rawData.map(d => d.valor),
                backgroundColor: [
                    '#3b82f6', // Blue
                    '#8b5cf6', // Purple
                    '#ec4899', // Pink
                    '#f59e0b', // Orange
                    '#10b981'  // Green
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            label += 'S/ ' + context.raw.toLocaleString('es-PE', { minimumFractionDigits: 2 });
                            return label;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}
