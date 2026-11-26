(function () {
    'use strict';

    const tableElement = document.getElementById('tablaModelos');
    const modeloForm = document.getElementById('modeloForm');
    const brandSelect = document.getElementById('id_marca');
    const nameInput = document.getElementById('nombre_modelo');
    const brandError = document.getElementById('smarca');
    const nameError = document.getElementById('snombre_modelo');
    const modeloModal = document.getElementById('modeloModal');
    const saveButton = document.querySelector('#modeloForm .btn-save');
    const allowedNameCharacter = /^[a-zA-ZÁÉÍÓÚáéíóúñÑüÜ0-9\s.\-&']$/;
    const validName = /^[a-zA-ZÁÉÍÓÚáéíóúñÑüÜ0-9\s.\-&']{2,25}$/;
    const modalIds = ['modeloModal', 'deleteModeloModal'];
    let activeDeleteId = null;
    let currentPermissions = null;
    let helpModalInstance = null;
    let helpModalPromise = null;
    let disallowedNameKey = false;

    if (!tableElement || !modeloForm || !brandSelect || !nameInput || !modeloModal) return;

    function byId(id) {
        return document.getElementById(id);
    }

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

    function setFieldError(field, errorElement, message) {
        if (errorElement) errorElement.textContent = message || '';
        field.classList.toggle('is-invalid', Boolean(message));
        field.classList.toggle('is-valid', !message && Boolean(field.value.trim()));
        field.setAttribute('aria-invalid', String(Boolean(message)));
    }

    function validateBrand() {
        const message = brandSelect.value ? '' : '*Debe seleccionar una marca*';
        setFieldError(brandSelect, brandError, message);
        return !message;
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

        setFieldError(nameInput, nameError, message);
        return !message;
    }

    function validateForm() {
        const brandIsValid = validateBrand();
        const nameIsValid = validateName();
        return brandIsValid && nameIsValid;
    }

    function showModal(id) {
        const modal = byId(id);
        if (!modal) return;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        const modal = byId(id || 'modeloModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        if (modal && modal.id === 'deleteModeloModal') activeDeleteId = null;
        if (!modalIds.some(function (modalId) {
            const element = byId(modalId);
            return element && element.style.display === 'block';
        })) {
            document.body.classList.remove('modal-open');
        }
    }

    function resetForm() {
        modeloForm.reset();
        byId('accionForm').value = 'registrar';
        byId('id_modelo').value = '';
        disallowedNameKey = false;
        [brandSelect, nameInput].forEach(function (field) {
            field.classList.remove('is-invalid', 'is-valid');
            field.removeAttribute('aria-invalid');
        });
        if (brandError) brandError.textContent = '';
        if (nameError) nameError.textContent = '';
    }

    function setSaveButtonLabel(label) {
        if (!saveButton) return;
        const textNode = saveButton.lastChild;
        if (textNode && textNode.nodeType === Node.TEXT_NODE) {
            textNode.nodeValue = ' ' + label;
        }
    }

    function openModeloModal(mode, modelo) {
        const editing = (mode === 'modificar' || mode === 'editar') && modelo;
        resetForm();
        byId('accionForm').value = editing ? 'modificar' : 'registrar';
        byId('modalTitle').textContent = editing ? 'Modificar Modelos' : 'Incluir Modelos';
        byId('modalSubtitle').textContent = editing
            ? 'Actualice los datos del modelo seleccionado'
            : 'Complete los datos para incluir un nuevo modelo';
        byId('id_modelo').value = editing ? modelo.id : '';
        brandSelect.value = editing ? modelo.idMarca : '';
        nameInput.value = editing ? modelo.nombre : '';
        setSaveButtonLabel(editing ? 'Modificar' : 'Registrar');

        const helpButton = modeloModal.querySelector('[data-contexto]');
        if (helpButton) {
            helpButton.dataset.contexto = editing ? 'modificar' : 'registrar';
            helpButton.title = editing ? 'Ayuda para Modificar Modelos' : 'Ayuda para Incluir Modelos';
        }

        showModal('modeloModal');
        (editing ? nameInput : brandSelect).focus();
    }

    function openDeleteModal(id, brandName, modelName) {
        activeDeleteId = String(id);
        byId('deleteModeloName').textContent = String(brandName) + '/' + String(modelName);
        showModal('deleteModeloModal');
    }

    function createActionButton(className, title, iconPath, id) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-action ' + className;
        button.title = title;
        button.dataset.id = String(id);

        const icon = document.createElement('img');
        icon.src = iconPath;
        icon.alt = '';
        button.appendChild(icon);
        return button;
    }

    function createRow(modelo) {
        const id = Number(modelo.id_modelo);
        const idMarca = Number(modelo.id_marca);
        if (!Number.isSafeInteger(id) || id < 1 ||
            !Number.isSafeInteger(idMarca) || idMarca < 1) {
            throw new Error('El servidor devolvió datos de modelo no válidos.');
        }

        const name = String(modelo.nombre_modelo || '');
        const brandName = String(modelo.nombre_marca || '');
        const row = document.createElement('tr');
        row.dataset.id = String(id);
        row.dataset.idMarca = String(idMarca);
        row.dataset.nombreModelo = name;

        [id, brandName, name].forEach(function (value) {
            const cell = document.createElement('td');
            const text = document.createElement('span');
            text.className = 'campo-texto';
            text.textContent = String(value);
            cell.appendChild(text);
            row.appendChild(cell);
        });

        const actionsCell = document.createElement('td');
        actionsCell.className = 'action-buttons';
        actionsCell.append(
            createActionButton('btn-modificar', 'Modificar Modelos', 'assets/img/pencil.svg', id),
            createActionButton('btn-eliminar', 'Eliminar Modelos', 'assets/img/circle-x.svg', id)
        );
        row.appendChild(actionsCell);
        return row;
    }

    function findRow(id) {
        const dataTable = getDataTable();
        if (dataTable) {
            const row = dataTable.rows().nodes().toArray().find(function (node) {
                return node.dataset.id === String(id);
            });
            if (row) return row;
        }
        return tableElement.querySelector('tbody tr[data-id="' + String(id) + '"]');
    }

    function applyPermissions() {
        if (!currentPermissions) return;
        const canConsult = Boolean(currentPermissions.consultar);
        const sectionHeader = document.querySelector('#contenedor-modelos .section-header');
        const permissionMessage = byId('mensaje-permiso');
        const includeButton = document.querySelector('#contenedor-modelos .btn-incluir');

        tableElement.hidden = !canConsult;
        if (sectionHeader) sectionHeader.hidden = !canConsult;
        if (permissionMessage) {
            permissionMessage.style.display = canConsult ? 'none' : 'block';
            permissionMessage.textContent = canConsult
                ? ''
                : 'No tiene permiso para consultar los registros.';
        }
        if (includeButton) includeButton.hidden = !currentPermissions.incluir;
        tableElement.querySelectorAll('.btn-modificar').forEach(function (button) {
            button.hidden = !currentPermissions.modificar;
        });
        tableElement.querySelectorAll('.btn-eliminar').forEach(function (button) {
            button.hidden = !currentPermissions.eliminar;
        });
    }

    function addRow(modelo) {
        const row = createRow(modelo);
        const dataTable = getDataTable();
        if (dataTable) {
            dataTable.row.add(row).draw(false);
        } else {
            tableElement.tBodies[0].insertBefore(row, tableElement.tBodies[0].firstChild);
        }
        applyPermissions();
    }

    function updateRow(modelo) {
        const row = findRow(modelo.id_modelo);
        if (!row) {
            addRow(modelo);
            return;
        }

        const updatedRow = createRow(modelo);
        row.dataset.id = updatedRow.dataset.id;
        row.dataset.idMarca = updatedRow.dataset.idMarca;
        row.dataset.nombreModelo = updatedRow.dataset.nombreModelo;
        while (row.firstChild) row.removeChild(row.firstChild);
        Array.from(updatedRow.cells).forEach(function (cell) {
            row.appendChild(cell);
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
            title: title,
            text: error && error.message ? error.message : 'Intente nuevamente.',
            confirmButtonText: 'Entendido'
        });
    }

    function showBackendFieldErrors(errors) {
        if (!errors || typeof errors !== 'object') return '';
        if (errors.id_marca) setFieldError(brandSelect, brandError, String(errors.id_marca));
        if (errors.nombre_modelo) setFieldError(nameInput, nameError, String(errors.nombre_modelo));
        return Object.values(errors).map(String).join(' ');
    }

    async function saveModelo() {
        if (!validateForm()) {
            await window.Swal.fire({
                icon: 'warning',
                title: 'Verifique los datos',
                text: 'Revise los campos señalados antes de continuar.',
                confirmButtonText: 'Entendido'
            });
            const invalidField = brandSelect.value ? nameInput : brandSelect;
            invalidField.focus();
            return;
        }

        if (saveButton) saveButton.disabled = true;
        nameInput.value = nameInput.value.trim();
        const formData = new FormData(modeloForm);
        const isEditing = formData.get('accion') === 'modificar';
        formData.set('accion', isEditing ? 'modificar' : 'registrar');

        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success' || !result.modelo) {
                const fieldErrors = showBackendFieldErrors(result && result.field_errors);
                throw new Error(fieldErrors || (result && result.message) || 'No se pudo guardar el modelo.');
            }

            if (isEditing) updateRow(result.modelo);
            else addRow(result.modelo);
            closeModal('modeloModal');
            resetForm();
            await window.Swal.fire({
                icon: 'success',
                title: isEditing ? 'Modelo modificado' : 'Modelo registrado',
                text: result.message || (isEditing
                    ? 'El modelo se modificó correctamente.'
                    : 'El modelo se registró correctamente.'),
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo guardar el modelo', error);
        } finally {
            if (saveButton) saveButton.disabled = false;
        }
    }

    async function confirmDeleteModelo() {
        if (!activeDeleteId) return;
        const button = byId('confirmDeleteModelo');
        if (button) button.disabled = true;

        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id_modelo', activeDeleteId);
        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success') {
                const fieldErrors = result && result.field_errors
                    ? Object.values(result.field_errors).map(String).join(' ')
                    : '';
                throw new Error(fieldErrors || (result && result.message) || 'No se pudo eliminar el modelo.');
            }
            removeRow(activeDeleteId);
            closeModal('deleteModeloModal');
            await window.Swal.fire({
                icon: 'success',
                title: 'Modelo eliminado',
                text: result.message || 'El modelo se eliminó correctamente.',
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo eliminar el modelo', error);
        } finally {
            if (button) button.disabled = false;
        }
    }

    function handleTableClick(event) {
        const button = event.target.closest('.btn-modificar, .btn-eliminar');
        if (!button) return;
        const row = button.closest('tr[data-id]');
        if (!row) return;

        if (button.classList.contains('btn-modificar')) {
            openModeloModal('modificar', {
                id: row.dataset.id,
                idMarca: row.dataset.idMarca,
                nombre: row.dataset.nombreModelo ||
                    (row.cells[2] ? row.cells[2].textContent.trim() : '')
            });
        } else {
            const brandName = row.cells[1] ? row.cells[1].textContent.trim() : '';
            const modelName = row.dataset.nombreModelo ||
                (row.cells[2] ? row.cells[2].textContent.trim() : '');
            openDeleteModal(row.dataset.id, brandName, modelName);
        }
    }

    async function loadHelpStyle() {
        if (document.querySelector('link[href*="ayuda/css/modal.css"]')) return;
        await new Promise(function (resolve, reject) {
            const style = document.createElement('link');
            style.rel = 'stylesheet';
            style.href = 'assets/public/ayuda/css/modal.css';
            style.addEventListener('load', resolve, { once: true });
            style.addEventListener('error', function () {
                reject(new Error('No se pudieron cargar los estilos de ayuda.'));
            }, { once: true });
            document.head.appendChild(style);
        });
    }

    async function loadHelpMarkup() {
        if (byId('modalAyuda')) return;
        const response = await fetch('assets/public/ayuda/modelo.php');
        if (!response.ok) throw new Error('No se pudo cargar el contenido de ayuda.');
        const html = await response.text();
        const container = document.createElement('div');
        container.innerHTML = html;
        if (!container.querySelector('#modalAyuda')) {
            throw new Error('El contenido de ayuda no contiene el modal esperado.');
        }
        while (container.firstChild) document.body.appendChild(container.firstChild);
    }

    function loadHelpScript() {
        if (typeof window.inicializarModalAyudaUsuario === 'function') return Promise.resolve();
        const existing = Array.from(document.scripts).find(function (script) {
            return script.src.includes('assets/public/ayuda/js/modal.js');
        });
        const script = existing || document.createElement('script');

        return new Promise(function (resolve, reject) {
            if (typeof window.inicializarModalAyudaUsuario === 'function') {
                resolve();
                return;
            }
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', function () {
                reject(new Error('No se pudo cargar el comportamiento del modal de ayuda.'));
            }, { once: true });
            if (!existing) {
                script.src = 'assets/public/ayuda/js/modal.js';
                document.body.appendChild(script);
            }
        });
    }

    function navigateHelp(context) {
        if (!context || !helpModalInstance) return;
        const index = typeof helpModalInstance.getSlideIndex === 'function'
            ? helpModalInstance.getSlideIndex(context)
            : helpModalInstance.mapeoContextos[context];
        if (Number.isInteger(index) && index > 0) helpModalInstance.goToSlide(index);
    }

    async function initializeHelpModal() {
        await loadHelpStyle();
        await loadHelpMarkup();
        await loadHelpScript();
        if (typeof window.inicializarModalAyudaUsuario !== 'function') {
            throw new Error('El modal de ayuda no se pudo inicializar.');
        }
        helpModalInstance = window.inicializarModalAyudaUsuario();
        if (!helpModalInstance) throw new Error('No se obtuvo una instancia del modal de ayuda.');
        return helpModalInstance;
    }

    async function openHelp(context) {
        try {
            if (helpModalInstance) {
                helpModalInstance.openModal();
            } else {
                if (!helpModalPromise) {
                    helpModalPromise = initializeHelpModal().catch(function (error) {
                        helpModalPromise = null;
                        throw error;
                    });
                }
                helpModalInstance = await helpModalPromise;
                helpModalInstance.openModal();
            }
            if (context) window.setTimeout(function () { navigateHelp(context); }, 300);
        } catch (error) {
            await showError('No se pudo abrir la ayuda', error);
        }
    }

    function initialize() {
        nameInput.addEventListener('keypress', function (event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
            if (!allowedNameCharacter.test(event.key)) {
                event.preventDefault();
                disallowedNameKey = true;
                setFieldError(nameInput, nameError, '*Use letras, números, espacios y caracteres especiales comunes*');
            }
        });
        nameInput.addEventListener('keyup', function () {
            if (disallowedNameKey) {
                disallowedNameKey = false;
                return;
            }
            validateName();
        });
        nameInput.addEventListener('input', validateName);
        nameInput.addEventListener('blur', validateName);

        brandSelect.addEventListener('keypress', validateBrand);
        brandSelect.addEventListener('keyup', validateBrand);
        brandSelect.addEventListener('change', validateBrand);
        brandSelect.addEventListener('blur', validateBrand);

        modeloForm.addEventListener('submit', function (event) {
            event.preventDefault();
            saveModelo();
        });
        if (saveButton) saveButton.addEventListener('click', saveModelo);
        byId('confirmDeleteModelo').addEventListener('click', confirmDeleteModelo);
        tableElement.addEventListener('click', handleTableClick);

        if (window.jQuery) {
            window.jQuery(tableElement).on('draw.dt.modelo', applyPermissions);
        }

        document.addEventListener('click', function (event) {
            const helpButton = event.target.closest('.btn-ayuda, .btn-help');
            if (!helpButton || helpButton.closest('#modalAyuda')) return;
            event.preventDefault();
            openHelp(helpButton.dataset.contexto || '');
        });
        document.addEventListener('click', function (event) {
            if (event.target === modeloModal || event.target === byId('deleteModeloModal')) {
                closeModal(event.target.id);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') modalIds.forEach(closeModal);
        });

        window.openModal = function (mode) {
            openModeloModal(mode === 'editar' || mode === 'modificar' ? 'modificar' : 'registrar');
        };
        window.closeModal = closeModal;

        const initialHelpButton = document.querySelector('#modeloModal [data-contexto]');
        if (initialHelpButton) initialHelpButton.dataset.contexto = 'registrar';
        checkPermissions();
        window.setInterval(checkPermissions, 10000);
    }

    async function checkPermissions() {
        const formData = new FormData();
        formData.append('accion', 'permisos_tiempo_real');
        try {
            currentPermissions = await postAction(formData);
            applyPermissions();
        } catch (error) {
            console.error('No se pudieron actualizar los permisos de modelos:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
