document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('productos-botica-tbody');
  const info = document.getElementById('productos-botica-pagination-info');
  const perPageEl = document.getElementById('mostrarBotica');
  const estadoEl = document.getElementById('estadoBotica');
  const searchEl = document.getElementById('buscarProductoBotica');
  const clearBtn = document.getElementById('clearBuscarProductoBotica');

  let page = 1;

  // Loading overlay (reutiliza el componente común)
  const loadingOverlay = document.getElementById('loadingOverlay');
  function showLoading(label = 'Cargando datos...') {
    if (loadingOverlay) {
      loadingOverlay.style.display = 'flex';
      const textEl = loadingOverlay.querySelector('.loading-text');
      if (textEl) textEl.textContent = label;
    }
  }
  function hideLoading() {
    if (loadingOverlay) loadingOverlay.style.display = 'none';
  }

  function getEstadoTooltip(prod) {
    const estado = prod.estado || 'Normal';
    if (estado === 'Bajo stock') {
      const s = Number(prod.stock_actual || 0);
      const m = Number(prod.stock_minimo || 0);
      if (m > 0 && s <= m) return `Stock bajo: ${s} por debajo del mínimo ${m}`;
      return 'Stock bajo';
    }
    if (estado === 'Por vencer') {
      const dias = calcDiasA(expiraEn(prod.fecha_vencimiento));
      if (dias !== null) return `Vence en ${dias} días`;
      return 'Producto próximo a vencer';
    }
    if (estado === 'Vencido') {
      const dias = calcDiasDesde(expiraEn(prod.fecha_vencimiento));
      if (dias !== null) return `Venció hace ${dias} días`;
      return 'Producto vencido';
    }
    if (estado === 'Agotado') {
      return 'Sin stock disponible';
    }
    return null;
  }

  function expiraEn(fechaStr) {
    if (!fechaStr) return null;
    const d = new Date(fechaStr);
    if (isNaN(d.getTime())) return null;
    return d;
  }
  function calcDiasA(date) { if (!date) return null; const ms = date.getTime() - Date.now(); return Math.max(0, Math.round(ms/86400000)); }
  function calcDiasDesde(date) { if (!date) return null; const ms = Date.now() - date.getTime(); return Math.max(0, Math.round(ms/86400000)); }

  function estadoBadge(prod) {
    const lotes = prod.lotes || [];
    const total = lotes.length;
    
    // Count states from lots
    let expiredCount = 0;
    let expiringCount = 0;
    
    lotes.forEach(l => {
        const dias = l.dias_para_vencer; 
        if (dias < 0) expiredCount++;
        else if (dias <= 90) expiringCount++;
    });
    
    // 1. Tooltip logic
    let tooltip = '';
    if (total > 0) {
        tooltip = lotes.map(l => {
            const d = l.dias_para_vencer;
            let s = 'Normal';
            if (d < 0) s = 'Vencido';
            else if (d <= 90) s = 'Por vencer';
            return `Lote: ${l.lote} (${s}) - Vence: ${formatFecha(l.vencimiento)}`;
        }).join('\n');
    } else {
        tooltip = getEstadoTooltip(prod);
    }
    const tooltipAttr = tooltip ? ` data-tooltip="${tooltip}"` : '';

    // 2. Smart Label Logic
    let badgeHtml = '';
    
    if (total === 0) {
        // Fallback to product state
        const map = {
            'Normal': 'estado-normal',
            'Bajo stock': 'estado-bajo-stock',
            'Por vencer': 'estado-por-vencer',
            'Por Vencer': 'estado-por-vencer',
            'Vencido': 'estado-vencido',
            'Agotado': 'estado-agotado'
        };
        const st = prod.estado || 'Normal';
        const cls = map[st] || 'estado-normal';
        badgeHtml = `<span class="estado-badge ${cls}"${tooltipAttr}>${st}</span>`;
    } else {
        // Has lots
        const loteLabel = total === 1 ? 'Lote' : 'Lotes';
        
        if (expiredCount > 0 && expiringCount > 0) {
            // Mixed: Expired + Expiring
            const totalAlerts = expiredCount + expiringCount;
            // Prioritize Red for mixed alerts containing expired items
            badgeHtml = `<span class="estado-badge estado-vencido"${tooltipAttr}>${total} ${loteLabel} (${totalAlerts} Alertas)</span>`;
        } else if (expiredCount > 0) {
             badgeHtml = `<span class="estado-badge estado-vencido"${tooltipAttr}>${total} ${loteLabel} (${expiredCount} Vencido${expiredCount>1?'s':''})</span>`;
        } else if (expiringCount > 0) {
             badgeHtml = `<span class="estado-badge estado-por-vencer"${tooltipAttr}>${total} ${loteLabel} (${expiringCount} Por Vencer)</span>`;
        } else {
             // Si todo está normal, mostrar solo "Normal"
             badgeHtml = `<span class="estado-badge estado-normal"${tooltipAttr}>Normal</span>`;
        }
    }

    return `<div class="relative inline-block">${badgeHtml}</div>`;
  }

  function formatFecha(str) {
    if (!str) return 'N/A';
    const d = new Date(str);
    if (isNaN(d.getTime())) return 'N/A';
    return d.toLocaleDateString('es-PE');
  }

  function chipStock(stock, minimo) {
    const s = Number(stock || 0);
    const m = Number(minimo || 0);
    
    let levelClass = 'high';
    let icon = ''; // Sin icono por defecto (Normal)
    
    if (s === 0) {
      levelClass = 'empty';
      icon = 'solar:box-cross-bold-duotone';
    } else if (s <= m) {
      levelClass = 'low';
      icon = 'solar:danger-triangle-bold-duotone';
    } else if (s <= m * 1.5) {
      levelClass = 'medium';
      icon = 'solar:box-bold-duotone';
    }

    const iconHtml = icon ? `<iconify-icon icon="${icon}"></iconify-icon>` : '';

    return `
      <div class="stock-chip ${levelClass}">
        ${iconHtml}
        <span class="stock-units">${s}</span>
      </div>
    `;
  }

  function ubicacionBadge(prod) {
    const total = prod.total_ubicaciones || 0;
    const sin = prod.tiene_stock_sin_ubicar || false;
    const sinCant = prod.stock_sin_ubicar || 0;
    if (total > 1) return `<div class="ubicacion-badge multiple"><iconify-icon icon="solar:buildings-2-bold-duotone"></iconify-icon><span>${total} ubicaciones</span></div>`;
    if (total === 1 && !sin) return `<div class="ubicacion-badge ubicado"><iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon><span>Ubicado</span></div>`;
    if (total >= 1 && sin) return `<div class="ubicacion-badge multiple"><iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon><span>Parcial (${sinCant} sin ubicar)</span></div>`;
    return `<div class="ubicacion-badge sin-ubicar"><iconify-icon icon="mdi:map-marker-off-outline"></iconify-icon><span>Sin ubicar</span></div>`;
  }

  function presentacionesBadge(prod) {
    const presentaciones = prod.presentaciones || [];
    const total = presentaciones.length;
    
    if (total === 0) {
      return `
        <div class="presentacion-pill single none">
          <iconify-icon icon="solar:box-minimalistic-broken" style="font-size:1.1rem;"></iconify-icon>
          <span>Sin datos</span>
        </div>`;
    }
    
    if (total === 1) {
      const pres = presentaciones[0];
      return `
        <div class="presentacion-pill single unit">
          <iconify-icon icon="solar:box-bold-duotone" style="font-size:1.1rem;"></iconify-icon>
          <div class="flex flex-col leading-tight">
            <span>${pres.nombre_presentacion}</span>
            <small style="font-size:10px; opacity:0.7; font-weight:600;">1 unidad</small>
          </div>
        </div>`;
    }
    
    const primerasPresentaciones = presentaciones.slice(0, 2).map(p => 
      `${p.nombre_presentacion}`
    ).join(', ');
    
    return `
      <div class="presentacion-pill multiple" onclick="verPresentacionesDeProducto('${prod.id}')">
        <iconify-icon icon="solar:boxes-bold-duotone" style="font-size:1.1rem;"></iconify-icon>
        <div class="flex flex-col leading-tight">
          <span>${total} Pres.</span>
          <small style="font-size:10px; opacity:0.8; font-weight:600;">${primerasPresentaciones}${total > 2 ? '...' : ''}</small>
        </div>
        <iconify-icon icon="solar:alt-arrow-right-bold-duotone" style="margin-left:auto; font-size:12px; opacity:0.5;"></iconify-icon>
      </div>`;
  }

  // Función global para ver presentaciones - Modal limpio estilo "Detalle de Lotes"
  window.verPresentacionesDeProducto = function(id) {
    const productos = window.boticaLastProducts || [];
    const prod = productos.find(p => String(p.id) === String(id));
    
    if (!prod) {
      Swal.fire('Error', 'No se encontró información del producto', 'error');
      return;
    }
    
    const presentaciones = prod.presentaciones || [];
    if (presentaciones.length === 0) {
      Swal.fire('Info', 'Este producto no tiene presentaciones registradas', 'info');
      return;
    }
    
    const rows = presentaciones.map(pres => {
        const precio = pres.precio_venta_presentacion ? `S/ ${parseFloat(pres.precio_venta_presentacion).toFixed(2)}` : '-';
        return `
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:14px 20px; font-weight:600; color:#1f2937;">${pres.nombre_presentacion}</td>
                <td style="padding:14px 20px; text-align:center;">
                    <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:9999px; font-size:0.75rem; font-weight:600; background-color:#f3f4f6; color:#4b5563;">
                        ${pres.unidades_por_presentacion} ${pres.unidades_por_presentacion === 1 ? 'uni' : 'unis'}
                    </span>
                </td>
                <td style="padding:14px 20px; text-align:right; font-weight:700; color:#16a34a; font-size:1rem;">${precio}</td>
            </tr>
        `;
    }).join('');

    Swal.fire({
        title: '',
        html: `
        <style>
            .swal2-popup.swal-popup-detail{padding:0 !important;border-radius:14px;}
            .swal2-popup.swal-popup-detail .swal2-title{display:none !important;margin:0 !important;padding:0 !important;}
            .swal2-popup.swal-popup-detail .swal2-html-container{margin:0 !important;padding:0 !important; width:100% !important; max-width:none !important;}
            .swal2-popup.swal-popup-detail .swal2-close{display:none !important}
            .detail-header-pres{background:#f5f3ff; border-bottom:1px solid #ddd6fe; border-top-left-radius:14px; border-top-right-radius:14px; box-shadow:0 2px 6px rgba(0,0,0,0.04); padding:18px 24px; display:flex; align-items:center; gap:14px; width:100%; position:relative;}
            .detail-close-btn{position:absolute; right:16px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#6b7280; font-size:26px; line-height:1; cursor:pointer; font-weight:400}
            .detail-close-btn:hover{color:#1f2937}
        </style>
        <div style="width:100%; margin:0; box-sizing:border-box;">
            <div class="detail-header-pres" style="position:sticky; top:0; z-index:5;">
                <div style="background:#ddd6fe; padding:10px; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
                    <i class="fas fa-boxes" style="font-size:20px; color:#7c3aed;"></i>
                </div>
                <div style="text-align:left;">
                    <span style="color:#1f2937; font-weight:800; font-size:1.2rem; display:block; letter-spacing:-0.01em;">Presentaciones de ${prod.nombre}</span>
                    <span style="color:#7c3aed; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Gestión de presentaciones del producto</span>
                </div>
                <button type="button" class="detail-close-btn" onclick="Swal.close()" aria-label="Cerrar">×</button>
            </div>
            
            <div style="padding:24px; box-sizing:border-box; max-height:70vh; overflow-y:auto;">
                <div style="overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <table style="width:100%; border-collapse:collapse; background:white;">
                        <thead>
                            <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                <th style="text-align:left; padding:12px 20px; color:#6b7280; font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Presentación</th>
                                <th style="text-align:center; padding:12px 20px; color:#6b7280; font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Unidades</th>
                                <th style="text-align:right; padding:12px 20px; color:#6b7280; font-weight:700; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding:12px 24px; background:#f9fafb; border-top:1px solid #e5e7eb; border-bottom-left-radius:14px; border-bottom-right-radius:14px; display:flex; justify-content:center; align-items:center; gap:8px;">
                <i class="fas fa-info-circle" style="color:#9ca3af; font-size:14px;"></i>
                <span style="color:#6b7280; font-size:0.8rem; font-weight:500;">Total de presentaciones: <b style="color:#4b5563;">${presentaciones.length}</b></span>
            </div>
        </div>
        `,
        width: '750px',
        showConfirmButton: false,
        showCloseButton: false,
        padding: 0,
        customClass: {
            popup: 'swal-popup-detail'
        }
    });
  };

  function lotesBadge(prod) {
    const total = prod.total_lotes || 0;
    const lotes = prod.lotes || [];
    
    if (total === 0) {
      return `
        <div class="lote-pill empty">
          <iconify-icon icon="solar:calendar-broken"></iconify-icon>
          <span>Sin lotes</span>
        </div>`;
    }

    const proximo = lotes[0];
    if (!proximo || !proximo.vencimiento) {
        return `<div class="lote-pill normal"><span>${total} Lotes</span></div>`;
    }

    const vencimientoStr = formatFecha(proximo.vencimiento);
    const dias = proximo.dias_para_vencer;
    
    let statusClass = 'normal';
    let icon = 'solar:calendar-bold-duotone';
    
    if (dias < 0) {
      statusClass = 'expired';
      icon = 'solar:calendar-mark-bold-duotone';
    } else if (dias <= 90) {
      statusClass = 'warning';
      icon = 'solar:calendar-minimalistic-bold-duotone';
    }
    
    if (total === 1) {
      return `
        <div class="lote-pill single ${statusClass}">
          <iconify-icon icon="${icon}"></iconify-icon>
          <div class="lote-info-mini">
            <span class="lote-code-mini">${proximo.lote || 'S/N'}</span>
            <span class="lote-date-mini">${vencimientoStr}</span>
          </div>
        </div>`;
    }

    return `
      <div class="lote-pill multiple ${statusClass}" onclick="verLotesDeProducto('${prod.id}')">
        <iconify-icon icon="${icon}"></iconify-icon>
        <div class="lote-info-mini">
          <span class="lote-count-mini">${total} Lotes</span>
          <span class="lote-next-mini">Próx: ${vencimientoStr}</span>
        </div>
        <iconify-icon icon="solar:alt-arrow-right-bold-duotone" class="lote-arrow"></iconify-icon>
      </div>`;
  }

  // Función global para ver lotes (Diseño Mejorado y Ajustado al estilo Historial)
  window.verLotesDeProducto = function(id) {
      const productos = window.boticaLastProducts || [];
      const prod = productos.find(p => String(p.id) === String(id));
      
      if (!prod) { Swal.fire('Error', 'No se encontró información', 'error'); return; }
      
      const lotes = prod.lotes || [];
      if (lotes.length === 0) { Swal.fire('Info', 'Sin lotes registrados', 'info'); return; }
      
      // Generar filas de la tabla
      const rows = lotes.map(l => {
          const rawDias = l.dias_para_vencer;
          const dias = Math.round(rawDias); // Sin decimales
          
          let badgeStyle = 'background-color:#f0fdf4; color:#166534; border:1px solid #bbf7d0;'; // Normal
          let badgeText = 'Normal';
          let rowBg = '#ffffff';
          let diasText = '';
          let diasColor = '#4b5563';

          if (dias < 0) {
              const diasVencido = Math.abs(dias);
              diasText = `Venció hace ${diasVencido} día${diasVencido !== 1 ? 's' : ''}`;
              badgeStyle = 'background-color:#fef2f2; color:#991b1b; border:1px solid #fecaca;'; // Vencido
              badgeText = 'Vencido';
              rowBg = '#fff5f5';
              diasColor = '#dc2626';
          } else if (dias === 0) {
              diasText = 'Vence hoy';
              badgeStyle = 'background-color:#fffbeb; color:#92400e; border:1px solid #fde68a;'; // Por vencer
              badgeText = 'Por Vencer';
              rowBg = '#fffbeb';
              diasColor = '#d97706';
          } else if (dias <= 90) {
              diasText = `Vence en ${dias} días`;
              badgeStyle = 'background-color:#fffbeb; color:#92400e; border:1px solid #fde68a;'; // Por vencer
              badgeText = 'Por Vencer';
              rowBg = '#fffbeb';
              diasColor = '#d97706';
          } else {
              diasText = `Vence en ${dias} días`;
          }
          
          let ubicacionBadge = '';
          if (!l.ubicacion || l.ubicacion === 'Sin asignar') {
             ubicacionBadge = `
                <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:600; background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb;">
                    <svg style="width:12px; height:12px; margin-right:4px; color:#9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Sin asignar
                </span>
             `;
          } else {
             ubicacionBadge = `
                <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:600; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                    <svg style="width:12px; height:12px; margin-right:4px; color:#10b981;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    ${l.ubicacion}
                </span>
             `;
          }
          
          return `
            <tr style="background:${rowBg}; border-bottom:1px solid #f3f4f6;">
                <td style="padding:12px 16px; font-weight:600; color:#1f2937;">${l.lote || 'S/N'}</td>
                <td style="padding:12px 16px;">
                    <div style="font-weight:600; color:#374151;">${formatFecha(l.vencimiento)}</div>
                    <div style="font-size:0.75rem; color:${diasColor}; margin-top:2px;">${diasText}</div>
                </td>
                <td style="padding:12px 16px; text-align:right; font-family:monospace; font-weight:600; color:#374151; font-size:0.95rem;">${l.cantidad}</td>
                <td style="padding:12px 16px; text-align:center;">
                    <span style="display:inline-block; padding:4px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600; ${badgeStyle}">${badgeText}</span>
                </td>
                <td style="padding:12px 16px; text-align:center;">
                    <button class="btn-eliminar-lote-swal" data-id="${l.id}" style="border:none; background:transparent; cursor:pointer; color:#ef4444; padding:4px; border-radius:4px; transition:all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'" title="Dar de baja">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </td>
            </tr>
          `;
      }).join('');
      
      Swal.fire({
          title: '',
          html: `
          <style>
              .swal2-popup.swal-popup-detail{padding:0 !important;border-radius:14px;}
              .swal2-popup.swal-popup-detail .swal2-title{display:none !important;margin:0 !important;padding:0 !important;}
              .swal2-popup.swal-popup-detail .swal2-html-container{margin:0 !important;padding:0 !important; width:100% !important; max-width:none !important;}
              .swal2-popup.swal-popup-detail .swal2-close{display:none !important}
              .detail-header{background:#eaf1ff; border-bottom:1px solid #dbe7ff; border-top-left-radius:14px; border-top-right-radius:14px; box-shadow:0 2px 6px rgba(0,0,0,0.06); padding:16px 20px; display:flex; align-items:center; gap:12px; width:100%; position:relative;}
              .detail-close-btn{position:absolute; right:12px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#374151; font-size:24px; line-height:1; cursor:pointer; font-weight:700}
              .detail-close-btn:hover{color:#1f2937}
          </style>
          <div style="width:100%; margin:0; box-sizing:border-box;">
              <div class="detail-header" style="position:sticky; top:0; z-index:5;">
                  <div style="background:#dbe7ff; padding:8px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                      <svg xmlns="http://www.w3.org/2000/svg" style="width:24px; height:24px; color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                  </div>
                  <div>
                      <span style="color:#1f2937; font-weight:700; font-size:1.18rem; display:block;">Detalle de Lotes</span>
                      <span style="color:#4b5563; font-size:0.85rem; font-weight:500;">Gestión de inventario FEFO</span>
                  </div>
                  <button type="button" class="detail-close-btn" onclick="Swal.close()" aria-label="Cerrar">×</button>
              </div>
              
              <div class="detail-scroll" style="padding:20px 24px; box-sizing:border-box; max-height:70vh; overflow-y:auto;">
                  
                  <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:20px; display:flex; flex-direction:column; gap:4px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                       <div style="color:#6b7280; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Producto Seleccionado</div>
                       <div style="color:#1f2937; font-weight:700; font-size:1.25rem;">${prod.nombre}</div>
                  </div>
  
                  <div style="overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                      <table style="width:100%; border-collapse:collapse;">
                          <thead>
                              <tr style="background:#f9fafb;">
                                  <th style="text-align:left; padding:12px 16px; color:#6b7280; font-weight:600; font-size:0.85rem; border-bottom:1px solid #e5e7eb;">Lote</th>
                                  <th style="text-align:left; padding:12px 16px; color:#6b7280; font-weight:600; font-size:0.85rem; border-bottom:1px solid #e5e7eb;">Vencimiento</th>
                                  <th style="text-align:right; padding:12px 16px; color:#6b7280; font-weight:600; font-size:0.85rem; border-bottom:1px solid #e5e7eb;">Cant.</th>
                                  <th style="text-align:center; padding:12px 16px; color:#6b7280; font-weight:600; font-size:0.85rem; border-bottom:1px solid #e5e7eb;">Estado</th>
                                  <th style="text-align:center; padding:12px 16px; color:#6b7280; font-weight:600; font-size:0.85rem; border-bottom:1px solid #e5e7eb;">Acciones</th>
                              </tr>
                          </thead>
                          <tbody style="background:white;">
                              ${rows}
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
          `,
          width: '850px',
          showConfirmButton: false,
          showCloseButton: false,
          padding: 0,
          didOpen: () => {
              const popup = Swal.getPopup();
              popup.querySelectorAll('.btn-eliminar-lote-swal').forEach(btn => {
                  btn.addEventListener('click', () => {
                      const id = btn.dataset.id;
                      eliminarLote(id);
                  });
              });
          },
          customClass: {
              popup: 'swal-popup-detail'
          }
      });
  };


  function updateActiveFilters(search, estado, total) {
    const container = document.getElementById('activeFilters');
    if (!container) return;
    
    container.innerHTML = '';
    
    const hasSearch = search && search.trim() !== '';
    const hasStatus = estado && estado !== 'todos';

    if (hasSearch || hasStatus) {
      container.classList.add('has-filters');
    } else {
      container.classList.remove('has-filters');
      return;
    }
    
    if (hasSearch) {
      const tag = document.createElement('div');
      tag.className = 'filter-tag search-tag';
      tag.innerHTML = `
        <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
        <span>Búsqueda: ${search}</span>
        <iconify-icon icon="solar:close-circle-bold-duotone" class="remove-filter" onclick="clearSearch()"></iconify-icon>
      `;
      container.appendChild(tag);
    }
    
    if (hasStatus) {
      const tag = document.createElement('div');
      // Mapear clase de color según estado
      const statusMap = {
        'Normal': 'status-normal',
        'Bajo stock': 'status-bajo-stock',
        'Por vencer': 'status-por-vencer',
        'Por Vencer': 'status-por-vencer',
        'Vencido': 'status-vencido',
        'Agotado': 'status-agotado'
      };
      const statusClass = statusMap[estado] || '';
      
      tag.className = `filter-tag ${statusClass}`;
      tag.innerHTML = `
        <iconify-icon icon="solar:filter-bold-duotone"></iconify-icon>
        <span>Estado: ${estado} <b style="margin-left:4px;">(${total || 0})</b></span>
        <iconify-icon icon="solar:close-circle-bold-duotone" class="remove-filter" onclick="clearEstado()"></iconify-icon>
      `;
      container.appendChild(tag);
    }
  }

  window.clearSearch = () => {
    const searchEl = document.getElementById('buscarProductoBotica');
    if (searchEl) {
      searchEl.value = '';
      searchEl.dispatchEvent(new Event('input'));
    }
  };

  window.clearEstado = () => {
    const estadoEl = document.getElementById('estadoBotica');
    if (estadoEl) {
      estadoEl.value = 'todos';
      estadoEl.dispatchEvent(new Event('change'));
    }
  };

  async function load() {
    const perPage = perPageEl.value || 10;
    let estado = estadoEl.value || 'todos';
    try {
      const params = new URLSearchParams(window.location.search);
      const urlEstado = params.get('estado');
      const legacyFilter = params.get('filter');
      if (urlEstado) {
        estadoEl.value = urlEstado;
        estado = urlEstado;
      } else if (legacyFilter) {
        const map = {
          'stock_bajo': 'Bajo stock',
          'agotados': 'Agotado',
          'por_vencer': 'Por Vencer',
          'vencidos': 'Vencido',
          'normal': 'Normal'
        };
        const mapped = map[legacyFilter] || 'todos';
        estadoEl.value = mapped;
        estado = mapped;
      }
    } catch (_) {}
    const search = (searchEl.value || '').trim();

    const url = new URL(window.APP_PRODUCTS_AJAX || '/inventario/productos/ajax', window.location.origin);
    url.searchParams.append('search', search);
    url.searchParams.append('estado', estado);
    url.searchParams.append('per_page', perPage);
    url.searchParams.append('page', page);

    // Mostrar skeleton de carga
    const skeleton = document.getElementById('productosBoticaSkeleton');
    if (skeleton) {
      skeleton.style.display = 'block';
      skeleton.innerHTML = Array.from({length: 6}).map(()=>
        `<div class="skeleton-row" style="grid-template-columns: 1.5fr 1fr 1fr 0.8fr 1fr 1fr 0.8fr;">
           <div class="flex items-center gap-3">
              <div class="skeleton-bar" style="width: 44px; height: 44px; border-radius: 12px;"></div>
              <div class="flex flex-col gap-2 flex-1">
                <div class="skeleton-bar" style="width: 80%; height: 16px;"></div>
                <div class="skeleton-bar" style="width: 50%; height: 12px;"></div>
              </div>
           </div>
           <div class="skeleton-bar" style="width: 85%; height: 38px; border-radius: 10px;"></div>
           <div class="flex flex-col gap-2">
             <div class="skeleton-bar" style="width: 70%; height: 18px;"></div>
             <div class="skeleton-bar" style="width: 40%; height: 12px;"></div>
           </div>
           <div class="skeleton-bar" style="width: 60px; height: 32px; border-radius: 9999px; justify-self: center;"></div>
           <div class="skeleton-bar" style="width: 100%; height: 38px; border-radius: 10px;"></div>
           <div class="skeleton-bar" style="width: 100px; height: 28px; border-radius: 9999px; justify-self: center;"></div>
           <div class="flex gap-2 justify-center">
             <div class="skeleton-bar" style="width: 32px; height: 32px; border-radius: 50%;"></div>
             <div class="skeleton-bar" style="width: 32px; height: 32px; border-radius: 50%;"></div>
             <div class="skeleton-bar" style="width: 32px; height: 32px; border-radius: 50%;"></div>
           </div>
        </div>`
      ).join('');
    }
    tbody.innerHTML = '';
    try {
      console.log('Haciendo fetch...');
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      console.log('Respuesta recibida:', res.status, res.ok);
      
      if (!res.ok) throw new Error('Error al cargar productos');
      const data = await res.json();
      console.log('Datos recibidos:', data);
      
      // Guardar última respuesta para exportar
      window.boticaLastResponse = data;
      window.boticaLastProducts = Array.isArray(data.data) ? data.data : [];
      
      updateActiveFilters(search, estado, data.total);
      render(data);
    } catch (e) {
      console.error('Error en load():', e);
      tbody.innerHTML = `<tr><td colspan="8" style="padding:24px;text-align:center;color:#dc2626;">No se pudo cargar productos</td></tr>`;
      console.error(e);
    } finally {
      if (skeleton) skeleton.style.display = 'none';
    }
  }

  // Exponer función de recarga para uso externo (guardar/editar)
  window.loadProducts = load;

  function formatMoney(n) {
    const num = Number(n || 0);
    return num.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function render(resp) {
    const productos = resp.data || [];
    const defaultImageUrl = window.APP_DEFAULT_IMAGE || '/assets/images/default-product.svg';
    if (!productos.length) {
      tbody.innerHTML = `<tr><td colspan="8" style="padding:24px;text-align:center;color:#64748b;">No se encontraron productos</td></tr>`;
    } else {
      console.log('Renderizando', productos.length, 'productos');
      tbody.innerHTML = productos.map((p, idx) => {
        const img = p.imagen_url || defaultImageUrl;
        
        // Verificar si tiene lotes por vencer para resaltar la fila
        const lotes = p.lotes || [];
        const tieneAlertas = lotes.some(l => l.dias_para_vencer <= 90);
        const rowHighlightClass = tieneAlertas ? 'row-alert-vencimiento' : '';

        return `<tr data-id="${p.id}" class="${rowHighlightClass}">
          <td>
            <div class="flex items-center gap-3">
              <div class="product-img-wrapper">
                <img data-src="${img}" src="${defaultImageUrl}" width="40" height="40" loading="lazy" decoding="async" fetchpriority="${idx < 6 ? 'high' : 'low'}" class="w-12 h-12 rounded-xl object-cover shadow-sm border border-gray-200 bg-white img-loading product-img-zoom" onerror="this.src='${defaultImageUrl}'"/>
              </div>
              <div class="product-info-cell">
                <h6 class="product-name-highlight">${p.nombre}</h6>
                <span class="product-concentration-sub">${p.concentracion || 'Sin concentración'}</span>
              </div>
            </div>
          </td>
          <td class="text-left">${presentacionesBadge(p)}</td>
          <td class="text-left">
            <div class="price-display-wrapper">
              <span class="price-main">S/ ${formatMoney(p.precio_venta)}</span>
              <span class="price-secondary">Costo: S/ ${formatMoney(p.precio_compra)}</span>
            </div>
          </td>
          <td class="text-center">${chipStock(p.stock_actual, p.stock_minimo)}</td>
          <td class="text-left">${lotesBadge(p)}</td>
          <td class="text-center">${estadoBadge(p)}</td>
          <td class="text-center acciones-cell">
            <button class="btn-view" data-id="${p.id}" title="Ver detalles"><iconify-icon icon="heroicons:eye"></iconify-icon></button>
            <button class="btn-edit" data-id="${p.id}" title="Editar"><iconify-icon icon="heroicons:pencil"></iconify-icon></button>
            <button class="btn-delete" data-id="${p.id}" title="Eliminar"><iconify-icon icon="heroicons:trash"></iconify-icon></button>
          </td>
        </tr>`;
      }).join('');
      initLazyImages();
    }
    info.textContent = `Mostrando ${resp.from || 0} a ${resp.to || 0} de ${resp.total || 0} productos`;

    // Render paginación estilo historial
    const controls = document.getElementById('productos-botica-pagination-controls');
    if (controls) {
      const current = Number(resp.current_page || 1);
      const last = Number(resp.last_page || 1);
      const isFirst = current <= 1;
      const isLast = current >= last;
      const btn = (label, disabled, action) => {
        if (disabled) return `<span class="historial-pagination-btn historial-pagination-btn-disabled">${label}</span>`;
        return `<button class="historial-pagination-btn" data-action="${action}">${label}</button>`;
      };
      const currentBtn = `<span class="historial-pagination-btn historial-pagination-btn-current">${current}</span>`;
      controls.innerHTML = [
        btn('Primera', isFirst, 'first'),
        btn('‹ Anterior', isFirst, 'prev'),
        currentBtn,
        btn('Siguiente ›', isLast, 'next'),
        btn('Última', isLast, 'last')
      ].join('');
      controls.querySelectorAll('button.historial-pagination-btn').forEach(el => {
        el.addEventListener('click', () => {
          const action = el.dataset.action;
          if (action === 'first') page = 1;
          else if (action === 'prev') page = Math.max(1, current - 1);
          else if (action === 'next') page = Math.min(last, current + 1);
          else if (action === 'last') page = last;
          load();
        });
      });
    }
  }

  // Wire up actions via delegation (robusto ante re-render) - MOVIDO FUERA DE RENDER
  tbody.addEventListener('click', (e) => {
    const btnView = e.target.closest('.btn-view');
    if (btnView) {
      const id = btnView.dataset.id || btnView.closest('tr')?.dataset.id;
      if (id) abrirDetalles(id);
      return;
    }
    const btnEdit = e.target.closest('.btn-edit');
    if (btnEdit) {
      const id = btnEdit.dataset.id || btnEdit.closest('tr')?.dataset.id;
      if (id) abrirModalEdicion(id); else console.error('No se encontró el id del producto para editar');
      return;
    }
    const btnDelete = e.target.closest('.btn-delete');
    if (btnDelete) {
      const id = btnDelete.dataset.id || btnDelete.closest('tr')?.dataset.id;
      if (id) eliminarProductoBotica(id);
    }
  });

  function initLazyImages() {
    const imgs = tbody.querySelectorAll('img[data-src]');
    const defaultImg = window.APP_DEFAULT_IMAGE || '/assets/images/default-product.svg';
    const load = (img) => {
      const src = img.getAttribute('data-src');
      if (!src || img.dataset.loaded) return;
      img.onload = () => { img.classList.remove('img-loading'); img.dataset.loaded = '1'; };
      img.onerror = () => { img.src = defaultImg; img.classList.remove('img-loading'); img.dataset.loaded = '1'; };
      img.src = src;
    };
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            load(e.target);
            io.unobserve(e.target);
          }
        });
      }, { root: null, rootMargin: '120px', threshold: 0.1 });
      imgs.forEach((img, i) => {
        io.observe(img);
        // Pre-cargar primeros visibles para percepción rápida
        if (i < 6) { load(img); io.unobserve(img); }
      });
    } else {
      imgs.forEach(load);
    }
  }

  // Forzar cierre de cualquier modal previo antes de abrir otro
  function resetModals() {
    const ids = ['modalEditar','modalDetallesBotica','modalAgregar'];
    ids.forEach((id)=>{
      const m = document.getElementById(id);
      if (m) { m.style.display='none'; m.classList.add('hidden'); m.classList.remove('flex'); }
    });
    document.body.classList.remove('modal-open');
  }

  // Variable global para el producto actual
  let currentProductId = null;
  let currentSelectedLote = null;

  async function abrirDetalles(id, loteSeleccionado = null) {
    try {
      resetModals();
      showLoading('Cargando datos...');
      currentProductId = id;
      const res = await fetch(`/inventario/producto/${id}?t=${Date.now()}`);
      const data = await res.json();
      if (!data.success) throw new Error('No se pudo obtener detalles');
      const p = data.data;
      
      console.log('Detalles del producto:', p);
      
      // Si hay múltiples lotes y no se ha seleccionado uno, mostrar selector
      const lotes = p.lotes_detalle || [];
      if (lotes.length > 1 && !loteSeleccionado) {
        hideLoading();
        mostrarSelectorLotes(lotes, p.nombre, (lote) => {
          if (lote) {
            window.abrirDetalles(id, lote);
          }
        }, 'Ver Detalles - Seleccionar Lote');
        return;
      }
      
      // Si se seleccionó un lote específico, usar sus datos
      currentSelectedLote = loteSeleccionado;
      let datosLote = loteSeleccionado || (lotes.length === 1 ? lotes[0] : null);
      
      // Resolver proveedor: si no viene nombre, intentar obtenerlo por ID
      let proveedorNombre = p.proveedor || '';
      let proveedorIdActual = p.proveedor_id;
      
      // Si hay un lote seleccionado con proveedor, usar ese
      if (datosLote && datosLote.proveedor_id) {
        proveedorIdActual = datosLote.proveedor_id;
        proveedorNombre = datosLote.proveedor || '';
      }
      
      if (!proveedorNombre && proveedorIdActual) {
        try {
          let rp = await fetch(`/api/compras/proveedor/${proveedorIdActual}`);
          let dp = await rp.json();
          if (dp && dp.success && dp.data) {
            proveedorNombre = dp.data.razon_social || dp.data.nombre || '';
          } else {
            // Fallback: buscar en lista completa
            rp = await fetch('/compras/proveedores/api');
            dp = await rp.json();
            if (dp && dp.success && Array.isArray(dp.data)) {
              const found = dp.data.find(x => String(x.id) === String(proveedorIdActual));
              if (found) proveedorNombre = found.razon_social || found.nombre || '';
            }
          }
        } catch(e) { console.error('Error cargando proveedor:', e); }
      }
      
      // Usar datos del lote seleccionado si existe
      // IMPORTANTE: Solo usar cantidad del lote si fue EXPLÍCITAMENTE seleccionado por el usuario
      const stockActual = loteSeleccionado ? loteSeleccionado.cantidad : p.stock_actual;
      const loteCode = datosLote ? datosLote.lote : p.lote;
      const fechaVenc = datosLote ? datosLote.fecha_vencimiento : p.fecha_vencimiento;
      const precioCompra = datosLote && datosLote.precio_compra_lote ? datosLote.precio_compra_lote : p.precio_compra;
      const precioVenta = datosLote && datosLote.precio_venta_lote ? datosLote.precio_venta_lote : p.precio_venta;
      
      // Poblar campos del modal detalles (lectura)
      const map = {
        'det-id': p.id,
        'det-nombre': p.nombre,
        'det-marca': p.marca,
        'det-categoria': p.categoria,
        'det-concentracion': p.concentracion,
        'det-lote': loteCode,
        'det-codigo_barras': p.codigo_barras,
        'det-proveedor': proveedorNombre,
        'det-stock_actual': stockActual,
        'det-stock_minimo': p.stock_minimo,
        'det-precio_compra': typeof precioCompra === 'number' ? precioCompra.toFixed(2) : precioCompra,
        'det-precio_venta': typeof precioVenta === 'number' ? precioVenta.toFixed(2) : precioVenta,
        'det-fecha_vencimiento': formatFecha(fechaVenc)
      };
      Object.entries(map).forEach(([id,val])=>{ const el=document.getElementById(id); if (el) el.value = val || ''; });
      
      // Poblar el selector de lotes dentro del modal de detalles
      const loteSelector = document.getElementById('det-lote-selector');
      if (loteSelector) {
        if (lotes.length > 0) {
            loteSelector.innerHTML = lotes.map(l => `<option value="${l.id}" ${datosLote && String(l.id) === String(datosLote.id) ? 'selected' : ''}>${l.lote} (Stock: ${l.cantidad})</option>`).join('');
            loteSelector.style.display = 'block';
            loteSelector.previousElementSibling.style.display = 'block'; // El label "Lote:"
            
            // Eliminar listeners previos
            const newSelector = loteSelector.cloneNode(true);
            loteSelector.parentNode.replaceChild(newSelector, loteSelector);
            
            newSelector.addEventListener('change', (e) => {
                const selectedLoteId = e.target.value;
                const selectedLote = lotes.find(l => String(l.id) === String(selectedLoteId));
                if (selectedLote) {
                    abrirDetalles(id, selectedLote);
                }
            });
        } else {
            loteSelector.innerHTML = '<option value="">Sin lotes</option>';
            loteSelector.style.display = 'none';
            if (loteSelector.previousElementSibling) loteSelector.previousElementSibling.style.display = 'none';
        }
      }
      // Renderizar presentaciones del producto (considerando el lote si fue seleccionado)
      const presentacionesList = document.getElementById('det-presentaciones-list');
      if (presentacionesList && p.presentaciones && p.presentaciones.length > 0) {
        // Mapear presentaciones del producto con los datos del lote si existen
        const presentacionesAMostrar = p.presentaciones.map(pres => {
          // Buscar si este lote tiene un precio específico para esta presentación
          let precioAMostrar = pres.precio_venta_presentacion;
          let unidadesAMostrar = pres.unidades_por_presentacion;
          
          if (loteSeleccionado && loteSeleccionado.presentaciones_lote) {
            const presLote = loteSeleccionado.presentaciones_lote.find(pl => 
              String(pl.producto_presentacion_id) === String(pres.id)
            );
            if (presLote) {
              precioAMostrar = presLote.precio_venta;
              if (presLote.unidades_por_presentacion) {
                unidadesAMostrar = presLote.unidades_por_presentacion;
              }
            }
          }
          
          return {
            ...pres,
            precio_final: precioAMostrar,
            unidades_final: unidadesAMostrar
          };
        });

        presentacionesList.innerHTML = `
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <table class="w-full text-sm">
              <thead class="bg-[#eff6ff] border-b border-[#dbeafe]">
                <tr>
                  <th class="px-4 py-3 text-left font-bold text-[#1e40af] uppercase tracking-wider" style="font-size: 0.7rem;">Presentación</th>
                  <th class="px-4 py-3 text-center font-bold text-[#1e40af] uppercase tracking-wider" style="font-size: 0.7rem;">Unidades</th>
                  <th class="px-4 py-3 text-center font-bold text-[#1e40af] uppercase tracking-wider" style="font-size: 0.7rem;">Precio Venta</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                ${presentacionesAMostrar.map(pres => `
                  <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-4 font-semibold text-gray-800">${pres.nombre_presentacion}</td>
                    <td class="px-4 py-4 text-center text-gray-600">
                        <span class="px-2 py-1 bg-gray-100 rounded-md text-xs font-bold">${pres.unidades_final} ${pres.unidades_final === 1 ? 'unidad' : 'unidades'}</span>
                    </td>
                    <td class="px-4 py-4 text-center font-bold text-green-600" style="font-size: 1rem;">S/ ${parseFloat(pres.precio_final || 0).toFixed(2)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
      } else {
        presentacionesList.innerHTML = '<div class="text-sm text-gray-500 italic text-center py-4 bg-gray-50 rounded-lg border border-gray-200">Este producto no tiene presentaciones registradas</div>';
      }
      
      // Verificar historial del producto y mostrar/ocultar botón
      if (window.verificarHistorialProducto && p.id) {
        window.verificarHistorialProducto(p.id);
      }

      // Renderizar lista de lotes FEFO - solo mostrar el lote seleccionado si hay uno
      if (datosLote && lotes.length > 1) {
        renderLotesList([datosLote]);
      } else {
        renderLotesList(lotes);
      }

      const prev = document.getElementById('det-preview-container');
      const img = document.getElementById('det-preview-image');
      if (prev && img) { prev.style.display='block'; img.src = p.imagen_url || '/assets/images/default-product.svg'; img.onerror = function(){ this.src='/assets/images/default-product.svg'; } }
      mostrarModal();
    } catch (e) {
      console.error(e);
      Swal.fire('Error', 'No se pudo cargar el producto', 'error');
    } finally { hideLoading(); }
  }

  // Exponer abrirDetalles globalmente
  window.abrirDetalles = abrirDetalles;

  function renderLotesList(lotes) {
      const lotesList = document.getElementById('det-lotes-list');
      if (!lotesList) return;
      
      lotesList.innerHTML = '';
      if (lotes.length === 0) {
          lotesList.innerHTML = '<div class="text-sm text-gray-500 italic text-center py-4">No hay lotes activos registrados para este producto.</div>';
      } else {
          // Table structure for better alignment
          lotesList.innerHTML = `
            <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm bg-white">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-[#f5f3ff] border-b border-[#ddd6fe]">
                        <tr>
                            <th class="px-4 py-3 font-bold text-[#6d28d9] uppercase tracking-wider" style="font-size: 0.7rem;">Lote</th>
                            <th class="px-4 py-3 font-bold text-[#6d28d9] uppercase tracking-wider" style="font-size: 0.7rem;">Vencimiento</th>
                            <th class="px-4 py-3 font-bold text-[#6d28d9] uppercase tracking-wider text-center" style="font-size: 0.7rem;">Cant.</th>
                            <th class="px-4 py-3 font-bold text-[#6d28d9] uppercase tracking-wider text-center" style="font-size: 0.7rem;">Estado</th>
                            <th class="px-4 py-3 font-bold text-[#6d28d9] uppercase tracking-wider text-center" style="font-size: 0.7rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>
          `;
          
          const tbody = lotesList.querySelector('tbody');

          lotes.forEach(l => {
              const dias = Math.round(l.dias_para_vencer);
              let statusClass = 'estado-normal';
              let statusText = 'Normal';
              let diasText = '';
              let isQuarantine = false;

              if (l.estado_lote === 'cuarentena' || l.estado === 'cuarentena') {
                  isQuarantine = true;
                  statusText = 'En Cuarentena';
                  diasText = `Vence en ${dias} día${dias !== 1 ? 's' : ''}`;
              } else if (dias < 0) {
                  const diasVencido = Math.abs(dias);
                  diasText = `Venció hace ${diasVencido} día${diasVencido !== 1 ? 's' : ''}`;
                  statusClass = 'estado-vencido';
                  statusText = 'Vencido';
              } else if (dias === 0) {
                  diasText = 'Vence hoy';
                  statusClass = 'estado-por-vencer';
                  statusText = 'Por Vencer';
              } else if (dias <= 90) {
                  diasText = `Vence en ${dias} día${dias !== 1 ? 's' : ''}`;
                  statusClass = 'estado-por-vencer';
                  statusText = 'Por Vencer';
              } else {
                  diasText = `Vence en ${dias} día${dias !== 1 ? 's' : ''}`;
              }
              
              const row = document.createElement('tr');
              row.className = 'hover:bg-gray-50 transition-colors';
              
              // Safe strings
              const loteCode = l.lote || 'S/N';
              const vencimiento = formatFecha(l.fecha_vencimiento);
              const cantidad = l.cantidad;
              
              const statusBadgeHtml = isQuarantine 
                  ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">En Cuarentena</span>`
                  : `<span class="estado-badge ${statusClass} scale-75 origin-center text-xs whitespace-nowrap" style="padding: 0.25rem 0.75rem !important; font-size: 0.75rem !important;">${statusText}</span>`;

              row.innerHTML = `
                  <td class="px-4 py-3.5 font-bold text-gray-800 whitespace-nowrap">${loteCode}</td>
                  <td class="px-4 py-3.5 text-gray-600">
                      <div class="font-bold text-gray-800">${vencimiento}</div>
                      <div class="text-[11px] font-semibold text-gray-500 leading-tight mt-0.5">${diasText}</div>
                  </td>
                  <td class="px-4 py-3.5 text-center font-bold text-gray-800" style="font-size: 0.95rem;">${cantidad}</td>
                  <td class="px-4 py-3.5 text-center">
                      ${statusBadgeHtml}
                  </td>
                  <td class="px-4 py-3.5 text-center">
                      <div class="flex items-center justify-center gap-3">
                          <button class="flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 transition-all btn-edit-lote" type="button" title="Editar Lote">
                              <iconify-icon icon="solar:pen-new-square-bold-duotone" width="18" height="18"></iconify-icon>
                          </button>
                          <button class="flex items-center justify-center w-8 h-8 rounded-lg text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 transition-all btn-delete-lote" type="button" title="Dar de baja">
                              <iconify-icon icon="solar:trash-bin-trash-bold-duotone" width="18" height="18"></iconify-icon>
                          </button>
                      </div>
                  </td>
              `;
              
              // Bind events directly to avoid delegation complexity
              const btnEdit = row.querySelector('.btn-edit-lote');
              const btnDelete = row.querySelector('.btn-delete-lote');
              
              btnEdit.onclick = (e) => { e.preventDefault(); e.stopPropagation(); abrirModalLote(l); };
              if(btnDelete) btnDelete.onclick = (e) => { e.preventDefault(); e.stopPropagation(); eliminarLote(l.id); };
              
              tbody.appendChild(row);
          });
      }
  }

  // --- Lotes Management ---
  const modalLote = document.getElementById('modalLote');
  const formLote = document.getElementById('formLote');
  const btnAgregarLote = document.getElementById('btnAgregarLote');
  const cerrarModalLoteBtn = document.getElementById('cerrarModalLote');
  const cancelarLoteBtn = document.getElementById('cancelarLote');

  if (btnAgregarLote) {
      btnAgregarLote.addEventListener('click', () => abrirModalLote());
  }

  if (cerrarModalLoteBtn) {
      cerrarModalLoteBtn.addEventListener('click', cerrarModalLote);
  }

  if (cancelarLoteBtn) {
      cancelarLoteBtn.addEventListener('click', cerrarModalLote);
  }

  if (formLote) {
      formLote.addEventListener('submit', guardarLote);
  }

  function abrirModalLote(lote = null) {
      if (!modalLote) return;
      
      const title = document.getElementById('modalLoteTitle');
      const loteId = document.getElementById('loteId');
      const loteProductoId = document.getElementById('loteProductoId');
      const loteCodigo = document.getElementById('loteCodigo');
      const loteVencimiento = document.getElementById('loteVencimiento');
      const loteCantidad = document.getElementById('loteCantidad');

      // Set product ID
      loteProductoId.value = currentProductId;

      if (lote) {
          title.textContent = 'Editar Lote';
          loteId.value = lote.id;
          loteCodigo.value = lote.lote;
          // Format date for input date (YYYY-MM-DD)
          // lote.fecha_vencimiento might be YYYY-MM-DD HH:MM:SS or similar
          loteVencimiento.value = lote.fecha_vencimiento ? lote.fecha_vencimiento.split(' ')[0] : '';
          loteCantidad.value = lote.cantidad;
      } else {
          title.textContent = 'Nuevo Lote';
          loteId.value = '';
          loteCodigo.value = '';
          loteVencimiento.value = '';
          loteCantidad.value = '';
      }

      modalLote.style.display = 'flex';
      modalLote.classList.remove('hidden');
  }

  function cerrarModalLote() {
      if (modalLote) {
          modalLote.style.display = 'none';
          modalLote.classList.add('hidden');
      }
  }

  async function guardarLote(e) {
      e.preventDefault();
      
      const loteId = document.getElementById('loteId').value;
      const formData = new FormData(formLote);
      const data = Object.fromEntries(formData.entries());
      
      // Add product_id explicitly as it might not be in FormData if disabled or something, though here it is hidden input
      data.producto_id = document.getElementById('loteProductoId').value;

      const isEdit = !!loteId;
      const url = isEdit ? `/inventario/lotes/${loteId}` : '/inventario/lotes';
      const method = isEdit ? 'PUT' : 'POST';

      try {
          showLoading('Guardando lote...');
          const res = await fetch(url, {
              method: method,
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
              },
              body: JSON.stringify(data)
          });

          const result = await res.json();

          if (result.success) {
              Swal.fire({
                  icon: 'success',
                  title: 'Éxito',
                  text: result.message,
                  timer: 1500,
                  showConfirmButton: false
              });
              cerrarModalLote();
              // Reload details to refresh list
              abrirDetalles(currentProductId);
              // Also reload main table to update badges
              load();
          } else {
              throw new Error(result.message || 'Error al guardar');
          }
      } catch (err) {
          console.error(err);
          Swal.fire('Error', err.message, 'error');
      } finally {
          hideLoading();
      }
    }

    async function cambiarEstadoLote(lote) {
      const currentStatus = lote.estado_lote || lote.estado || 'activo';
      
      const { value: estado } = await Swal.fire({
        title: '',
        html: `
            <div style="text-align:left; margin-bottom:1.5rem;">
                <h3 style="font-size:1.25rem; font-weight:700; color:#1f2937; margin-bottom:0.25rem;">Cambiar Estado del Lote</h3>
                <p style="font-size:0.875rem; color:#6b7280;">Selecciona el nuevo estado para el lote <span style="font-weight:600; color:#111827;">${lote.lote || 'S/N'}</span></p>
            </div>
            
            <div class="relative w-full">
                <select id="swal-input-estado" class="w-full p-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none bg-white text-gray-700 text-sm font-medium shadow-sm transition-all hover:border-gray-400">
                    <option value="activo" ${currentStatus === 'activo' || currentStatus === 'Normal' ? 'selected' : ''}>Activo</option>
                    <option value="cuarentena" ${currentStatus === 'cuarentena' || currentStatus === 'En Cuarentena' ? 'selected' : ''}>En Cuarentena</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500 z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#ef4444',
        focusConfirm: false,
        customClass: {
            popup: 'rounded-xl shadow-xl border border-gray-100',
            confirmButton: 'px-5 py-2.5 rounded-lg font-medium text-sm shadow-sm',
            cancelButton: 'px-5 py-2.5 rounded-lg font-medium text-sm shadow-sm'
        },
        preConfirm: () => {
            return document.getElementById('swal-input-estado').value;
        }
      });

      if (estado) {
        try {
          showLoading('Actualizando estado...');
          const res = await fetch(`/inventario/lotes/${lote.id}/cambiar-estado`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ estado: estado })
          });

          const data = await res.json();

          if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message || 'Estado actualizado correctamente',
                timer: 1500,
                showConfirmButton: false
            });
            await abrirDetalles(currentProductId);
            load(); // Reload main table
          } else {
            throw new Error(data.message || 'Error al cambiar estado');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error', err.message, 'error');
        } finally {
          hideLoading();
        }
      }
    }

  async function eliminarLote(id) {
      const result = await Swal.fire({
          title: 'Dar de Baja Lote',
          html: `
              <p style="font-size: 1rem; margin-bottom: 0.5rem;">¿Estás seguro de dar de baja el lote <strong>LOTE-${id}</strong>?</p>
              <p style="font-size: 0.875rem; color: #dc2626; font-weight: 600;">
                El stock pasará a 0 y se registrará como "Vencimiento/Merma".
              </p>
          `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Dar de Baja',
          cancelButtonText: 'Cancelar',
          reverseButtons: true
      });

      if (result.isConfirmed) {
          try {
              showLoading('Dando de baja el lote...');
              const res = await fetch(`/inventario/lotes/${id}`, {
                  method: 'DELETE',
                  headers: {
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                      'Content-Type': 'application/json'
                  }
              });

              const data = await res.json();
              
              // Ocultar loading ANTES de mostrar el SweetAlert
              hideLoading();

              if (data.success) {
                  // Toast de éxito que desaparece automáticamente
                  const Toast = Swal.mixin({
                      toast: true,
                      position: 'top-end',
                      showConfirmButton: false,
                      timer: 3000,
                      timerProgressBar: true,
                      didOpen: (toast) => {
                          toast.addEventListener('mouseenter', Swal.stopTimer)
                          toast.addEventListener('mouseleave', Swal.resumeTimer)
                      }
                  });
                  
                  Toast.fire({
                      icon: 'success',
                      title: 'Lote dado de baja correctamente',
                      text: 'Stock ajustado a 0 y registrado como vencimiento/merma'
                  });
                  
                  // Recargar datos
                  abrirDetalles(currentProductId);
                  load();
              } else {
                  throw new Error(data.message || 'Error al dar de baja el lote');
              }
          } catch (err) {
              hideLoading();
              console.error(err);
              Swal.fire({
                  title: 'Error',
                  text: err.message || 'No se pudo dar de baja el lote',
                  icon: 'error',
                  confirmButtonColor: '#dc2626'
              });
          }
      }
  }

function mostrarModal() {
  const modal = document.getElementById('modalDetallesBotica');
  modal.style.display = 'flex';
  modal.classList.remove('hidden');
  lockScroll();
  const nombre = document.getElementById('nombreProducto');
  if (nombre) { nombre.removeAttribute('readonly'); nombre.removeAttribute('disabled'); nombre.focus(); }
}
function cerrarModal() {
  const modal = document.getElementById('modalDetallesBotica');
  modal.style.display = 'none';
  modal.classList.add('hidden');
  unlockScroll();
}
  document.getElementById('cerrarModalBotica')?.addEventListener('click', cerrarModal);
  // Cerrar al hacer click fuera del contenedor
  const overlayDetalles = document.getElementById('modalDetallesBotica');
  if (overlayDetalles) {
    overlayDetalles.addEventListener('click', (e) => {
      if (e.target === overlayDetalles) cerrarModal();
    });
  }
  // Soporta ambos IDs por compatibilidad con agregar.js
  const btnAddA = document.getElementById('btnAgregarProductoBotica');
  const btnAddB = document.getElementById('btnAgregarProducto');
  (btnAddA || btnAddB)?.addEventListener('click', () => {
    const m = document.getElementById('modalAgregar');
    // Resetear formulario al abrir
    resetAgregarForm();
    // Asegurar opciones actualizadas
    try { cargarCategoriasYPresentaciones(); cargarProveedores(); } catch(e){}
    m.style.display = 'flex';
    lockScroll();
    const nombre = document.getElementById('nombreProducto');
    if (nombre) { nombre.removeAttribute('readonly'); nombre.removeAttribute('disabled'); setTimeout(()=> nombre.focus(), 50); }
  });
  document.getElementById('closeAgregar')?.addEventListener('click', () => { resetAgregarForm(); document.getElementById('modalAgregar').style.display='none'; unlockScroll(); });
  document.getElementById('btnCancelarAgregar')?.addEventListener('click', () => { resetAgregarForm(); document.getElementById('modalAgregar').style.display='none'; unlockScroll(); });
  function closeModalEditar() {
    const m = document.getElementById('modalEditar');
    if (m) { m.style.display='none'; m.classList.add('hidden'); }
    unlockScroll();
  }
  document.getElementById('closeEditar')?.addEventListener('click', closeModalEditar);
  document.getElementById('btnCancelarEditar')?.addEventListener('click', closeModalEditar);
  // Cerrar al hacer click fuera del contenedor
  const overlayEdit = document.getElementById('modalEditar');
  if (overlayEdit) {
    overlayEdit.addEventListener('click', (e)=>{ if (e.target === overlayEdit) closeModalEditar(); });
  }

  // Events
  perPageEl.addEventListener('change', () => { page = 1; load(); });
  estadoEl.addEventListener('change', () => { page = 1; load(); });
  let searchTimeout;
  searchEl.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page = 1; load(); }, 200);
  });

  // --- Export dropdown handlers (Excel/PDF)
  const btnExportar = document.getElementById('btnExportarBotica');
  const menuExportar = document.getElementById('exportarDropdownMenuBotica');
  const btnExcel = document.getElementById('btnExportarExcelBotica');
  const btnPDF = document.getElementById('btnExportarPDFBotica');

  if (btnExportar && menuExportar) {
    btnExportar.addEventListener('click', (e) => {
      e.stopPropagation();
      menuExportar.classList.toggle('hidden');
      btnExportar.classList.toggle('active');
    });
    document.addEventListener('click', (e) => {
      if (!menuExportar.classList.contains('hidden')) {
        menuExportar.classList.add('hidden');
        btnExportar.classList.remove('active');
      }
    });
  }

  async function obtenerDatosParaExportar() {
    // Mostrar loading
    Swal.fire({
      title: 'Generando reporte...',
      text: 'Recopilando información de todos los productos...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    try {
      const perPage = -1; // -1 indica "todos" (según configuración backend)
      let estado = estadoEl.value || 'todos';
      // Replicar lógica de filtro de estado
      try {
        const params = new URLSearchParams(window.location.search);
        const urlEstado = params.get('estado');
        if (urlEstado) estado = urlEstado;
      } catch (_) {}
      const search = (searchEl.value || '').trim();

      const url = new URL(window.APP_PRODUCTS_AJAX || '/inventario/productos/ajax', window.location.origin);
      url.searchParams.append('search', search);
      url.searchParams.append('estado', estado);
      url.searchParams.append('per_page', perPage);
      url.searchParams.append('page', 1);

      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('Error al cargar productos');
      const data = await res.json();
      
      return Array.isArray(data.data) ? data.data : [];
    } catch (e) {
      console.error('Error exportando:', e);
      Swal.fire({ icon:'error', title:'Error', text:'No se pudo obtener la lista completa de productos' });
      return null;
    }
  }

  async function ensureXLSX() {
    function load(url) {
        return new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = url; s.async = true; s.onload = () => resolve(true); s.onerror = () => resolve(false);
            document.head.appendChild(s);
        });
    }
    // Intentar primero xlsx-js-style (soporta estilos), luego fallback a xlsx estándar
    const primary = await load('https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx-js-style.min.js');
    if (primary && typeof XLSX !== 'undefined') return true;
    const fallback = await load('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
    return fallback && typeof XLSX !== 'undefined';
  }

  async function exportarExcelBotica() {
    try {
      const search = (searchEl.value || '').trim();
      const estado = estadoEl.value || 'todos';
      
      // Mostrar loading
      Swal.fire({
        title: 'Preparando Excel...',
        text: 'Generando reporte profesional de productos',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      // Construir URL con filtros actuales
      const url = new URL('/inventario/productos/exportar', window.location.origin);
      url.searchParams.append('search', search);
      url.searchParams.append('estado', estado);

      // Redirigir a la descarga
      window.location.href = url.toString();
      
      // Cerrar loading después de un momento
      setTimeout(() => { Swal.close(); }, 2000);
      
    } catch (e) {
      console.error('Error exportando:', e);
      Swal.fire({ icon:'error', title:'Error', text:'No se pudo generar el reporte Excel' });
    }
  }

  async function exportarPDFBotica() {
    const productos = await obtenerDatosParaExportar();
    if (!productos || !productos.length) {
      if (productos) Swal.fire({ icon:'warning', title:'Sin datos', text:'No hay productos para exportar' });
      return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l','mm','a4');
    
    // Header profesional
    doc.setFillColor(239, 83, 80); // Rojo suave (#EF5350)
    doc.rect(0, 0, 297, 28, 'F'); // Aumentar un poco la altura del header del reporte
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(18); doc.setFont('helvetica','bold');
    doc.text('REPORTE DE PRODUCTOS - BOTICA SAN ANTONIO', 14, 16);
    
    doc.setFontSize(12); doc.setFont('helvetica','normal');
    doc.text(`Total Productos: ${productos.length}`, 14, 24);

    doc.setFontSize(10); doc.setFont('helvetica','normal');
    doc.text(`Fecha de emisión: ${new Date().toLocaleDateString('es-PE')}`, 230, 16);

    const columnas = ['ID', 'Producto','Concentración','Marca','Proveedor','Stock','P. Venta','P. Compra','Vencimiento','Categoría'];
    const filas = productos.map((p, index) => [
      index + 1,
      (p.nombre || '').slice(0,30),
      p.concentracion || 'N/A',
      p.marca || 'N/A',
      p.proveedor || 'Sin proveedor',
      p.stock_actual,
      `S/ ${parseFloat(p.precio_venta || 0).toFixed(2)}`,
      `S/ ${parseFloat(p.precio_compra || 0).toFixed(2)}`,
      formatFecha(p.fecha_vencimiento),
      p.categoria || 'N/A'
    ]);
    
    doc.autoTable({ 
      head:[columnas], 
      body:filas, 
      startY:35, 
      styles:{ 
        fontSize:8, 
        cellPadding: 3, // Padding general
        valign: 'middle'
      },
      headStyles: { 
        fillColor: [239, 83, 80], 
        textColor: [255, 255, 255], 
        fontStyle: 'bold',
        minCellHeight: 12, // Aumentar altura del encabezado
        valign: 'middle',
        halign: 'center'
      },
      columnStyles: {
        0: { halign: 'center' }, // ID centrado
        5: { halign: 'center' }, // Stock centrado
        6: { halign: 'right' }, // Precio venta alineado a la derecha
        7: { halign: 'right' }, // Precio compra alineado a la derecha
        8: { halign: 'center' } // Vencimiento centrado
      },
      alternateRowStyles: { 
        fillColor: [255, 255, 255] // Filas blancas (sin alternancia de color visible)
      },
      theme: 'grid'
    });
    
    const fecha = new Date().toISOString().split('T')[0];
    const nombreArchivo = `productos_botica_${fecha}.pdf`;
    doc.save(nombreArchivo);
    Swal.fire({ 
      icon:'success', 
      title:'Exportación exitosa', 
      text:`Archivo ${nombreArchivo} descargado`,
      timer: 2000,
      showConfirmButton: false
    });
  }

  if (btnExcel) btnExcel.addEventListener('click', (e)=>{ e.stopPropagation(); exportarExcelBotica(); });
  if (btnPDF) btnPDF.addEventListener('click', (e)=>{ e.stopPropagation(); exportarPDFBotica(); });
  clearBtn.addEventListener('click', () => { searchEl.value = ''; page = 1; load(); });

  window.addEventListener('turbo:load', unlockScroll);
  window.addEventListener('beforeunload', unlockScroll);

  // Expose defaults
  window.APP_PRODUCTS_AJAX = window.APP_PRODUCTS_AJAX || '/inventario/productos/ajax';
  window.APP_DEFAULT_IMAGE = window.APP_DEFAULT_IMAGE || '/assets/images/default-product.svg';

  // Submit handlers
  const formAgregar = document.getElementById('formAgregarProducto');
  if (formAgregar) {
    formAgregar.addEventListener('submit', async (e) => {
      e.preventDefault();
      await guardarNuevoProducto();
    });
    const imgAdd = document.getElementById('imagen-input');
    if (imgAdd) {
      imgAdd.addEventListener('change', () => {
        const file = imgAdd.files?.[0];
        const prev = document.getElementById('preview-container');
        const img = document.getElementById('preview-image');
        if (file && prev && img) { prev.classList.remove('hidden'); img.src = URL.createObjectURL(file); }
      });
    }
  }

  function resetAgregarForm() {
    const form = document.getElementById('formAgregarProducto');
    if (form) {
      form.reset();
      // limpiar estados de validación si los hubiera
      form.querySelectorAll('input, select, textarea').forEach(el => {
        el.classList.remove('campo-invalido','campo-valido','border-red-500','bg-red-50','border-green-500','bg-green-50');
      });
    }
    const prev = document.getElementById('preview-container');
    const img = document.getElementById('preview-image');
    if (prev) prev.classList.add('hidden');
    if (img) img.src = '';
  }
  const formEditar = document.getElementById('formEditarProducto');
  if (formEditar) {
    formEditar.addEventListener('submit', async (e) => {
      e.preventDefault();
      await guardarEdicionProducto();
    });
    const imgEdit = document.getElementById('edit-imagen-input');
    if (imgEdit) {
      imgEdit.addEventListener('change', () => {
        const file = imgEdit.files?.[0];
        const prev = document.getElementById('edit-preview-container');
        const img = document.getElementById('edit-preview-image');
        if (file && prev && img) { prev.style.display='block'; img.src = URL.createObjectURL(file); }
      });
    }
  }

  load();

  // Populate selects for agregar
  cargarCategoriasYPresentaciones();
  cargarProveedores();
});

// === Variables globales para selector de lotes ===
let selectorLotesCallback = null;

// === Funciones auxiliares globales ===
function formatFecha(str) {
  if (!str) return 'N/A';
  const d = new Date(str);
  if (isNaN(d.getTime())) return 'N/A';
  return d.toLocaleDateString('es-PE');
}

// === Funciones globales para selector de lotes ===
function mostrarSelectorLotes(lotes, productoNombre, callback, titulo = 'Detalle de Lotes') {
  console.log('mostrarSelectorLotes llamado con:', { lotes, productoNombre, titulo });
  
  if (!lotes || lotes.length === 0) {
    callback(null);
    return;
  }
  
  if (lotes.length === 1) {
    callback(lotes[0]);
    return;
  }

  selectorLotesCallback = callback;

  const modal = document.getElementById('modalSelectorLotes');
  const modalTitle = document.getElementById('modalSelectorLotesTitle');
  const productoNombreEl = document.getElementById('selectorProductoNombre');
  const tbody = document.getElementById('selectorLotesBody');

  if (!modal || !tbody) return;

  modalTitle.textContent = titulo;
  productoNombreEl.textContent = productoNombre;
  tbody.innerHTML = '';

  lotes.forEach((l, idx) => {
    const dias = Math.round(l.dias_para_vencer || 0);
    const cantidad = Number(l.cantidad || 0);
    let estadoClass = '';
    let estadoText = '';
    
    if (cantidad <= 0) {
      estadoClass = 'bg-gray-100 text-gray-600 border-gray-200';
      estadoText = 'Agotado';
    } else if (dias < 0) {
      estadoClass = 'bg-red-100 text-red-700 border-red-200';
      estadoText = 'Vencido';
    } else if (dias <= 90) {
      estadoClass = 'bg-amber-100 text-amber-700 border-amber-200';
      estadoText = 'Por Vencer';
    } else {
      estadoClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
      estadoText = 'Vigente';
    }

    const diasText = dias >= 0 ? `Vence en ${dias} días` : `Venció hace ${Math.abs(dias)} días`;
    const precioVenta = l.precio_venta_lote || l.precio_venta || 0;
    const precioCompra = l.precio_compra_lote || l.precio_compra || 0;

    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-100 cursor-pointer transition-all border-b border-gray-100 group';
    row.dataset.loteIdx = idx;
    row.innerHTML = `
      <td class="px-4 py-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 group-hover:bg-blue-100 transition-colors">
                <iconify-icon icon="solar:box-minimalistic-bold-duotone" class="text-xl"></iconify-icon>
            </div>
            <div class="font-bold text-gray-900 text-base">${l.lote || 'Sin código'}</div>
        </div>
      </td>
      <td class="px-4 py-4">
        <div class="font-bold text-gray-800">${formatFecha(l.fecha_vencimiento)}</div>
        <div class="text-xs font-semibold text-gray-500 mt-0.5">${diasText}</div>
      </td>
      <td class="px-4 py-4 text-center">
        <span class="inline-flex items-center justify-center px-3 py-1 bg-gray-50 rounded-lg font-extrabold text-gray-900 text-base border border-gray-200">${cantidad}</span>
      </td>
      <td class="px-4 py-4 text-center">
        <div class="flex flex-col items-center">
            <span class="font-extrabold text-emerald-600 text-base">S/ ${Number(precioCompra).toFixed(2)}</span>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Costo Unit.</span>
        </div>
      </td>
      <td class="px-4 py-4 text-center">
        <div class="flex flex-col items-center">
            <span class="font-extrabold text-emerald-600 text-base">S/ ${Number(precioVenta).toFixed(2)}</span>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Venta Unit.</span>
        </div>
      </td>
    `;

    row.addEventListener('click', function(e) {
      e.stopPropagation();
      const callbackToExecute = selectorLotesCallback;
      cerrarModalSelectorLotes();
      if (callbackToExecute) {
        callbackToExecute(lotes[idx]);
      }
    });

    tbody.appendChild(row);
  });

  modal.style.display = 'flex';
  modal.classList.remove('hidden');
}

function cerrarModalSelectorLotes() {
  const modal = document.getElementById('modalSelectorLotes');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.add('hidden');
  }
  selectorLotesCallback = null;
}

// Event listeners para cerrar modal selector de lotes
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('cerrarModalSelectorLotes')?.addEventListener('click', cerrarModalSelectorLotes);
  document.getElementById('modalSelectorLotes')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalSelectorLotes') {
      cerrarModalSelectorLotes();
    }
  });
});

// === Global loading helpers (usable outside DOMContentLoaded scope) ===
function showLoading(label = 'Cargando datos...') {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) {
    overlay.style.display = 'flex';
    const textEl = overlay.querySelector('.loading-text');
    if (textEl) textEl.textContent = label;
  }
}
function hideLoading() {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) overlay.style.display = 'none';
}

// Cierra cualquier modal previo para evitar estados bloqueados
function resetModals() {
  const ids = ['modalEditar','modalDetallesBotica','modalAgregar'];
  ids.forEach((id)=>{
    const m = document.getElementById(id);
    if (m) { m.style.display='none'; m.classList.add('hidden'); m.classList.remove('flex'); }
  });
  document.body.classList.remove('modal-open');
}

// --- Complementary functions for CRUD ---
async function cargarCategoriasYPresentaciones(catSel=null, presSel=null) {
  try {
    const rc = await fetch('/inventario/categoria/api/all');
    const dc = await rc.json();
    const selCatAdd = document.getElementById('add-categoria');
    const selCatEdit = document.getElementById('edit-categoria');
    if (dc.success) {
      const opts = ['<option value="">Seleccionar</option>'].concat(dc.data.map(c=>`<option value="${c.nombre}">${c.nombre}</option>`)).join('');
      if (selCatAdd) selCatAdd.innerHTML = opts;
      if (selCatEdit) selCatEdit.innerHTML = opts;
      if (catSel && selCatEdit) selCatEdit.value = catSel;
    }
    // COMENTADO: Ya no cargamos presentaciones del catálogo antiguo
    /*
    const rp = await fetch('/inventario/presentacion/api');
    const dp = await rp.json();
    const selPresAdd = document.getElementById('add-presentacion');
    const selPresEdit = document.getElementById('edit-presentacion');
    if (dp.success) {
      const optsP = ['<option value="">Seleccionar</option>'].concat(dp.data.map(p=>`<option value="${p.nombre}">${p.nombre}</option>`)).join('');
      if (selPresAdd) selPresAdd.innerHTML = optsP;
      if (selPresEdit) selPresEdit.innerHTML = optsP;
      if (presSel && selPresEdit) selPresEdit.value = presSel;
    }
    */
  } catch(e) { console.error(e); }
}

async function cargarProveedores(provSel=null) {
  try {
    // Usar endpoint que lista todos los proveedores activos, sin requerir término de búsqueda
    const rp = await fetch('/compras/proveedores/api');
    const dp = await rp.json();
    const selAdd = document.getElementById('add-proveedor');
    const selEdit = document.getElementById('edit-proveedor');
    if (dp.success && Array.isArray(dp.data)) {
      // Ordenar por razón social para mejorar UX
      dp.data.sort((a,b)=>((a.razon_social||'').localeCompare(b.razon_social||'')));
      const opts = ['<option value="">Seleccionar</option>']
        .concat(dp.data.map(pr => {
          const display = pr.razon_social || pr.nombre || pr.nombre_comercial || pr.ruc || (`Proveedor #${pr.id}`);
          return `<option value="${pr.id}">${display}</option>`;
        }))
        .join('');
      if (selAdd) { selAdd.innerHTML = opts; selAdd.disabled = false; }
      if (selEdit) { selEdit.innerHTML = opts; selEdit.disabled = false; }
      // Selección por ID si está disponible
      if (provSel) {
        if (selEdit) selEdit.value = String(provSel);
        if (selAdd) selAdd.value = String(provSel);
      } else {
        // Fallback: seleccionar por nombre si el ID no está
        const targetName = window.currentEditProveedorName || '';
        if (targetName && selEdit) {
          const opt = Array.from(selEdit.options).find(o => (o.text || '').trim().toLowerCase() === targetName.trim().toLowerCase());
          if (opt) selEdit.value = opt.value;
        }
      }
    } else {
      if (selAdd) { selAdd.innerHTML = '<option value="">No hay proveedores activos</option>'; selAdd.disabled = false; }
      if (selEdit) { selEdit.innerHTML = '<option value="">No hay proveedores activos</option>'; selEdit.disabled = false; }
    }
  } catch(e) { console.error(e); }
}

