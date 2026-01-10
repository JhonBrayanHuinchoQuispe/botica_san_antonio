'use strict';

(function(){
  try {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    const main = document.querySelector('.dashboard-main');
    function readCookie(name){
      try {
        const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
      } catch(e){ return null; }
    }
    function getPersisted(){
      const c = readCookie('sidebar_collapsed');
      if (c === '1' || c === '0') return c === '1';
      const ls = localStorage.getItem('sidebar_collapsed');
      return ls === '1';
    }
    const collapsed = getPersisted();
    if (collapsed) {
      if (toggle) toggle.classList.add('active');
      if (sidebar) sidebar.classList.add('active');
      if (main) main.classList.add('active');
    } else {
      if (toggle) toggle.classList.remove('active');
      if (sidebar) sidebar.classList.remove('active');
      if (main) main.classList.remove('active');
    }
    try {
      let t0 = Date.now();
      const ensure = () => {
        const wantCollapsed = getPersisted();
        const hasCollapsed = sidebar && sidebar.classList.contains('active');
        if (wantCollapsed !== hasCollapsed) {
          if (wantCollapsed) {
            if (toggle) toggle.classList.add('active');
            if (sidebar) sidebar.classList.add('active');
            if (main) main.classList.add('active');
          } else {
            if (toggle) toggle.classList.remove('active');
            if (sidebar) sidebar.classList.remove('active');
            if (main) main.classList.remove('active');
          }
        }
      };
      const id = setInterval(function(){
        ensure();
        if (Date.now() - t0 > 4000) clearInterval(id);
      }, 200);
      requestAnimationFrame(ensure);
      document.addEventListener('turbo:load', ensure);
      document.addEventListener('turbo:render', ensure);
      window.addEventListener('pageshow', ensure);
    } catch(e) {}
  } catch(e) {}
  try {
    window.addEventListener('beforeunload', function(){
      try {
        const sb = document.querySelector('.sidebar');
        const isCollapsed = sb && sb.classList.contains('active');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
        document.cookie = 'sidebar_collapsed=' + (isCollapsed ? '1' : '0') + ';path=/;max-age=31536000';
      } catch(e) {}
    });
    document.addEventListener('turbo:before-visit', function(){
      try {
        const sb = document.querySelector('.sidebar');
        const isCollapsed = sb && sb.classList.contains('active');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
        document.cookie = 'sidebar_collapsed=' + (isCollapsed ? '1' : '0') + ';path=/;max-age=31536000';
      } catch(e) {}
    });
  } catch(e) {}
})();

// Ensure sidebar toggle functionality works properly
document.addEventListener('DOMContentLoaded', function() {
    try {
        const preloader = document.getElementById('preloader');
        if (preloader) { preloader.remove(); }
        document.body.classList.remove('overlay-active');
        document.querySelectorAll('.modal-overlay, .modal-overlay-estante').forEach(function(el){
            el.classList.add('hidden');
            el.style.display = 'none';
        });
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    } catch(_) {}
    // Double-check sidebar toggle functionality after DOM is loaded
    const sidebarToggle = document.querySelector(".sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");
    const dashboardMain = document.querySelector(".dashboard-main");

    function applySidebarStateFromStorage(){
      try {
        const persisted = localStorage.getItem('sidebar_collapsed');
        const collapsed = persisted === '1';
        if (collapsed) {
          if (sidebarToggle) sidebarToggle.classList.add('active');
          if (sidebar) sidebar.classList.add('active');
          if (dashboardMain) dashboardMain.classList.add('active');
        } else {
          if (sidebarToggle) sidebarToggle.classList.remove('active');
          if (sidebar) sidebar.classList.remove('active');
          if (dashboardMain) dashboardMain.classList.remove('active');
        }
      } catch(e) {}
    }
    applySidebarStateFromStorage();
    try {
      if (sidebar) {
        const obs = new MutationObserver(function(){
          try {
            const should = localStorage.getItem('sidebar_collapsed') === '1';
            const has = sidebar.classList.contains('active');
            if (should !== has) applySidebarStateFromStorage();
          } catch(e) {}
        });
        obs.observe(sidebar, { attributes:true, attributeFilter:['class'] });
      }
    } catch(e) {}
            try {
      const persisted = localStorage.getItem('sidebar_collapsed');
      if (persisted === '1') {
        if (sidebarToggle) sidebarToggle.classList.add('active');
        if (sidebar) sidebar.classList.add('active');
        if (dashboardMain) dashboardMain.classList.add('active');
      }
    } catch(e) {}
    
    if (sidebarToggle && !sidebarToggle.hasAttribute('data-initialized')) {
        sidebarToggle.setAttribute('data-initialized', 'true');
        
        // Remove any existing listeners to prevent duplicates
        const newToggle = sidebarToggle.cloneNode(true);
        sidebarToggle.parentNode.replaceChild(newToggle, sidebarToggle);
        
        newToggle.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                this.classList.toggle("active");
                
                if (sidebar) {
                    sidebar.classList.toggle("active");
                }
                
                if (dashboardMain) {
                    dashboardMain.classList.toggle("active");
                }
                
                // Force reflow
                if (sidebar) {
                    sidebar.offsetHeight;
                }
                
                console.log('Sidebar toggled successfully');
                try {
                  const isCollapsed = sidebar && sidebar.classList.contains('active');
                  localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
                } catch(_) {}
                
            } catch (error) {
                console.error('Error in sidebar toggle:', error);
            }
        });
    }
});

