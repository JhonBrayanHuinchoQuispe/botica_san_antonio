/**
 * ===== SISTEMA DE BÚSQUEDA DE NAVEGACIÓN =====
 * Busca y navega directamente a los menús y submenús del sidebar
 * Implementado por: Sistema de Botica
 */

class NavbarSearch {
    constructor() {
        this.searchInput = document.getElementById('navbar-search-input');
        this.searchResults = document.getElementById('search-results');
        this.searchResultsContent = document.getElementById('search-results-content');
        this.searchNoResults = document.getElementById('search-no-results');
        
        this.menuItems = [];
        this.currentIndex = -1;
        this.debounceTimer = null;
        
        this.init();
    }

    init() {
        if (!this.searchInput) return;
        
        this.extractMenuItems();
        this.bindEvents();
        console.log('🔍 Sistema de búsqueda jerárquica inicializado');
        console.log(`📋 ${this.menuItems.length} elementos extraídos`);
    }

    /**
     * Extrae todos los menús y submenús del sidebar
     */
    extractMenuItems() {
        const sidebar = document.getElementById('sidebar-menu');
        if (!sidebar) return;

        this.menuItems = [];
        
        // Buscar todos los enlaces del sidebar
        const links = sidebar.querySelectorAll('a[href]:not([href="javascript:void(0)"])');
        
        links.forEach(link => {
            const text = this.getCleanText(link);
            const href = link.getAttribute('href');
            const parentGroup = this.getParentGroup(link);
            const isSubmenu = this.isSubmenuItem(link);
            const icon = this.getIcon(link);
            
            if (text && href && href !== '#') {
                this.menuItems.push({
                    text: text,
                    href: href,
                    parentGroup: parentGroup,
                    isSubmenu: isSubmenu,
                    icon: icon,
                    element: link,
                    searchText: `${parentGroup} ${text}`.toLowerCase()
                });
            }
        });

        // Ordenar: primero los elementos principales, luego los submenús
        this.menuItems.sort((a, b) => {
            if (a.isSubmenu !== b.isSubmenu) {
                return a.isSubmenu ? 1 : -1;
            }
            return a.text.localeCompare(b.text);
        });
    }

    /**
     * Obtiene el texto limpio del enlace
     */
    getCleanText(element) {
        // Primero intentar obtener el texto del span
        const spanElement = element.querySelector('span');
        if (spanElement) {
            return spanElement.textContent.trim();
        }
        
        // Si no hay span, obtener todo el texto y limpiar iconos
        let text = element.textContent || element.innerText || '';
        
        // Limpiar espacios múltiples y trim
        text = text.replace(/\s+/g, ' ').trim();
        
        // Si el texto contiene íconos al inicio (como círculos), removerlos
        text = text.replace(/^[●○◯◉◎⚫⚪🔵🔴🟡🟢🟠🟣⭕]\s*/, '');
        
        return text;
    }

    /**
     * Obtiene el grupo padre del elemento
     */
    getParentGroup(element) {
        let current = element.closest('li');
        
        // Si es un submenú, buscar el dropdown padre
        if (this.isSubmenuItem(element)) {
            const submenu = element.closest('.sidebar-submenu');
            if (submenu) {
                const parentDropdown = submenu.closest('.dropdown');
                if (parentDropdown) {
                    const parentLink = parentDropdown.querySelector('a[href="javascript:void(0)"]');
                    if (parentLink) {
                        return this.getCleanText(parentLink);
                    }
                }
            }
        }
        
        // Buscar el título del grupo más cercano
        while (current && current.previousElementSibling) {
            current = current.previousElementSibling;
            if (current.classList.contains('sidebar-menu-group-title')) {
                return current.textContent.trim();
            }
        }
        
        return 'Navegación';
    }

    /**
     * Verifica si es un elemento de submenú
     */
    isSubmenuItem(element) {
        return element.closest('.sidebar-submenu') !== null;
    }