async function abrirModalEdicion(productId, loteSeleccionado = null) {
  try {
    resetModals();
    showLoading('Cargando datos para editar...');
    const res = await fetch(`/inventario/producto/${productId}`);
    const data = await res.json();
    if (!data.success || !data.data) throw new Error('No se pudo cargar producto');
    const p = data.data;
    
    // Si hay múltiples lotes y no se ha seleccionado uno, mostrar selector
    const lotes = p.lotes_detalle || [];
    if (lotes.length > 1 && !loteSeleccionado) {
      hideLoading();
      mostrarSelectorLotes(lotes, p.nombre, (lote) => {
        if (lote) {
          window.abrirModalEdicion(productId, lote);
        }
      }, 'Editar Producto - Seleccionar Lote');
      return;
    }
    
    // Usar datos del lote seleccionado si existe
    let datosLote = loteSeleccionado || (lotes.length === 1 ? lotes[0] : null);
    
    // Datos a usar (del lote si existe, sino del producto)
    const stockActual = loteSeleccionado ? loteSeleccionado.cantidad : p.stock_actual;
    const loteCode = datosLote ? datosLote.lote : p.lote;
    const fechaVenc = datosLote ? datosLote.fecha_vencimiento : p.fecha_vencimiento;
    const precioCompra = datosLote && datosLote.precio_compra_lote ? datosLote.precio_compra_lote : (p.precio_compra || p.compra_precio || 0);
    const precioVenta = datosLote && datosLote.precio_venta_lote ? datosLote.precio_venta_lote : (p.precio_venta || p.venta_precio || 0);
    const proveedorId = datosLote && datosLote.proveedor_id ? datosLote.proveedor_id : p.proveedor_id;
    const proveedorNombre = datosLote && datosLote.proveedor ? datosLote.proveedor : p.proveedor;
    
    // Guardar ID del lote para la edición
    window.currentEditLoteId = datosLote ? datosLote.id : null;

    // --- GUARDAR VALORES ORIGINALES PARA DETECCIÓN DE CAMBIOS ---
    window.originalEditValues = {
        nombre: p.nombre || '',
        categoria: p.categoria || '',
        marca: p.marca || '',
        presentacion: p.presentacion || '',
        lote: loteCode || '',
        codigo_barras: p.codigo_barras || '',
        stock_actual: Number(stockActual || 0),
        stock_minimo: Number(p.stock_minimo || 0),
        precio_compra: Number(precioCompra || 0),
        precio_venta: Number(precioVenta || 0),
        fecha_fabricacion: p.fecha_fabricacion || '',
        proveedor_id: String(proveedorId || '')
    };
    // -------------------------------------------------------------
    
  if (typeof window.abrirModalEditarProducto === 'function') {
    await window.abrirModalEditarProducto({
        id: p.id,
        nombre: p.nombre,
        categoria: p.categoria,
        marca: p.marca,
        proveedor_id: proveedorId,
        proveedor: proveedorNombre,
        // REMOVIDO: presentacion: p.presentacion,
        concentracion: p.concentracion,
        lote: loteCode,
        codigo_barras: p.codigo_barras,
        stock_actual: stockActual,
        stock_minimo: p.stock_minimo,
        precio_compra: precioCompra,
        precio_venta: precioVenta,
        // REMOVIDO: fecha_fabricacion: p.fecha_fabricacion || '',
      fecha_vencimiento: fechaVenc || '',
      imagen_url: p.imagen_url || '',
      lote_id: datosLote ? datosLote.id : null
    });
  } else {
      document.getElementById('edit-producto-id').value = p.id;
      
      // Guardar ID del lote en un campo oculto si existe
      let loteIdField = document.getElementById('edit-lote-id');
      if (!loteIdField) {
        loteIdField = document.createElement('input');
        loteIdField.type = 'hidden';
        loteIdField.id = 'edit-lote-id';
        loteIdField.name = 'lote_id';
        document.getElementById('formEditarProducto')?.appendChild(loteIdField);
      }
      loteIdField.value = datosLote ? datosLote.id : '';
      
      const ids = ['edit-nombre','edit-concentracion','edit-marca','edit-lote','edit-codigo_barras','edit-stock_actual','edit-stock_minimo','precio_compra_base_edit','precio_venta_base_edit'];
      const vals = [p.nombre,p.concentracion,p.marca,loteCode,p.codigo_barras,stockActual,p.stock_minimo,precioCompra,precioVenta];
      ids.forEach((id,i)=>{ 
          const el=document.getElementById(id); 
          if(el) {
              el.value = vals[i]??'';
              // Disparar evento input para que el manager de presentaciones actualice la "Unidad"
              el.dispatchEvent(new Event('input'));
          }
      });
      
    cargarCategoriasYPresentaciones(p.categoria, null); // No cargar presentación antigua
    
    window.currentEditProveedorName = proveedorNombre || '';
    cargarProveedores(proveedorId);

      const prev = document.getElementById('edit-preview-container');
      const img = document.getElementById('edit-preview-image');
    if (prev && img) { 
        prev.style.display='block'; 
        img.src = p.imagen_url || '/assets/images/default-product.svg'; 
    }
    
      const modalEdit = document.getElementById('modalEditar');
    if (modalEdit) {
      modalEdit.style.display='flex';
      modalEdit.classList.remove('hidden');
      document.body.classList.add('modal-open');
    }
  }

  // Cargar presentaciones del producto SIEMPRE al final, una sola vez
  setTimeout(async () => {
    if (typeof window.loadExistingPresentaciones === 'function') {
      const loteId = datosLote ? datosLote.id : null;
      console.log('🔄 Cargando presentaciones para producto:', p.id, loteId ? `y lote: ${loteId}` : '');
      await window.loadExistingPresentaciones(p.id, loteId);
    }
  }, 200);

  } catch(e) { console.error(e); Swal.fire('Error','No se pudo cargar el producto','error'); }
  finally { hideLoading(); }
}