// === Gestión global del scroll en modales/alerts ===
(function(){
  let __adjustingScroll = false;
  let __lastInteraction = Date.now();
  function lockScroll(){
    try { document.documentElement.style.overflow='hidden'; document.body.style.overflow='hidden'; } catch(e){}
  }
  function unlockScroll(){
    try {
      document.documentElement.style.overflow='';
      document.body.style.overflow='';
      document.body.classList.remove('modal-open');
      document.body.classList.remove('overlay-active');
    } catch(e){}
  }
  function isBlockingOverlayOpen(){
    try {
      const ids = ['modalAgregar','modalEditar','modalDetallesBotica','modalDetalles'];
      for (const id of ids){ const el = document.getElementById(id); if (el && el.style.display !== 'none' && !el.classList.contains('hidden')) return true; }
      const estantes = document.querySelectorAll('.modal-overlay-estante:not(.hidden)'); if (estantes.length) return true;
      const swal = document.querySelector('.swal2-container'); if (swal && getComputedStyle(swal).display !== 'none') return true;
      if (document.body.classList.contains('overlay-active')) return true;
    } catch(e){}
    return false;
  }
  function ensureScroll(){
    if (__adjustingScroll) return;
    if (!isBlockingOverlayOpen()) {
      const needsUnlock = (
        document.documentElement.style.overflow !== '' ||
        document.body.style.overflow !== '' ||
        document.body.classList.contains('modal-open') ||
        document.body.classList.contains('overlay-active')
      );
      if (needsUnlock) {
        __adjustingScroll = true;
        unlockScroll();
        requestAnimationFrame(() => { __adjustingScroll = false; });
      }
    }
  }
  function safetyUnblockUI(){
    try {
      const now = Date.now();
      if (now - __lastInteraction > 3000) {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay && getComputedStyle(overlay).display !== 'none') {
          overlay.style.display = 'none';
        }
        const preloader = document.getElementById('preloader');
        if (preloader && preloader.style.visibility !== 'hidden') {
          preloader.style.opacity = '0';
          preloader.style.visibility = 'hidden';
          preloader.style.pointerEvents = 'none';
          try { setTimeout(()=>{ if (preloader.parentNode) preloader.remove(); }, 300); } catch(e){}
        }
        unlockScroll();
      }
    } catch(e){}
  }
  // Parchear SweetAlert2 para no dejar el body sin scroll
  if (window.Swal && typeof window.Swal.fire === 'function') {
    const _fire = window.Swal.fire.bind(window.Swal);
    window.Swal.fire = function(opts){
      const o = (typeof opts === 'object') ? opts : { title: String(opts) };
      const prevOpen = o.didOpen; const prevClose = o.didClose;
      o.didOpen = function(el){ lockScroll(); if (typeof prevOpen === 'function') prevOpen(el); };
      o.didClose = function(el){ unlockScroll(); if (typeof prevClose === 'function') prevClose(el); };
      const p = _fire(o);
      if (p && typeof p.finally === 'function') { p.finally(unlockScroll); }
      return p;
    };
  }
  // Exponer utilidades globales para ser usadas por módulos de inventario
  window.UIFix = window.UIFix || {
    lockScroll,
    unlockScroll,
    ensureScrollNow(){ try {
      document.documentElement.style.overflow = 'auto';
      document.body.style.overflow = 'auto';
      document.body.classList.remove('modal-open');
      document.body.classList.remove('overlay-active');
    } catch(e){} }
  };
  // Seguridad adicional al navegar
  window.addEventListener('turbo:load', unlockScroll);
  window.addEventListener('beforeunload', unlockScroll);
  window.addEventListener('popstate', unlockScroll);

  // Observadores para evitar que overflow quede bloqueado por librerías (focus-trap, etc.)
  try {
    const obs = new MutationObserver(() => ensureScroll());
    obs.observe(document.documentElement, { attributes:true, attributeFilter:['class']});
    obs.observe(document.body, { attributes:true, attributeFilter:['class']});
  } catch(e){}

  // Fallback en interacciones comunes
  let __tickScheduled = false;
  function scheduleEnsure(){ if (!__tickScheduled){ __tickScheduled = true; requestAnimationFrame(()=>{ __tickScheduled = false; ensureScroll(); }); } }
  function updateLastInteraction(){ __lastInteraction = Date.now(); }
  document.addEventListener('click', function(e){ updateLastInteraction(); scheduleEnsure(); }, true);
  document.addEventListener('keyup', function(e){ updateLastInteraction(); scheduleEnsure(); }, true);
  document.addEventListener('pointerdown', function(e){ updateLastInteraction(); scheduleEnsure(); }, true);
  document.addEventListener('scroll', function(e){ scheduleEnsure(); }, true);
  setInterval(safetyUnblockUI, 2000);
})();

