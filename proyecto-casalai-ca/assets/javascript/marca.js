(function () {
    'use strict';

    const tableElement = document.getElementById('tablaMarcas') ||
        document.querySelector('#contenedor-marcas table.table-info');
    const marcaForm = document.getElementById('marcaForm');
    const nameInput = document.getElementById('nombre_marca');
    const nameError = document.getElementById('snombre_marca') ||
        document.getElementById('error-nombre-marca');
    const marcaModal = document.getElementById('marcaModal');
    const saveButton = document.querySelector('#marcaForm .btn-save, #btn-save-marca');
    const allowedNameCharacter = /^[a-zA-ZÁÉÍÓÚáéíóúñÑüÜ0-9\s\-.&']$/;
    const validName = /^[a-zA-ZÁÉÍÓÚáéíóúñÑüÜ0-9\s\-.&']{2,25}$/;
    let activeDeleteId = null;
    let helpModalInstance = null;
    let helpLoadPromise = null;
    let currentPermissions = null;

    if (!tableElement || !marcaForm || !nameInput || !marcaModal) return;

    function getDataTable() {
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.DataTable ||
            !$.fn.DataTable.isDataTable(tableElement)) {
            return null;
        }
        return $(tableElement).DataTable();
    }

    function normalizeSpaces(value) {
        return String(value || '').replace(/\s{2,}/g, ' ');
    }

    function setNameError(message) {
        if (nameError) nameError.textContent = message || '';
        nameInput.classList.toggle('is-invalid', Boolean(message));
        nameInput.setAttribute('aria-invalid', String(Boolean(message)));
    }

    function validateName() {
        nameInput.value = normalizeSpaces(nameInput.value);
        const value = nameInput.value.trim();
        let message = '';

        if (!value) {
            message = '*Este campo es obligatorio*';
        } else if (value.length < 2) {
            message = '*El nombre debe tener al menos 2 caracteres*';
        } else if (value.length > 25) {
            message = '*El nombre no puede superar 25 caracteres*';
        } else if (!validName.test(value)) {
            message = '*Use letras, números, espacios y caracteres especiales comunes*';
        }

        setNameError(message);
        return !message;
    }

    function showModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        const modal = document.getElementById(id || 'marcaModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        const modalIds = ['marcaModal', 'deleteMarcaModal', 'searchModal'];
        if (!modalIds.some(modalId => {
            const element = document.getElementById(modalId);
            return element && element.style.display === 'block';
        })) {
            document.body.classList.remove('modal-open');
        }
    }

    function resetForm() {
        marcaForm.reset();
        document.getElementById('accionForm').value = 'registrar';
        document.getElementById('id_marca').value = '';
        nameInput.classList.remove('is-invalid');
        nameInput.removeAttribute('aria-invalid');
        setNameError('');
    }

    function openMarcaModal(mode, marca) {
        const editing = mode === 'modificar' && marca;
        resetForm();
        document.getElementById('accionForm').value = editing ? 'modificar' : 'registrar';
        document.getElementById('modalTitle').textContent = editing ? 'Modificar Marca' : 'Incluir Marcas';
        document.getElementById('modalSubtitle').textContent = editing
            ? 'Actualice los datos de la marca'
            : 'Complete los datos para incluir una nueva marca';
        document.getElementById('id_marca').value = editing ? marca.id : '';
        nameInput.value = editing ? marca.nombre : '';

        const helpButton = marcaModal.querySelector('[data-contexto]');
        if (helpButton) {
            helpButton.dataset.contexto = editing ? 'modificar' : 'registrar';
            helpButton.title = editing ? 'Ayuda para modificar marcas' : 'Ayuda para Incluir Marcas';
        }
        if (saveButton) {
            const label = saveButton.lastChild;
            if (label && label.nodeType === Node.TEXT_NODE) {
                label.nodeValue = editing ? ' Modificar' : ' Registrar';
            }
        }
        showModal('marcaModal');
        nameInput.focus();
    }

    function openDeleteModal(id, name) {
        activeDeleteId = String(id);
        document.getElementById('deleteMarcaName').textContent = name;
        showModal('deleteMarcaModal');
    }

    function createActionButton(className, title, iconPath, id, name) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-action ' + className;
        button.title = title;
        button.dataset.id = String(id);
        button.dataset.nombre = String(name);

        const icon = document.createElement('img');
        icon.src = iconPath;
        icon.alt = '';
        button.appendChild(icon);
        return button;
    }

    function createRow(marca) {
        const id = Number(marca.id_marca);
        if (!Number.isSafeInteger(id) || id < 1) {
            throw new Error('El servidor devolvió un identificador de marca no válido.');
        }
        const name = String(marca.nombre_marca || '');
        const row = document.createElement('tr');
        row.dataset.id = String(id);

        const idCell = document.createElement('td');
        const idText = document.createElement('span');
        idText.className = 'campo-texto';
        idText.textContent = String(id);
        idCell.appendChild(idText);

        const nameCell = document.createElement('td');
        const nameText = document.createElement('span');
        nameText.className = 'campo-texto';
        nameText.textContent = name;
        nameCell.appendChild(nameText);

        const actionsCell = document.createElement('td');
        actionsCell.className = 'action-buttons';
        actionsCell.append(
            createActionButton('btn-modificar', 'Modificar Marca', 'assets/img/pencil.svg', id, name),
            createActionButton('btn-eliminar', 'Eliminar Marca', 'assets/img/circle-x.svg', id, name)
        );
        row.append(idCell, nameCell, actionsCell);
        return row;
    }

    function findRow(id) {
        const dataTable = getDataTable();
        if (dataTable) {
            const rows = dataTable.rows().nodes().toArray();
            const row = rows.find(node => node.dataset.id === String(id));
            if (row) return row;
        }
        return tableElement.querySelector('tbody tr[data-id="' + String(id) + '"]');
    }

    function addRow(marca) {
        const row = createRow(marca);
        const dataTable = getDataTable();
        if (dataTable) {
            dataTable.row.add(row).draw(false);
        } else {
            tableElement.tBodies[0].insertBefore(row, tableElement.tBodies[0].firstChild);
        }
        applyPermissions();
    }

    function updateRow(marca) {
        const row = findRow(marca.id_marca);
        if (!row) {
            addRow(marca);
            return;
        }
        const updatedRow = createRow(marca);
        row.dataset.id = updatedRow.dataset.id;
        Array.from(updatedRow.cells).forEach((cell, index) => {
            row.cells[index].replaceWith(cell);
        });
        const dataTable = getDataTable();
        if (dataTable) dataTable.row(row).invalidate('dom').draw(false);
        applyPermissions();
    }

    function removeRow(id) {
        const row = findRow(id);
        if (!row) return;
        const dataTable = getDataTable();
        if (dataTable) dataTable.row(row).remove().draw(false);
        else row.remove();
    }

    async function postAction(formData) {
        const response = await fetch(window.location.href.split('#')[0], {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (error) {
            throw new Error('El servidor respondió con un formato inesperado. Intente nuevamente.');
        }
        if (!response.ok) {
            throw new Error((result && result.message) || 'La solicitud no pudo completarse.');
        }
        return result;
    }

    function showError(title, error) {
        return window.Swal.fire({
            icon: 'error',
            title,
            text: error && error.message ? error.message : 'Intente nuevamente.',
            confirmButtonText: 'Entendido'
        });
    }

    function showBackendFieldErrors(errors) {
        const message = errors && errors.nombre_marca ? String(errors.nombre_marca) : '';
        if (message) {
            setNameError(message);
            nameInput.focus();
        }
        return message;
    }

    async function saveMarca() {
        if (!validateName()) {
            nameInput.focus();
            return;
        }

        const button = saveButton;
        if (button) button.disabled = true;
        nameInput.value = nameInput.value.trim();
        const formData = new FormData(marcaForm);
        const isEditing = formData.get('accion') === 'modificar';

        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success' || !result.marca) {
                const backendError = showBackendFieldErrors(result && result.field_errors);
                throw new Error(backendError || (result && result.message) || 'No se pudo guardar la marca.');
            }

            if (isEditing) updateRow(result.marca);
            else addRow(result.marca);
            closeModal('marcaModal');
            resetForm();
            await window.Swal.fire({
                icon: 'success',
                title: isEditing ? 'Marca modificada' : 'Marca registrada',
                text: result.message || (isEditing
                    ? 'La marca se modificó correctamente.'
                    : 'La marca se registró correctamente.'),
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo guardar la marca', error);
        } finally {
            if (button) button.disabled = false;
        }
    }

    async function confirmDeleteMarca() {
        if (!activeDeleteId) return;
        const button = document.getElementById('confirmDeleteMarca');
        button.disabled = true;
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id_marca', activeDeleteId);

        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success') {
                throw new Error((result && result.message) || 'No se pudo eliminar la marca.');
            }
            removeRow(activeDeleteId);
            closeModal('deleteMarcaModal');
            activeDeleteId = null;
            await window.Swal.fire({
                icon: 'success',
                title: 'Marca eliminada',
                text: 'La marca se eliminó correctamente.',
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo eliminar la marca', error);
        } finally {
            button.disabled = false;
        }
    }

    function applyPermissions() {
        if (!currentPermissions) return;
        const canConsult = Boolean(currentPermissions.consultar);
        const permissionMessage = document.getElementById('mensaje-permiso');
        const sectionHeader = document.querySelector('#contenedor-marcas .section-header');

        tableElement.hidden = !canConsult;
        if (sectionHeader) sectionHeader.hidden = !canConsult;
        if (permissionMessage) {
            permissionMessage.style.display = canConsult ? 'none' : 'block';
            permissionMessage.textContent = canConsult
                ? ''
                : 'No tiene permiso para consultar los registros.';
        }

        const includeButton = document.querySelector('#contenedor-marcas .btn-incluir');
        if (includeButton) includeButton.hidden = !currentPermissions.incluir;
        tableElement.querySelectorAll('.btn-modificar').forEach(button => {
            button.hidden = !currentPermissions.modificar;
        });
        tableElement.querySelectorAll('.btn-eliminar').forEach(button => {
            button.hidden = !currentPermissions.eliminar;
        });
    }

    async function checkPermissions() {
        const formData = new FormData();
        formData.append('accion', 'permisos_tiempo_real');
        try {
            currentPermissions = await postAction(formData);
            applyPermissions();
        } catch (error) {
            console.error('No se pudieron actualizar los permisos de marcas:', error);
        }
    }

    function loadStyle() {
        if (document.querySelector('link[href*="ayuda/css/modal.css"]')) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const style = document.createElement('link');
            style.rel = 'stylesheet';
            style.href = 'assets/public/ayuda/css/modal.css';
            style.onload = resolve;
            style.onerror = () => reject(new Error('No se pudieron cargar los estilos de ayuda.'));
            document.head.appendChild(style);
        });
    }

    function loadHelpScript() {
        if (typeof window.inicializarModalAyudaUsuario === 'function') return Promise.resolve();
        const existing = Array.from(document.scripts).find(script =>
            script.src.includes('assets/public/ayuda/js/modal.js')
        );
        const script = existing || document.createElement('script');
        if (!existing) script.src = 'assets/public/ayuda/js/modal.js';

        return new Promise((resolve, reject) => {
            if (typeof window.inicializarModalAyudaUsuario === 'function') {
                resolve();
                return;
            }
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', () => {
                reject(new Error('No se pudo cargar el comportamiento del modal de ayuda.'));
            }, { once: true });
            if (!existing) document.body.appendChild(script);
        });
    }

    function navigateHelp(context) {
        if (!context || !helpModalInstance) return;
        const index = typeof helpModalInstance.getSlideIndex === 'function'
            ? helpModalInstance.getSlideIndex(context)
            : helpModalInstance.mapeoContextos[context];
        if (Number.isInteger(index) && index > 0) helpModalInstance.goToSlide(index);
    }

    function openHelp(context) {
        if (helpModalInstance) {
            helpModalInstance.openModal();
            if (context) window.setTimeout(() => navigateHelp(context), 300);
            return Promise.resolve();
        }
        if (helpLoadPromise) {
            return helpLoadPromise.then(() => navigateHelp(context));
        }

        helpLoadPromise = loadStyle()
            .then(() => {
                if (document.getElementById('modalAyuda')) return;
                return fetch('assets/public/ayuda/marca.php')
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar el contenido de ayuda.');
                        return response.text();
                    })
                    .then(html => {
                        const container = document.createElement('div');
                        container.innerHTML = html;
                        if (!container.querySelector('#modalAyuda')) {
                            throw new Error('El contenido de ayuda no contiene el modal esperado.');
                        }
                        while (container.firstChild) document.body.appendChild(container.firstChild);
                    });
            })
            .then(loadHelpScript)
            .then(() => {
                if (typeof window.inicializarModalAyudaUsuario !== 'function') {
                    throw new Error('El modal de ayuda no se pudo inicializar.');
                }
                helpModalInstance = window.inicializarModalAyudaUsuario();
                if (!helpModalInstance) throw new Error('No se obtuvo una instancia del modal de ayuda.');
                helpModalInstance.openModal();
                if (context) window.setTimeout(() => navigateHelp(context), 300);
            })
            .catch(error => {
                helpLoadPromise = null;
                return showError('No se pudo abrir la ayuda', error);
            });
        return helpLoadPromise;
    }

    function handleTableClick(event) {
        const button = event.target.closest('.btn-modificar, .btn-eliminar');
        if (!button) return;
        const row = button.closest('tr[data-id]');
        if (!row) return;
        const id = row.dataset.id;
        const name = row.cells[1] ? row.cells[1].textContent.trim() : '';

        if (button.classList.contains('btn-modificar')) {
            openMarcaModal('modificar', { id, nombre: name });
        } else {
            openDeleteModal(id, name);
        }
    }

    function initialize() {
        nameInput.addEventListener('keypress', event => {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
            if (!allowedNameCharacter.test(event.key)) {
                event.preventDefault();
                setNameError('*Use letras, números, espacios y caracteres especiales comunes*');
            }
        });
        nameInput.addEventListener('keyup', validateName);
        nameInput.addEventListener('input', validateName);
        nameInput.addEventListener('blur', validateName);

        marcaForm.addEventListener('submit', event => {
            event.preventDefault();
            saveMarca();
        });
        saveButton?.addEventListener('click', saveMarca);
        document.getElementById('confirmDeleteMarca').addEventListener('click', confirmDeleteMarca);
        tableElement.addEventListener('click', handleTableClick);
        if (window.jQuery) {
            window.jQuery(tableElement).on('draw.dt.marca', applyPermissions);
        }

        document.addEventListener('click', event => {
            const helpButton = event.target.closest('.btn-ayuda, .btn-ayuda-modal, .btn-help');
            if (!helpButton || helpButton.closest('#modalAyuda')) return;
            event.preventDefault();
            openHelp(helpButton.dataset.contexto || '');
        });
        document.addEventListener('click', event => {
            const modal = event.target.classList && event.target.classList.contains('modal')
                ? event.target
                : null;
            if (modal && ['marcaModal', 'deleteMarcaModal', 'searchModal'].includes(modal.id)) {
                closeModal(modal.id);
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                ['marcaModal', 'deleteMarcaModal', 'searchModal'].forEach(closeModal);
            }
        });

        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', event => {
                event.preventDefault();
                const term = document.getElementById('searchNombre').value.trim();
                const dataTable = getDataTable();
                if (dataTable) {
                    dataTable.search(term).draw();
                } else {
                    tableElement.querySelectorAll('tbody tr[data-id]').forEach(row => {
                        const name = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                        row.hidden = term !== '' && !name.includes(term.toLowerCase());
                    });
                }
                closeModal('searchModal');
            });
        }

        window.openModal = function (mode) {
            openMarcaModal(mode === 'editar' || mode === 'modificar' ? 'modificar' : 'registrar');
        };
        window.closeModal = closeModal;
        window.closeSearchModal = function () { closeModal('searchModal'); };

        checkPermissions();
        window.setInterval(checkPermissions, 10000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