// Exponer abrirModalEdicion globalmente
window.abrirModalEdicion = abrirModalEdicion;

async function guardarNuevoProducto() {
  try {
    // Verificar duplicados antes de procesar
    if (typeof window.verificarDuplicado === 'function') {
        const isDuplicate = await window.verificarDuplicado(true); // true para silent check si se quisiera, pero aquí queremos que marque el input
        if (isDuplicate) {
            Swal.fire({
                icon: 'warning',
                title: 'Producto Duplicado',
                text: 'Ya existe un producto con el mismo nombre y concentración. Verifica los campos marcados.',
                confirmButtonColor: '#f59e0b'
            });
            return; // Detener guardado
        }
    }

    const form = document.getElementById('formAgregarProducto');
    if (window.validacionesTiempoReal) {
      const ok = await window.validacionesTiempoReal.validateForm('formAgregarProducto');
      if (!ok) {
        Swal.fire('Errores de validación','Corrige los campos marcados antes de guardar','warning');
        return;
      }
    }
    clearFieldErrors(form);
    const fd = new FormData(form);
    
    // --- FIX: Agregar presentaciones al FormData ---
    if (typeof window.getPresentacionesData === 'function') {
        const presentacionesObj = window.getPresentacionesData();
        Object.entries(presentacionesObj).forEach(([key, pres]) => {
            // Usamos el key original (id o new_X) para que el controlador pueda distinguir
            fd.append(`presentaciones[${key}][nombre_presentacion]`, pres.nombre_presentacion);
            fd.append(`presentaciones[${key}][unidades_por_presentacion]`, pres.unidades_por_presentacion);
            fd.append(`presentaciones[${key}][precio_venta_presentacion]`, pres.precio_venta_presentacion);
        });
    }
    // ----------------------------------------------

    const selProvAdd = document.getElementById('add-proveedor');
    if (selProvAdd) {
      fd.set('proveedor_id', selProvAdd.value || '');
    }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const resp = await fetch('/inventario/producto/guardar', { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body: fd });
    if (resp.status === 422) {
      const data = await resp.json();
      const errs = data.errors || {};
      
      // Verificar si hay error de duplicado en los mensajes de validación
      const errorValues = Object.values(errs).flat().map(e => e.toLowerCase());
      const isDuplicate = errorValues.some(e => e.includes('ya ha sido registrado') || e.includes('duplicado') || e.includes('ya existe'));
      
      if (isDuplicate) {
          Swal.fire({
            icon: 'error',
            title: 'Producto Duplicado',
            html: `
                <div class="flex flex-col items-center gap-3">
                    <iconify-icon icon="solar:danger-circle-bold-duotone" class="text-6xl text-red-500"></iconify-icon>
                    <div class="text-center">
                        <p class="text-gray-800 font-bold text-lg mb-1">¡Ya existe este producto!</p>
                        <p class="text-gray-600">No es posible registrar dos productos con el mismo <b>Nombre</b> y <b>Concentración</b>.</p>
                        <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-100 text-sm text-red-700 text-left">
                           <ul class="list-disc pl-4 space-y-1">
                               <li>Verifica si el producto ya está en el inventario.</li>
                               <li>Si es una nueva presentación, asegúrate de diferenciar el nombre o concentración.</li>
                           </ul>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido, revisaré'
        });
        // También marcamos los campos
        Object.keys(errs).forEach(k => showFieldError(form, k, errs[k][0] || 'Campo inválido'));
        return;
      }

      Object.keys(errs).forEach(k => showFieldError(form, k, errs[k][0] || 'Campo inválido'));
      Swal.fire('Revisa los campos','Hay errores de validación','warning');
      return;
    }
    const data = await resp.json();
    if (!resp.ok || !data.success) throw new Error(data.message || 'Error al guardar');
    document.getElementById('modalAgregar').style.display='none';
    Swal.fire({
      icon: 'success',
      title: '¡Producto creado!',
      text: 'El producto se guardó correctamente',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true,
      willClose: () => {
        if (typeof window.loadProducts === 'function') {
          window.loadProducts();
        } else {
          location.reload();
        }
      }
    });
  } catch(e) { 
    console.error(e); 
    // Detectar error de duplicado (backend suele devolver 500 con SQLSTATE 23000 o mensaje custom)
    if (e.message && (e.message.includes('Duplicate entry') || e.message.toLowerCase().includes('duplicado') || e.message.includes('SQLSTATE[23000]'))) {
        Swal.fire({
            icon: 'error',
            title: 'Producto Duplicado',
            html: `
                <div class="flex flex-col items-center gap-3">
                    <iconify-icon icon="solar:danger-circle-bold-duotone" class="text-6xl text-red-500"></iconify-icon>
                    <div class="text-center">
                        <p class="text-gray-800 font-bold text-lg mb-1">¡Ya existe este producto!</p>
                        <p class="text-gray-600">No es posible registrar dos productos con el mismo <b>Nombre</b> y <b>Concentración</b>.</p>
                        <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-100 text-sm text-red-700 text-left">
                           <ul class="list-disc pl-4 space-y-1">
                               <li>Verifica si el producto ya está en el inventario.</li>
                               <li>Si es una nueva presentación, asegúrate de diferenciar el nombre o concentración.</li>
                           </ul>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido, revisaré'
        });
    } else {
        Swal.fire('Error', e.message || 'No se pudo guardar', 'error'); 
    }
  }
}

async function guardarEdicionProducto() {
  try {
    // Validación rápida en cliente para evitar 422 innecesarios
    const form = document.getElementById('formEditarProducto');
    const nombre = form.querySelector('[name="nombre"]')?.value?.trim();
    const categoria = form.querySelector('[name="categoria"]')?.value?.trim();
    const marca = form.querySelector('[name="marca"]')?.value?.trim();
    const concentracion = form.querySelector('[name="concentracion"]')?.value?.trim();
    const lote = form.querySelector('[name="lote"]')?.value?.trim();
    const codigo_barras = form.querySelector('[name="codigo_barras"]')?.value?.trim();

    clearFieldErrors(form);
    let hasError = false;
    const req = (val, field) => { if (!val) { showFieldError(form, field, 'Este campo es obligatorio'); hasError = true; } };
    
    req(nombre,'nombre'); 
    req(categoria,'categoria'); 
    req(marca,'marca'); 
    req(concentracion,'concentracion');
    req(lote,'lote'); 
    req(codigo_barras,'codigo_barras');
    
    // --- FECHA DE VENCIMIENTO (OCULTA Y OPCIONAL) ---
    const fvInput = document.getElementById('edit-fecha_vencimiento');
    const fecha_vencimiento = fvInput?.value || '';

    if (codigo_barras && codigo_barras.length !== 13) { showFieldError(form,'codigo_barras','Debe tener 13 dígitos (EAN13)'); hasError = true; }
    
    // --- VALIDACIÓN DE PRECIOS BASE ---
    const pcVal = document.getElementById('precio_compra_base_edit')?.value;
    const pvVal = document.getElementById('precio_venta_base_edit')?.value;
    const nPC = parseFloat(pcVal || 0);
    const nPV = parseFloat(pvVal || 0);

    console.log('🔍 Validando precios al guardar:', { pcVal, pvVal, nPC, nPV });

    if (!pcVal || isNaN(nPC) || nPC <= 0) { 
        showFieldError(form,'precio_compra','Mínimo > 0'); 
        hasError = true; 
    }
    if (!pvVal || isNaN(nPV) || nPV <= 0) { 
        showFieldError(form,'precio_venta','Mínimo > 0'); 
        hasError = true; 
    }
    if (nPV > 0 && nPC > 0 && nPV <= nPC) { 
        showFieldError(form,'precio_venta','Debe ser mayor al costo'); 
        hasError = true; 
    }

    if (hasError) { 
        console.warn('⚠️ Guardado bloqueado por validaciones JS internas');
        Swal.fire('Revisa los campos','Hay errores de validación o campos vacíos','warning'); 
        return; 
    }

    // --- VALIDACIÓN SECUNDARIA (TIME REAL) ---
    // Si el validador externo falla, no detenemos el proceso si ya pasamos nuestra validación interna fuerte
    if (window.validacionesTiempoReal) {
      try {
          const ok = await window.validacionesTiempoReal.validateForm('formEditarProducto');
          if (!ok) {
              console.warn('⚠️ Validador de tiempo real detectó errores, pero intentaremos proceder si los campos críticos están llenos');
              // Si quieres ser estricto, deja el return. Si quieres que guarde sí o sí, comenta la siguiente línea:
              // return; 
          }
      } catch (e) {
          console.error('Error en validador externo:', e);
      }
    }
    
    clearFieldErrors(form);
    const fd = new FormData(form);

  // --- FIX: Asegurar que los datos críticos se envíen correctamente ---
  const pcFinal = document.getElementById('precio_compra_base_edit')?.value;
  const pvFinal = document.getElementById('precio_venta_base_edit')?.value;
  const fvVal = document.getElementById('edit-fecha_vencimiento')?.value;
  
  if (pcFinal) fd.set('precio_compra', pcFinal);
  if (pvFinal) fd.set('precio_venta', pvFinal);
  if (fvVal) fd.set('fecha_vencimiento', fvVal);

  // --- FIX: Agregar presentaciones al FormData (Edit) ---
  if (typeof window.getPresentacionesData === 'function') {
      const presentacionesObj = window.getPresentacionesData();
      Object.entries(presentacionesObj).forEach(([key, pres]) => {
          fd.append(`presentaciones[${key}][nombre_presentacion]`, pres.nombre_presentacion);
          fd.append(`presentaciones[${key}][unidades_por_presentacion]`, pres.unidades_por_presentacion);
          fd.append(`presentaciones[${key}][precio_venta_presentacion]`, pres.precio_venta_presentacion);
      });
  }
  // -----------------------------------------------------

  const selProvEdit = document.getElementById('edit-proveedor');
  if (selProvEdit) {
    selProvEdit.disabled = false;
    fd.set('proveedor_id', selProvEdit.value || '');
  }
  
  // Agregar lote_id si existe
  if (window.currentEditLoteId) {
    fd.set('lote_id', window.currentEditLoteId);
    console.log('Agregando lote_id al FormData:', window.currentEditLoteId);
  }
  
  const id = document.getElementById('edit-producto-id').value;
  
  // Log para debugging
  console.log('=== DATOS ENVIADOS AL EDITAR ===');
  for (let [key, value] of fd.entries()) {
    console.log(`${key}: ${value}`);
  }
  
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const resp = await fetch(`/inventario/producto/actualizar/${id}`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'X-HTTP-Method-Override':'PUT','Accept':'application/json'}, body: fd });
    if (resp.status === 422) {
      const data = await resp.json();
      console.error('=== ERROR 422 ===', data);
      const errs = data.errors || {};
      Object.keys(errs).forEach(k => {
        console.error(`Campo ${k}: ${errs[k][0]}`);
        showFieldError(form, k, errs[k][0] || 'Campo inválido');
      });
      Swal.fire('Revisa los campos','Hay errores de validación','warning');
      return;
    }
    const data = await resp.json();
    if (!resp.ok || !data.success) throw new Error(data.message || 'Error al actualizar');
    document.getElementById('modalEditar').style.display='none';
    Swal.fire({
      icon: 'success',
      title: '¡Producto actualizado!',
      text: 'Cambios guardados correctamente',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true,
      willClose: () => {
        if (typeof window.loadProducts === 'function') {
          window.loadProducts();
        } else {
          location.reload();
        }
      }
    });
  } catch(e) { console.error(e); Swal.fire('Error', e.message || 'No se pudo actualizar', 'error'); }
}