// === Reinicialización con Turbo Drive ===
function initSidebarUIWithTurbo() {
  try {
    // Resetear estado para evitar residuos entre páginas
    try {
      const toggle = document.querySelector('.sidebar-toggle');
      const sidebar = document.querySelector('.sidebar');
      const dashboardMain = document.querySelector('.dashboard-main');
      document.body.classList.remove('overlay-active');
      if (sidebar) sidebar.classList.remove('sidebar-open');
      
      document.querySelectorAll('.sidebar-menu .dropdown').forEach(function(dd){
        dd.classList.remove('dropdown-open','open','show');
        const sub = dd.querySelector('.sidebar-submenu');
        if (sub) sub.style.display = 'none';
      });
    } catch (e) {}

    const sidebar = document.querySelector('.sidebar');
    const dashboardMain = document.querySelector('.dashboard-main');
    try {
      const persisted = localStorage.getItem('sidebar_collapsed');
      const toggle = document.querySelector('.sidebar-toggle');
      if (persisted === '1') {
        if (toggle) toggle.classList.add('active');
        if (sidebar) sidebar.classList.add('active');
        if (dashboardMain) dashboardMain.classList.add('active');
      } else {
        if (toggle) toggle.classList.remove('active');
        if (sidebar) sidebar.classList.remove('active');
        if (dashboardMain) dashboardMain.classList.remove('active');
      }
    } catch(_) {}
    try {
      if (sidebar) {
        const obs = new MutationObserver(function(){
          try {
            const should = localStorage.getItem('sidebar_collapsed') === '1';
            const has = sidebar.classList.contains('active');
            if (should !== has) {
              const toggle = document.querySelector('.sidebar-toggle');
              if (should) {
                if (toggle) toggle.classList.add('active');
                sidebar.classList.add('active');
                if (dashboardMain) dashboardMain.classList.add('active');
              } else {
                if (toggle) toggle.classList.remove('active');
                sidebar.classList.remove('active');
                if (dashboardMain) dashboardMain.classList.remove('active');
              }
            }
          } catch(e) {}
        });
        obs.observe(sidebar, { attributes:true, attributeFilter:['class'] });
      }
    } catch(e) {}

    // Toggle principal
    const toggleBtn = document.querySelector('.sidebar-toggle');
    if (toggleBtn) {
      const newToggle = toggleBtn.cloneNode(true);
      toggleBtn.parentNode.replaceChild(newToggle, toggleBtn);
      newToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
      try {
        this.classList.toggle('active');
        if (sidebar) sidebar.classList.toggle('active');
        if (dashboardMain) dashboardMain.classList.toggle('active');
        if (sidebar) sidebar.offsetHeight;
        try {
          const isCollapsed = sidebar && sidebar.classList.contains('active');
          localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
          document.cookie = 'sidebar_collapsed=' + (isCollapsed ? '1' : '0') + ';path=/;max-age=31536000';
        } catch(_) {}
      } catch (err) { console.error('Sidebar toggle err', err); }
    });
  }

    // Toggle móvil
    const mobileToggle = document.querySelector('.sidebar-mobile-toggle');
    if (mobileToggle) {
      const newMobile = mobileToggle.cloneNode(true);
      mobileToggle.parentNode.replaceChild(newMobile, mobileToggle);
      newMobile.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        try {
          if (sidebar) sidebar.classList.add('sidebar-open');
          document.body.classList.add('overlay-active');
        } catch (err) { console.error('Mobile toggle err', err); }
      });
    }

    // Botón cerrar
    const closeBtn = document.querySelector('.sidebar-close-btn');
    if (closeBtn) {
      const newClose = closeBtn.cloneNode(true);
      closeBtn.parentNode.replaceChild(newClose, closeBtn);
      newClose.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        try {
          if (sidebar) sidebar.classList.remove('sidebar-open');
          document.body.classList.remove('overlay-active');
        } catch (err) { console.error('Close sidebar err', err); }
      });
    }

    // Dropdowns del sidebar
    document.querySelectorAll('.sidebar-menu .dropdown > a').forEach(function(link){
      const newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);
      newLink.addEventListener('click', function(event){
        if (this.getAttribute('href') === 'javascript:void(0)') {
          event.preventDefault();
          const parentLi = this.parentElement;
          const submenu = parentLi.querySelector('.sidebar-submenu');
          const isOpening = !parentLi.classList.contains('dropdown-open');
          document.querySelectorAll('.sidebar-menu .dropdown').forEach(function(other){
            if (other !== parentLi) {
              other.classList.remove('dropdown-open','open');
              const otherSub = other.querySelector('.sidebar-submenu');
              if (otherSub) otherSub.style.display = 'none';
            }
          });
          if (submenu) {
            parentLi.classList.toggle('dropdown-open', isOpening);
            parentLi.classList.toggle('open', isOpening);
            submenu.style.display = isOpening ? 'block' : 'none';
          }
        }
      });
    });
  } catch (error) {
    console.error('initSidebarUIWithTurbo error', error);
  }
}

