/* backup-module.js - Versión corregida y unificada */
document.addEventListener('DOMContentLoaded', function () {
    const DEBUG = true;
    const dlog = (...args) => { if (DEBUG) console.log('[BACKUP]', ...args); };
    const BACKUP_CONTROLLER = 'Modelo/Controlador/backup.php';
    dlog('Inicializando módulo de respaldo...');

    /* ---------------------------
       Utilidades
       --------------------------- */
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0 || bytes === undefined || bytes === null) return '0 Bytes';
        const k = 1024;
        const dm = Math.max(0, decimals);
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(Math.abs(bytes)) / Math.log(k)) || 0;
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    async function toast(titulo, mensaje, tipo = 'success', tiempo = 3000) {
        await new Promise(resolve => setTimeout(resolve, 50));
 
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: tiempo,
            timerProgressBar: true,
            didOpen: (toastEl) => {
                toastEl.addEventListener('mouseenter', Swal.stopTimer);
                toastEl.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: tipo, title: titulo, text: mensaje });
    }

    async function mostrarDialogoResultado(resultado = {}, opciones = {}) {
        const success = Boolean(resultado.success);
        const titulo = success ? (opciones.tituloExito || 'Operación completada') : (opciones.tituloError || 'Error');
        const mensaje = resultado.message || resultado.error || (success ? (opciones.mensajeExito || 'La acción se realizó correctamente') : (opciones.mensajeError || 'Ocurrió un error inesperado'));
        const icono = success ? 'success' : 'error';
        const botonClase = success ? 'btn btn-success' : 'btn btn-danger';
        await new Promise(resolve => setTimeout(resolve, 50));
 
        return Swal.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Aceptar',
            customClass: { confirmButton: botonClase },
            buttonsStyling: false
        });
    }

    /* ---------------------------
       fetchJson robusto
       --------------------------- */
    async function fetchJson(url, options = {}) {
        // Forzar encabezado X-Requested-With y credentials por defecto
        const defaultOpts = {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        };
        const merged = Object.assign({}, defaultOpts, options);
        // Si options.headers existe, mezclar
        merged.headers = Object.assign({}, defaultOpts.headers, options.headers || {});

        let response, text;
        try {
            response = await fetch(url, merged);
            text = await response.text();
        } catch (err) {
            dlog('fetch error:', err);
            throw new Error('No se pudo conectar con el servidor.');
        }

        // Si la respuesta contiene HTML o warnings, lanzar error descriptivo
        const trimmed = text ? text.trim() : '';
        if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
            // Log completo para desarrollador
            console.error('Respuesta no JSON del backend:', text);
            // Guardar en localStorage si parece un error PHP (útil para detectar Class not found)
            if (trimmed.toLowerCase().includes('class') && trimmed.toLowerCase().includes('not found')) {
                localStorage.setItem('backupError', trimmed);
            }
            throw new Error('El servidor devolvió una salida no válida (HTML, warnings o errores PHP). Revisa el backend.');
        }

        let data;
        try {
            data = JSON.parse(trimmed);
        } catch (err) {
            console.error('Error parseando JSON:', err, trimmed);
            throw new Error('Respuesta JSON inválida del servidor.');
        }

        if (!response.ok || data.success === false) {
            const msg = data.error || data.message || 'Error desconocido retornado por el servidor';
            const error = new Error(msg);
            error.data = data;
            throw error;
        }

        return data;
    }

    /* ---------------------------
       Verificar errores de clase no encontrada
       (cuando backend deja warnings en pantalla)
       --------------------------- */
   async function verificarErroresClaseNoEncontrada() {
        const urlParams = new URLSearchParams(window.location.search);
        const errorUrl = urlParams.get('error');
        const errorStorage = localStorage.getItem('backupError');

        const mensajeError = errorUrl || errorStorage;
        if (!mensajeError) return;

        if (mensajeError.includes('Class "') && mensajeError.includes('" not found')) {
            const nombreClase = mensajeError.split('Class \"')[1]?.split('\" not found')[0] || 'Clase desconocida';
            Swal.fire({
                title: 'Error de Configuración',
                html: `No se pudo cargar la clase: <strong>${nombreClase}</strong><br><br>Verifica el autoloader de Composer.`,
                icon: 'error',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false
            });
            localStorage.removeItem('backupError');
        } else {
            // Si es otro tipo de HTML/warning, mostrar toast y log
            console.warn('Warning/HTML detectado en respuesta del backend:', mensajeError);
            toast('Advertencia', 'El servidor devolvió advertencias. Revisa la consola.', 'warning', 5000);
            localStorage.removeItem('backupError');
        }
    }

    verificarErroresClaseNoEncontrada();

    /* ---------------------------
       Render fila backup (HTML)
       Formato alineado con la vista backup.php
       --------------------------- */
    function crearFilaBackupHtml(backup) {
        const tamano = backup.tamano ? formatBytes(backup.tamano) : (backup.tamano_text || 'N/A');
        const fecha = backup.fecha_modificacion || backup.fecha || '';
        const nombre = backup.nombre || '';
        const tipo = backup.tipo || 'Principal';

        return `
            <tr>
                <td>
                    <span class="campo-nombres">
                        ${nombre}
                    </span>
                </td>
                <td>
                    <span class="campo-rango">
                        ${tipo}
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        ${tamano}
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        ${fecha}
                    </span>
                </td>
                <td>
                    <ul>
                        <button class="btn-restaurar"
                            data-archivo="${nombre}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Restaurar">
                            <img src="assets/img/rotate-ccw.svg" alt="Restaurar">
                        </button>
                        <button class="btn-descargar"
                            data-archivo="${nombre}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Descargar">
                            <img src="assets/img/download.svg" alt="Descargar">
                        </button>
                        <button class="btn-eliminar"
                            data-archivo="${nombre}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Eliminar">
                            <img src="assets/img/circle-x.svg" alt="Eliminar">
                        </button>
                    </ul>
                </td>
            </tr>
        `;
    }

    /* ---------------------------
       Actualizar tabla respaldos
       - Admite dos formatos:
         a) Array simple [ {nombre, tamano, fecha_modificacion, tipo}, ... ]
         b) { success: true, data: [...] }
       --------------------------- */
    let updating = false;
    async function actualizarTablaRespaldos() {
        if (updating) {
            dlog('Actualización ya en curso, se omite esta llamada');
            return;
        }
        updating = true;
        const tbody = document.querySelector('#tablaConsultas tbody') || document.querySelector('tablaConsultas tbody');

        if (!tbody) {
            dlog('No se encontró el tbody de la tabla de respaldos');
            updating = false;
            return;
        }

        tbody.innerHTML = `<tr><td colspan="5" class="text-center">Cargando respaldos...</td></tr>`;

        try {
            const data = await fetchJson(`${BACKUP_CONTROLLER}?accion=listar`, { method: 'GET' });
            // Data puede ser array o objeto {success:true, data: [...]}
            let lista = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
            if (!lista || lista.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center">No hay archivos de respaldo disponibles</td></tr>`;
            } else {
                tbody.innerHTML = lista.map(backup => crearFilaBackupHtml(backup)).join('');
            }

            // Inicializar tooltips Bootstrap (si existe bootstrap)
            if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
                const triggers = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                triggers.forEach(el => {
                    if (el._tooltipInstance) el._tooltipInstance.dispose();
                    el._tooltipInstance = new bootstrap.Tooltip(el);
                });
            }

            // Si usas DataTables, reinicializar
            if (window.jQuery && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable('tablaConsultas')) {
                $('#tablaConsultas').DataTable().destroy();
                setTimeout(() => {
                    $('#tablaConsultas').DataTable({
                        order: [[0, 'desc']],
                        responsive: true,
                        scrollX: true,
                        scrollCollapse: true,
                        language: { "url": "assets/public/js/es-ES.json" },
                        initComplete: function () {
                            var api = this.api();
                            api.columns.adjust();
                        }
                    });
                }, 10);
            }

        } catch (err) {
            console.error('Error al cargar los respaldos:', err);
            toast('Error', err.message || 'No se pudieron cargar los respaldos', 'error', 5000);
            // Si el backend devolvió advertencias guardadas, mostrarlas
            verificarErroresClaseNoEncontrada();
        } finally {
            updating = false;
        }
    }

    /* ---------------------------
       Generar respaldo (tipo 'S' o 'P')
       --------------------------- */
    async function generarRespaldo(tipo) {
        const tipoTexto = tipo === 'S' ? 'seguridad' : 'principal';
        const confirm = await Swal.fire({
            title: `Generar respaldo de ${tipoTexto}`,
            text: `¿Está seguro de generar un respaldo ${tipoTexto}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar'
        });
        if (!confirm.isConfirmed) return;

        Swal.fire({ title: 'Generando respaldo', text: 'Por favor espere...', allowOutsideClick: false, didOpen: Swal.showLoading });

        try {
            // Usar fetch con GET o POST según tu backend; aquí usamos GET similar al original
            const data = await fetchJson(`${BACKUP_CONTROLLER}?accion=generar&tipo=${encodeURIComponent(tipo)}`, { method: 'GET' });
            // Si el backend devuelve {archivo, fecha, tamano, tipo}
            const info = {
                nombre: data.archivo || data.data?.archivo || 'desconocido',
                tipo: data.tipo || 'Principal',
                tamano: data.tamano || null,
                fecha_modificacion: data.fecha || data.fecha_modificacion || ''
            };
            // Agregar fila manualmente
            const tbody = document.querySelector('#tablaConsultas tbody') || document.querySelector('tablaConsultas tbody');
            if (tbody) {
                // Inserta al inicio
                tbody.insertAdjacentHTML('afterbegin', crearFilaBackupHtml(info));
            }
            await mostrarDialogoResultado({ success: true, message: data.message || 'Respaldo generado correctamente' }, { tituloExito: '¡Éxito!' });
            actualizarTablaRespaldos();
        } catch (err) {
            console.error('Error al generar respaldo:', err);
            await mostrarDialogoResultado({ success: false, error: err.message || 'Error al generar respaldo' }, { tituloError: 'Error' });
        } finally {
            Swal.close();
        }
    }

    /* ---------------------------
       Delegación global para botones (descargar, restaurar, eliminar)
       --------------------------- */
    document.addEventListener('click', async function (e) {
        // Descargar
        const btnD = e.target.closest('.btn-descargar');
        if (btnD) {
            e.preventDefault();
            const archivo = btnD.dataset.archivo;
            if (!archivo) return toast('Error', 'Archivo inválido', 'error');
            // Descarga directa
            window.location.href = `${BACKUP_CONTROLLER}?accion=descargar&archivo=${encodeURIComponent(archivo)}`;
            return;
        }

        // Restaurar
        const btnR = e.target.closest('.btn-restaurar');
        if (btnR) {
            e.preventDefault();
            const archivo = btnR.dataset.archivo;
            if (!archivo) return toast('Error', 'Archivo inválido', 'error');

            const confirm = await Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas restaurar el respaldo ${archivo}? Esta acción sobrescribirá los datos actuales.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, restaurar'
            });
            if (!confirm.isConfirmed) return;

            Swal.fire({ title: 'Restaurando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: Swal.showLoading });

            try {
                const data = await fetchJson(`${BACKUP_CONTROLLER}?accion=restaurar&archivo=${encodeURIComponent(archivo)}`, { method: 'GET' });
                await mostrarDialogoResultado(data, { tituloExito: 'Restauración completada' });
                actualizarTablaRespaldos();
            } catch (err) {
                console.error('Error restaurar:', err);
                await mostrarDialogoResultado({ success: false, error: err.message || 'Error al restaurar' }, { tituloError: 'Error al restaurar' });
            } finally {
                Swal.close();
            }

            return;
        }

        // Eliminar
        const btnE = e.target.closest('.btn-eliminar');
        if (btnE) {
            e.preventDefault();
            const archivo = btnE.dataset.archivo;
            if (!archivo) return toast('Error', 'Archivo inválido', 'error');

            const confirm = await Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar el respaldo ${archivo}? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                confirmButtonColor: '#d33'
            });
            if (!confirm.isConfirmed) return;

            Swal.fire({ title: 'Eliminando...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: Swal.showLoading });

            try {
                const data = await fetchJson(`${BACKUP_CONTROLLER}?accion=eliminar&archivo=${encodeURIComponent(archivo)}`, { method: 'GET' });
                await mostrarDialogoResultado(data, { tituloExito: 'Respaldo eliminado' });
                actualizarTablaRespaldos();
            } catch (err) {
                console.error('Error eliminar:', err);
                await mostrarDialogoResultado({ success: false, error: err.message || 'Error al eliminar' }, { tituloError: 'Error al eliminar' });
            } finally {
                Swal.close();
            }

            return;
        }
    });

    /* ---------------------------
       Botones generar respaldo (estáticos en la página)
       --------------------------- */
    document.getElementById('btn-backup-seguridad')?.addEventListener('click', (e) => {
        e.preventDefault();
        generarRespaldo('S');
    });
    document.getElementById('btn-backup-principal')?.addEventListener('click', (e) => {
        e.preventDefault();
        generarRespaldo('P');
    });

    /* ---------------------------
       Restaurar último respaldo
       --------------------------- */
    const btnRestaurarUltimo = document.getElementById('btn-restaurar-ultimo');
    if (btnRestaurarUltimo) {
        btnRestaurarUltimo.addEventListener('click', async function (e) {
            e.preventDefault();

            const confirm = await Swal.fire({
                title: '¿Deseas restaurar el último respaldo disponible?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, restaurar'
            });
            if (!confirm.isConfirmed) return;

            Swal.fire({ title: 'Consultando respaldos...', text: 'Por favor espere...', allowOutsideClick: false, didOpen: Swal.showLoading });

            try {
                const listaResp = await fetchJson(`${BACKUP_CONTROLLER}?accion=consultar`, { method: 'GET' });
                const lista = Array.isArray(listaResp) ? listaResp : (Array.isArray(listaResp.data) ? listaResp.data : []);
                if (!lista || lista.length === 0) throw new Error('No hay respaldos disponibles');
                // Asumimos que los nombres contienen fecha o están en orden; usar sort descendente
                const ultimo = lista.slice().sort().reverse()[0];
                const archivo = typeof ultimo === 'string' ? ultimo : (ultimo.nombre || ultimo);
                const restoreData = await fetchJson(`${BACKUP_CONTROLLER}?accion=restaurar&archivo=${encodeURIComponent(archivo)}`, { method: 'GET' });
                await mostrarDialogoResultado(restoreData, { tituloExito: 'Restauración completada' });
                actualizarTablaRespaldos();
            } catch (err) {
                console.error('Error restaurar último:', err);
                await mostrarDialogoResultado({ success: false, error: err.message || 'No se pudo restaurar el respaldo' }, { tituloError: 'Error' });
            } finally {
                Swal.close();
            }
        });
    }

    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
        // Si ya existe una instancia, solo abrir y navegar
        if (modalAyudaInstance) {
            modalAyudaInstance.openModal();

            if (contexto) {
                setTimeout(() => {
                    const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                    if (slideIndex !== undefined) {
                        modalAyudaInstance.goToSlide(slideIndex);
                    }
                }, 300);
            }

            return;
        }

        // Cargar CSS si no está cargado
        if (!$('link[href*="ayuda/css/modal.css"]').length) {
            $('<link>')
                .attr({
                    'rel': 'stylesheet',
                    'type': 'text/css',
                    'href': 'assets/public/ayuda/css/modal.css'
                })
                .appendTo('head');
        }

        // Cargar HTML del modal
        $.get('assets/public/ayuda/backup.php')
            .done(function(html) {
                // Insertar el modal si no existe. Si existe, reutilizarlo para evitar pérdida de listeners.
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                }

                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            // Inicializar modal
                            if (typeof inicializarModalAyudaUsuario === 'function') {
                                modalAyudaInstance = inicializarModalAyudaUsuario();

                                // Asegurar restauración del scroll (por si algún cierre previo falló)
                                document.body.style.overflow = '';

                                // Abrir modal con contexto si se proporciona
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) {
                                            modalAyudaInstance.goToSlide(slideIndex);
                                        }
                                    }, 300);
                                }

                                // Abrir modal
                                modalAyudaInstance.openModal();
                            } else {
                                console.error('La función inicializarModalAyudaUsuario no está disponible');
                            }
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else {
                    // Si el JS ya está cargado, solo abrir el modal existente
                    if (typeof inicializarModalAyudaUsuario === 'function') {
                        modalAyudaInstance = inicializarModalAyudaUsuario();

                        // Asegurar restauración del scroll (por si algún cierre previo falló)
                        document.body.style.overflow = '';

                        // Abrir modal con contexto si se proporciona
                        if (contexto) {
                            setTimeout(() => {
                                const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    modalAyudaInstance.goToSlide(slideIndex);
                                }
                            }, 300);
                        }

                        // Abrir modal
                        modalAyudaInstance.openModal();
                    }
                }
            })
            .fail(function() {
                console.error('Error al cargar el HTML del modal');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el contenido de ayuda'
                });
            });
    }

    // Botón de ayuda principal
    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda detectado');
        cargarYMostrarModalAyuda(); // Sin contexto específico
    });
});