async function eliminarProductoBotica(id) {
  try {
    // Si existe la función global del módulo original, reutilizarla para mantener diseño/flujo
    if (typeof window.eliminarProducto === 'function') {
      return window.eliminarProducto(id);
    }
    const ok = await Swal.fire({ icon:'warning', title:'Eliminar producto', text:'Esta acción no se puede deshacer', showCancelButton:true, confirmButtonText:'Eliminar', cancelButtonText:'Cancelar' });
    if (!ok.isConfirmed) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const resp = await fetch(`/inventario/producto/eliminar/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    if (!resp.ok) throw new Error('Error al eliminar');
    Swal.fire('Eliminado','El producto fue eliminado','success');
    load();
  } catch(e) { console.error(e); Swal.fire('Error','No se pudo eliminar','error'); }
}
// Helpers para errores de campo
function clearFieldErrors(form) {
  if (!form) return;
  form.querySelectorAll('.field-error').forEach(el => el.remove());
}
function showFieldError(form, fieldName, message) {
  if (!form) return;
  const field = form.querySelector(`[name="${fieldName}"]`);
  if (field) {
    const p = document.createElement('p');
    p.className = 'field-error text-red-500 text-sm mt-1';
    p.textContent = message;
    field.insertAdjacentElement('afterend', p);
  }
}
  function lockScroll(){
    try {
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
    } catch(e){}
  }
  function unlockScroll(){
    try {
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
      document.body.classList.remove('overlay-active');
    } catch(e){}
  }