document.addEventListener('turbo:load', initSidebarUIWithTurbo);
document.addEventListener('turbo:render', initSidebarUIWithTurbo);
window.addEventListener('pageshow', function(){ try { initSidebarUIWithTurbo(); } catch(e){} });
document.addEventListener('turbo:before-cache', function(){
  try {
    document.body.classList.remove('overlay-active');
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.remove('sidebar-open');
  } catch (e) {}
});

// sidebar submenu collapsible js
function setupSidebarSubmenus(){
  const anchors = document.querySelectorAll('.sidebar-menu .dropdown > a');
  anchors.forEach(function(a){
    const href = a.getAttribute('href') || '';
    if (href === 'javascript:void(0)'){
      const fresh = a.cloneNode(true);
      a.parentNode.replaceChild(fresh, a);
      fresh.addEventListener('click', function(event){
        event.preventDefault();
        event.stopPropagation();
        const parentLi = this.parentElement;
        const submenu = parentLi.querySelector('.sidebar-submenu');
        const isOpening = !parentLi.classList.contains('dropdown-open');
        document.querySelectorAll('.sidebar-menu .dropdown').forEach(function(other){
          if (other !== parentLi){
            other.classList.remove('dropdown-open','open');
            const otherSub = other.querySelector('.sidebar-submenu');
            if (otherSub) otherSub.style.display = 'none';
          }
        });
        if (submenu){
          try {
            const sidebar = document.querySelector('.sidebar');
            if (isOpening && sidebar && sidebar.classList.contains('active')){
              sidebar.classList.remove('active');
              const toggleBtn = document.querySelector('.sidebar-toggle');
              if (toggleBtn) toggleBtn.classList.remove('active');
              try { localStorage.setItem('sidebar_collapsed','0'); document.cookie = 'sidebar_collapsed=0;path=/;max-age=31536000'; } catch(_){}
            }
          } catch(_){}
          parentLi.classList.toggle('dropdown-open', isOpening);
          parentLi.classList.toggle('open', isOpening);
          submenu.style.display = isOpening ? 'block' : 'none';
        }
      });
    }
  });
}

document.addEventListener('DOMContentLoaded', function(){ try { setupSidebarSubmenus(); ensureActiveSubmenuOpen(); } catch(_){}});
document.addEventListener('turbo:load', function(){ try { setupSidebarSubmenus(); ensureActiveSubmenuOpen(); document.body.classList.remove('overlay-active'); } catch(_){}});
document.addEventListener('turbo:render', function(){ try { setupSidebarSubmenus(); ensureActiveSubmenuOpen(); document.body.classList.remove('overlay-active'); } catch(_){}});
window.addEventListener('pageshow', function(){ try { setupSidebarSubmenus(); ensureActiveSubmenuOpen(); } catch(_){} });

