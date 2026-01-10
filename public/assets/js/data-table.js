if (document.getElementById("selection-table") && typeof simpleDatatables.DataTable !== 'undefined') {
    let table = null;

    const resetTable = function () {
        if (table) {
            table.destroy();
        }

        const options = {
            searchable: false, // Desactivamos la búsqueda predeterminada
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [5, 10, 15, 20, 25],
            labels: {
                placeholder: "Buscar...",
                perPage: "Registros por página",
                noRows: `
                    <div class="flex flex-col items-center justify-center p-8">
                        <div class="bg-gray-50 rounded-full p-3 mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M8 16l-4-4m0 0l4-4m-4 4h12"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">No se encontraron registros</h3>
                        <p class="text-sm text-gray-500 text-center mt-2">
                            No hay productos que coincidan con los criterios de búsqueda.
                        </p>
                    </div>`,
                info: "Mostrando {start} a {end} de {rows} registros",
                loading: "Cargando...",
                previous: "Anterior",
                next: "Siguiente",
            },
            columns: [
                { select: [5], sortable: false } // Columna de acciones no ordenable
            ]
        };

        table = new simpleDatatables.DataTable("#selection-table", options);

        // Implementar búsqueda personalizada
        const searchInput = document.getElementById('searchInput');
        const filterEstado = document.getElementById('filterEstado');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const estado = filterEstado.value.toLowerCase();
            
            table.rows().forEach(row => {
                const rowData = row.children;
                const productName = rowData[1].textContent.toLowerCase();
                const productId = rowData[0].textContent.toLowerCase();
                const estadoText = rowData[4].textContent.toLowerCase().trim();

                const matchesSearch = productName.includes(searchTerm) || 
                                   productId.includes(searchTerm);
                const matchesFilter = estado === 'todos' || estadoText === estado;

                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });

            updateCounter();
        }

        function updateCounter() {
            const totalRows = table.rows().length;
            const visibleRows = Array.from(table.rows()).filter(row => 
                row.style.display !== 'none'
            ).length;

            const counter = document.getElementById('filter-counter');
            if (counter) {
                const estado = filterEstado.value;
                const estadoText = estado === 'todos' ? 'total' : `en estado "${estado}"`;
                counter.textContent = `Mostrando ${visibleRows} de ${totalRows} productos ${estadoText}`;
            }
        }

        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }
        if (filterEstado) {
            filterEstado.addEventListener('change', filterTable);
        }

        // Inicializar contador
        updateCounter();
    };

    resetTable();
}