    /**
     * Obtiene el icono del elemento
     */
    getIcon(element) {
        // Buscar iconos en diferentes ubicaciones
        let iconElement = element.querySelector('iconify-icon');
        
        // Si no encuentra iconify-icon, buscar en el elemento padre (para submenús)
        if (!iconElement && this.isSubmenuItem(element)) {
            const parentDropdown = element.closest('.dropdown');
            if (parentDropdown) {
                iconElement = parentDropdown.querySelector('a[href="javascript:void(0)"] iconify-icon');
            }
        }
        
        // Si no encuentra iconify-icon, buscar íconos con clases
        if (!iconElement) {
            iconElement = element.querySelector('i[class*="ri-"], .menu-icon');
        }
        
        // Retornar el icono encontrado o uno por defecto
        if (iconElement) {
            if (iconElement.hasAttribute('icon')) {
                return iconElement.getAttribute('icon');
            }
            if (iconElement.className.includes('ri-')) {
                // Para íconos remix, convertir a iconify equivalente
                if (iconElement.className.includes('ri-circle-fill')) {
                    return 'solar:record-circle-bold';
                }
                return 'solar:menu-dots-bold';
            }
        }
        
        // Iconos por defecto basados en el tipo
        if (this.isSubmenuItem(element)) {
            return 'solar:arrow-right-linear';
        } else {
            return 'solar:widget-2-bold';
        }
    }

    // Funciones de badges removidas - diseño sin badges