// Toggle sidebar visibility and active class
const sidebarToggle = document.querySelector(".sidebar-toggle");
  if(sidebarToggle) {
    sidebarToggle.addEventListener("click", function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Toggle classes with error handling
    try {
      this.classList.toggle("active");
      
      const sidebar = document.querySelector(".sidebar");
      const dashboardMain = document.querySelector(".dashboard-main");
      
      if (sidebar) {
        sidebar.classList.toggle("active");
      }
      
      if (dashboardMain) {
        dashboardMain.classList.toggle("active");
      }
      
      // Force a reflow to ensure CSS transitions work properly
      if (sidebar) {
        sidebar.offsetHeight;
      }
      
      try {
        const isCollapsed = sidebar && sidebar.classList.contains('active');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
        document.cookie = 'sidebar_collapsed=' + (isCollapsed ? '1' : '0') + ';path=/;max-age=31536000';
      } catch(_) {}
      
    } catch (error) {
      console.error('Error toggling sidebar:', error);
    }
  });
}

// Open sidebar in mobile view and add overlay
const sidebarMobileToggle = document.querySelector(".sidebar-mobile-toggle");
if(sidebarMobileToggle) {
  sidebarMobileToggle.addEventListener("click", function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    try {
      const sidebar = document.querySelector(".sidebar");
      if (sidebar) {
        sidebar.classList.add("sidebar-open");
        document.body.classList.add("overlay-active");
      }
    } catch (error) {
      console.error('Error opening mobile sidebar:', error);
    }
  });
}

// Close sidebar and remove overlay
const sidebarCloseBtn = document.querySelector(".sidebar-close-btn");
if(sidebarCloseBtn){
  sidebarCloseBtn.addEventListener("click", function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    try {
      const sidebar = document.querySelector(".sidebar");
      if (sidebar) {
        sidebar.classList.remove("sidebar-open");
        document.body.classList.remove("overlay-active");
      }
    } catch (error) {
      console.error('Error closing sidebar:', error);
    }
  });
}

//to keep the current page active
document.addEventListener("DOMContentLoaded", function () {
  var nk = window.location.href;
  var links = document.querySelectorAll("ul#sidebar-menu a");

  links.forEach(function (link) {
    if (link.href === nk) {
      link.classList.add("active-page"); // anchor
      var parent = link.parentElement;
      parent.classList.add("active-page"); // li

      // Traverse up the DOM tree and add classes to parent elements
      while (parent && parent.tagName !== "BODY") {
        if (parent.tagName === "LI") {
          parent.classList.add("show");
          parent.classList.add("open");
           // Add dropdown-open class if it's a dropdown
           if (parent.classList.contains('dropdown')) {
            parent.classList.add('dropdown-open');
          }
        }
        parent = parent.parentElement;
      }
    }
  });

  // Special handling for Almacén submenu based on URL hash
  handleAlmacenSubmenuActive();
  ensureActiveSubmenuOpen();
});

// Garantiza que el submenú esté expandido si hay un hijo activo
function ensureActiveSubmenuOpen(){
  try {
    var activeSubLinks = document.querySelectorAll("ul#sidebar-menu .sidebar-submenu a.active-page");
    activeSubLinks.forEach(function(link){
      var li = link.parentElement;
      if (li) li.classList.add("active-page");
      var dropdown = link.closest(".dropdown");
      if (dropdown) {
        try {
          var sidebar = document.querySelector(".sidebar");
          if (sidebar && sidebar.classList.contains("active")) {
            sidebar.classList.remove("active");
            var toggleBtn = document.querySelector(".sidebar-toggle");
            if (toggleBtn) toggleBtn.classList.remove("active");
            try { localStorage.setItem("sidebar_collapsed","0"); document.cookie = "sidebar_collapsed=0;path=/;max-age=31536000"; } catch(_){}
          }
        } catch(_){}
        dropdown.classList.add("dropdown-open","open","show");
        var submenu = dropdown.querySelector(".sidebar-submenu");
        if (submenu) submenu.style.display = "block";
        var parentA = dropdown.querySelector(":scope > a");
        if (parentA) parentA.classList.add("active-page");
      }
    });
  } catch(_) {}
}
try {
  document.addEventListener('DOMContentLoaded', function(){
    var sidebarLinks = document.querySelectorAll('#sidebar-menu a[href]');
    sidebarLinks.forEach(function(link){
      var href = link.getAttribute('href') || '';
      if (href && href !== '#' && href !== 'javascript:void(0)') {
        link.addEventListener('click', function(){
          try {
            document.body.classList.remove('overlay-active');
            document.body.classList.remove('modal-open');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            document.querySelectorAll('.modal-overlay, .modal-overlay-estante').forEach(function(el){
              el.classList.add('hidden');
              el.style.display = 'none';
            });
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
              sidebar.classList.remove('sidebar-open');
            }
          } catch(_) {}
        }, { once:false });
      }
    });
  });
} catch(_) {}

