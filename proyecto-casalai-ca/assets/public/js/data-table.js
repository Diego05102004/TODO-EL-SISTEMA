(function () {
    'use strict';

    const tables = Array.from(document.querySelectorAll('table[data-datatable]'));
    if (tables.length === 0) {
        return;
    }

    function loadScript(src, isReady) {
        if (isReady()) {
            return Promise.resolve();
        }

        let script = Array.from(document.scripts).find(function (element) {
            return element.src.endsWith(src);
        });

        if (!script) {
            script = document.createElement('script');
            script.src = src;
            document.body.appendChild(script);
        }

        return new Promise(function (resolve, reject) {
            if (isReady()) {
                resolve();
                return;
            }
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', function () {
                reject(new Error('No se pudo cargar ' + src));
            }, { once: true });
        });
    }

    function parseIndexes(table, attribute) {
        const value = table.getAttribute(attribute);
        if (!value) {
            return [];
        }

        return value.split(',')
            .map(function (index) {
                return Number(index.trim());
            })
            .filter(function (index) {
                return Number.isInteger(index) && index >= 0;
            });
    }

    function parseOrder(table) {
        const value = table.getAttribute('data-datatable-order');
        if (!value) {
            return [];
        }

        try {
            const order = JSON.parse(value);
            if (!Array.isArray(order)) {
                throw new TypeError('El orden debe ser un array.');
            }
            return order;
        } catch (error) {
            console.error('Configuración data-datatable-order no válida:', error);
            return [];
        }
    }

    function initializeTables() {
        const $ = window.jQuery;

        tables.forEach(function (table) {
            if ($.fn.DataTable.isDataTable(table)) {
                return;
            }

            const configuredLength = Number(table.getAttribute('data-page-length'));
            const pageLength = Number.isInteger(configuredLength) &&
                (configuredLength === -1 || configuredLength > 0)
                ? configuredLength
                : 10;
            const columnDefs = [];
            const noOrder = parseIndexes(table, 'data-datatable-no-order');
            const noSearch = parseIndexes(table, 'data-datatable-no-search');

            if (noOrder.length > 0) {
                columnDefs.push({ targets: noOrder, orderable: false });
            }
            if (noSearch.length > 0) {
                columnDefs.push({ targets: noSearch, searchable: false });
            }

            $(table).DataTable({
                pageLength: pageLength,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                order: parseOrder(table),
                columnDefs: columnDefs,
                language: {
                    url: 'assets/public/js/es-ES.json'
                }
            });
        });
    }

    loadScript('assets/public/js/jquery-3.7.1.min.js', function () {
        return Boolean(window.jQuery);
    })
        .then(function () {
            return loadScript('assets/public/js/jquery.dataTables.min.js', function () {
                return Boolean(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable);
            });
        })
        .then(function () {
            return loadScript('assets/public/js/dataTables.bootstrap5.min.js', function () {
                return Boolean(
                    window.jQuery &&
                    window.jQuery.fn &&
                    window.jQuery.fn.dataTable &&
                    window.jQuery.fn.dataTable.ext.renderer.pageButton.bootstrap
                );
            });
        })
        .then(initializeTables)
        .catch(function (error) {
            console.error('No se pudieron inicializar las tablas DataTables:', error);
        });
})();