    /**
     * Vincula los eventos necesarios
     */
    bindEvents() {
        // Evento de escritura en el input
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.handleSearch(e.target.value);
            }, 150);
        });

        // Eventos de teclado
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyDown(e);
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.searchResults.contains(e.target)) {
                this.hideResults();
            }
        });

        // Mostrar resultados al enfocar si hay texto
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.trim()) {
                this.handleSearch(this.searchInput.value);
            }
        });

        // Limpiar al perder foco (con delay para permitir clics)
        this.searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                if (!this.searchResults.matches(':hover')) {
                    this.hideResults();
                }
            }, 200);
        });
    }

    /**
     * Maneja la búsqueda
     */
    handleSearch(query) {
        query = query.trim().toLowerCase();
        
        if (query.length < 1) {
            this.hideResults();
            return;
        }

        const results = this.searchMenuItems(query);
        this.displayResults(results, query);
    }

    /**
     * Busca en los elementos del menú
     */
    searchMenuItems(query) {
        const results = [];
        const queryLower = query.toLowerCase();
        
        this.menuItems.forEach(item => {
            let score = 0;
            const itemText = item.text.toLowerCase();
            const parentText = item.parentGroup.toLowerCase();
            
            // Búsqueda ESTRICTA - solo desde el inicio
            let hasMatch = false;
            
            // 1. Coincidencia exacta completa (prioridad máxima)
            if (itemText === queryLower) {
                score = 150;
                hasMatch = true;
            }
            // 2. Coincidencia exacta al inicio del texto completo
            else if (itemText.startsWith(queryLower)) {
                score = 120;
                hasMatch = true;
            }
            // 3. Coincidencia al inicio de cualquier palabra del item
            else if (this.startsWithAnyWord(itemText, queryLower)) {
                score = 100;
                hasMatch = true;
            }
            // 4. Búsqueda en grupo padre SOLO para consultas de 2+ caracteres
            else if (queryLower.length >= 2 && this.startsWithAnyWord(parentText, queryLower)) {
                score = 50;
                hasMatch = true;
            }
            
            // Solo agregar si hay una coincidencia real desde el inicio
            if (hasMatch && score >= 100) {
                // Bonus por tipo de elemento
                if (item.isSubmenu) {
                    score += 10;
                } else {
                    score += 5;
                }
                
                results.push({
                    ...item,
                    score: score
                });
            }
        });

        // Ordenar por score descendente, luego por longitud del texto
        return results.sort((a, b) => {
            if (b.score !== a.score) {
                return b.score - a.score;
            }
            return a.text.length - b.text.length;
        });
    }

    /**
     * Verifica si la consulta coincide al inicio de cualquier palabra del texto
     */
    startsWithAnyWord(text, query) {
        const words = text.split(' ');
        return words.some(word => word.startsWith(query));
    }

    /**
     * Muestra los resultados de búsqueda
     */
    displayResults(results, query) {
        this.searchResultsContent.innerHTML = '';
        this.currentIndex = -1;
        
        if (results.length === 0) {
            this.searchNoResults.classList.remove('hidden');
            this.showResults();
            return;
        }
        
        this.searchNoResults.classList.add('hidden');
        
        // Agrupar resultados por grupo padre para mostrar jerarquía
        const groupedResults = {};
        let itemIndex = 0;
        
        // Priorizar submenús y agrupar por padre
        const subItems = results.filter(item => item.isSubmenu);
        const mainItems = results.filter(item => !item.isSubmenu);
        
        // Primero procesar submenús agrupados por su menú padre
        subItems.slice(0, 8).forEach(item => {
            const parentGroup = item.parentGroup;
            if (!groupedResults[parentGroup]) {
                groupedResults[parentGroup] = {
                    groupName: parentGroup,
                    items: [],
                    isSubmenuGroup: true
                };
            }
            groupedResults[parentGroup].items.push({...item, index: itemIndex++});
        });
        
        // Luego agregar menús principales si hay espacio
        if (Object.keys(groupedResults).length < 3) {
            mainItems.slice(0, 3).forEach(item => {
                const parentGroup = item.parentGroup;
                const groupKey = `main_${parentGroup}`;
                if (!groupedResults[groupKey]) {
                    groupedResults[groupKey] = {
                        groupName: parentGroup,
                        items: [],
                        isSubmenuGroup: false
                    };
                }
                groupedResults[groupKey].items.push({...item, index: itemIndex++});
            });
        }
        
        // Mostrar resultados agrupados
        Object.values(groupedResults).forEach((group, groupIndex) => {
            if (group.items.length > 0) {
                this.addGroupHeader(group.groupName);
                group.items.forEach(item => {
                    this.addHierarchicalResultItem(item, query);
                });
                
                // Agregar divisor entre grupos (excepto el último)
                if (groupIndex < Object.keys(groupedResults).length - 1) {
                    this.addDivider();
                }
            }
        });
        
        this.showResults();
    }

    /**
     * Agrega un header de grupo
     */
    addGroupHeader(title) {
        const header = document.createElement('div');
        header.className = 'group-header';
        header.innerHTML = `
            <h6>
                ${title}
            </h6>
        `;
        this.searchResultsContent.appendChild(header);
    }

    /**
     * Agrega un divisor
     */
    addDivider() {
        const divider = document.createElement('div');
        divider.className = 'search-divider';
        this.searchResultsContent.appendChild(divider);
    }

    /**
     * Agrega un elemento de resultado (función legacy - no se usa en el diseño actual)
     */
    addResultItem(item, query, index) {
        const resultItem = document.createElement('a');
        resultItem.href = item.href;
        resultItem.className = 'search-result-item';
        resultItem.dataset.index = index;
        
        let titleHtml, subtitleHtml;
        
        if (item.isSubmenu) {
            // Para submenús: mostrar el menú padre arriba y el submenú debajo
            titleHtml = this.highlightText(item.parentGroup, query);
            subtitleHtml = this.highlightText(item.text, query);
        } else {
            // Para menús principales: mostrar el menú y su categoría
            titleHtml = this.highlightText(item.text, query);
            subtitleHtml = this.highlightText(item.parentGroup, query);
        }
        
        resultItem.innerHTML = `
            <div class="search-result-icon">
                <iconify-icon icon="${item.icon}"></iconify-icon>
            </div>
            <div class="search-result-content">
                <h6 class="search-result-title">${titleHtml}</h6>
                <p class="search-result-path">${subtitleHtml}</p>
            </div>
        `;
        
        // Evento de clic
        resultItem.addEventListener('click', (e) => {
            this.navigateToItem(item);
            this.hideResults();
        });
        
        this.searchResultsContent.appendChild(resultItem);
    }

    /**
     * Agrega un elemento de resultado de manera jerárquica
     */
    addHierarchicalResultItem(item, query) {
        const resultItem = document.createElement('a');
        resultItem.href = item.href;
        resultItem.className = 'search-result-item hierarchical-item';
        resultItem.dataset.index = item.index;
        
        let titleHtml, subtitleHtml;
        
        // Para la vista jerárquica, mostrar solo el item específico
        titleHtml = this.highlightText(item.text, query);
        
        if (item.isSubmenu) {
            // Para submenús, no mostrar subtítulo repetitivo
            subtitleHtml = '';
        } else {
            subtitleHtml = this.highlightText(item.parentGroup, query);
        }
        
        // Solo mostrar icono para menús principales, no para submenús
        if (item.isSubmenu) {
            resultItem.innerHTML = `
                <div class="search-result-content">
                    <h6 class="search-result-title">${titleHtml}</h6>
                    ${subtitleHtml ? `<p class="search-result-path">${subtitleHtml}</p>` : ''}
                </div>
            `;
        } else {
            resultItem.innerHTML = `
                <div class="search-result-icon">
                    <iconify-icon icon="${item.icon}"></iconify-icon>
                </div>
                <div class="search-result-content">
                    <h6 class="search-result-title">${titleHtml}</h6>
                    ${subtitleHtml ? `<p class="search-result-path">${subtitleHtml}</p>` : ''}
                </div>
            `;
        }
        
        // Evento de clic
        resultItem.addEventListener('click', (e) => {
            this.navigateToItem(item);
            this.hideResults();
        });
        
        this.searchResultsContent.appendChild(resultItem);
    }

    /**
     * Resalta el texto de búsqueda
     */
    highlightText(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    }

    /**
     * Maneja la navegación por teclado
     */
    handleKeyDown(e) {
        const items = this.searchResults.querySelectorAll('.search-result-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentIndex = Math.min(this.currentIndex + 1, items.length - 1);
                this.updateActiveItem(items);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentIndex = Math.max(this.currentIndex - 1, -1);
                this.updateActiveItem(items);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.currentIndex >= 0 && items[this.currentIndex]) {
                    items[this.currentIndex].click();
                }
                break;
                
            case 'Escape':
                this.hideResults();
                this.searchInput.blur();
                break;
        }
    }

    /**
     * Actualiza el elemento activo
     */
    updateActiveItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle('active', index === this.currentIndex);
        });
        
        // Scroll al elemento activo
        if (this.currentIndex >= 0 && items[this.currentIndex]) {
            items[this.currentIndex].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }

    /**
     * Navega al elemento seleccionado
     */
    navigateToItem(item) {
        console.log(`🚀 Navegando a: ${item.text} (${item.href})`);
        
        // Limpiar el input
        this.searchInput.value = '';
        
        // Si es una URL externa o absoluta, navegar directamente
        if (item.href.startsWith('http') || item.href.startsWith('/')) {
            window.location.href = item.href;
            return;
        }
        
        // Para URLs relativas, asegurar navegación correcta
        window.location.href = item.href;
    }

    /**
     * Muestra los resultados
     */
    showResults() {
        this.searchResults.classList.remove('hidden');
        this.searchResults.classList.add('show');
    }

    /**
     * Oculta los resultados
     */
    hideResults() {
        this.searchResults.classList.add('hidden');
        this.searchResults.classList.remove('show');
        this.currentIndex = -1;
    }

    /**
     * Recargar los elementos del menú (útil para cambios dinámicos)
     */
    refresh() {
        this.extractMenuItems();
        console.log(`🔄 Menús actualizados: ${this.menuItems.length} elementos`);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.navbarSearch = new NavbarSearch();
});

// Reinicializar con Turbo Drive
document.addEventListener('turbo:load', function() {
    // Si existe, refrescar; si no, crear
    if (window.navbarSearch) {
        window.navbarSearch.refresh();
    } else {
        window.navbarSearch = new NavbarSearch();
    }
});

// Reinicializar si se actualiza el sidebar dinámicamente
window.refreshNavbarSearch = function() {
    if (window.navbarSearch) {
        window.navbarSearch.refresh();
    }
};