// Function to handle Almacén submenu active states based on hash
function handleAlmacenSubmenuActive() {
  // Check if we're on the ubicaciones/mapa page
  if (window.location.pathname.includes('/ubicaciones/mapa')) {
    const currentHash = window.location.hash;
    
    // Remove active-page class from all Almacén submenu items
    const almacenSubmenuLinks = document.querySelectorAll('a[href*="#mapa"], a[href*="#productos-ubicados"], a[href*="#productos-sin-ubicar"]');
    almacenSubmenuLinks.forEach(function(link) {
      link.classList.remove('active-page');
      link.parentElement.classList.remove('active-page');
    });
    
    // Activate the correct submenu item based on hash
    let targetLink = null;
    if (currentHash === '#productos-ubicados') {
      targetLink = document.querySelector('a[href*="#productos-ubicados"]');
    } else if (currentHash === '#productos-sin-ubicar') {
      targetLink = document.querySelector('a[href*="#productos-sin-ubicar"]');
    } else {
      // Default to "Mapa del Almacén" if no hash or #mapa
      targetLink = document.querySelector('a[href*="#mapa"]');
    }
    
    if (targetLink) {
      targetLink.classList.add('active-page');
      targetLink.parentElement.classList.add('active-page');
      
      // Make sure the Almacén dropdown is open
      const almacenDropdown = targetLink.closest('.dropdown');
      if (almacenDropdown) {
        almacenDropdown.classList.add('dropdown-open', 'open', 'show');
        const submenu = almacenDropdown.querySelector('.sidebar-submenu');
        if (submenu) {
          submenu.style.display = 'block';
        }
      }
    }
  }
}

// Listen for hash changes to update active menu
window.addEventListener('hashchange', function() {
  handleAlmacenSubmenuActive();
});




// On page load or when changing themes, best to add inline in `head` to avoid FOUC
// SIEMPRE iniciar en modo claro por defecto - solo usar modo oscuro si está explícitamente guardado
localStorage.setItem('color-theme', 'light');
document.documentElement.classList.remove('dark');
document.documentElement.classList.add('light');

// light dark version js
var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
var themeToggleBtn = document.getElementById('theme-toggle');

// Disable theme toggle logic as button is removed
if (themeToggleBtn) {
    themeToggleBtn.style.display = 'none';
}

// 🧠 PRELOADER INTELIGENTE - Backup para casos especiales
// Nota: El preloader principal se maneja inline en el HTML para máxima velocidad
(function() {
    const preloader = document.getElementById('preloader');
    
    if (preloader) {
        // Helpers globales para control manual del preloader desde cualquier módulo
        window.Preloader = {
            show() {
                const el = document.getElementById('preloader');
                if (!el) return;
                el.style.opacity = '1';
                el.style.visibility = 'visible';
                el.style.display = 'flex';
            },
            hide() {
                const el = document.getElementById('preloader');
                if (!el) return;
                el.style.transition = 'all 0.15s ease-out';
                el.style.opacity = '0';
                el.style.visibility = 'hidden';
                el.style.transform = 'scale(0.95)';
                el.style.pointerEvents = 'none';
            }
        };
        // Solo actuar si el preloader inline no funcionó (casos raros)
        setTimeout(() => {
            if (preloader && preloader.style.opacity !== '0') {
                console.log('🔧 Backup preloader logic activado');
                
                preloader.style.transition = 'all 0.15s ease-out';
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
                preloader.style.transform = 'scale(0.95)';
                preloader.style.pointerEvents = 'none';
                
                setTimeout(() => {
                    if (preloader.parentNode) {
                        preloader.remove();
                    }
                }, 150);
            }
        }, 100);
    }
})();

// Progressive image loading for better perceived performance
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
});
