(function () {
    'use strict';

    const table = document.getElementById('tablaClientes');
    const form = document.getElementById('clientForm');
    const modal = document.getElementById('clientModal');
    const deleteModal = document.getElementById('deleteClientModal');
    const saveButton = form && form.querySelector('.btn-save');
    const fields = {
        nombre: document.getElementById('clientName'),
        cedula: document.getElementById('clientCedula'),
        telefono: document.getElementById('clientPhone'),
        correo: document.getElementById('clientEmail'),
        direccion: document.getElementById('clientAddress')
    };
    const errorElements = {
        nombre: document.getElementById('snombre'),
        cedula: document.getElementById('scedula'),
        telefono: document.getElementById('stelefono'),
        correo: document.getElementById('scorreo'),
        direccion: document.getElementById('sdireccion')
    };
    const modalIds = ['clientModal', 'deleteClientModal'];
    const allowedCharacters = {
        nombre: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]$/,
        cedula: /^[0-9.]$/,
        telefono: /^[0-9-]$/,
        correo: /^[A-Za-z0-9._%+\-@ÁÉÍÓÚÜÑáéíóúüñ]$/,
        direccion: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s,./#-]$/
    };
    const validationPatterns = {
        nombre: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{2,100}$/,
        cedula: /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
        telefono: /^\d{4}-\d{3}-\d{4}$/,
        correo: /^[A-Za-z0-9._%+\-ÁÉÍÓÚÜÑáéíóúüñ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/i,
        direccion: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s,./#-]{4,100}$/
    };
    const validationMessages = {
        nombre: '*Ingrese entre 2 y 100 caracteres; solo se permiten letras y espacios*',
        cedula: '*Formato válido: 1.234.567 o 12.345.678*',
        telefono: '*Formato válido: 0400-000-0000*',
        correo: '*Ingrese un correo activo (gmail, outlook, yahoo o iCloud).*',
        direccion: '*Ingrese entre 4 y 100 caracteres; use letras, números y signos comunes*'
    };

    let activeDeleteId = null;
    let currentPermissions = null;
    let helpModalInstance = null;
    let helpModalPromise = null;
    let permissionsRequestInProgress = false;

    if (!table || !form || !modal || !deleteModal || !saveButton ||
        Object.keys(fields).some(function (key) { return !fields[key] || !errorElements[key]; })) {
        console.error('No se encontraron los elementos requeridos para el módulo de clientes.');
        return;
    }

    function getDataTable() {
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.DataTable ||
            !$.fn.DataTable.isDataTable(table)) {
            return null;
        }
        return $(table).DataTable();
    }

    function normalizeSpaces(value) {
        return String(value || '').replace(/\s{2,}/g, ' ');
    }

    function setFieldError(name, message) {
        const field = fields[name];
        const errorElement = errorElements[name];
        errorElement.textContent = message || '';
        field.classList.toggle('is-invalid', Boolean(message));
        field.setAttribute('aria-invalid', String(Boolean(message)));
    }

    function normalizeIdentityNumber(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);
        if (digits.length === 7) {
            return digits.slice(0, 1) + '.' + digits.slice(1, 4) + '.' + digits.slice(4);
        }
        if (digits.length === 8) {
            return digits.slice(0, 2) + '.' + digits.slice(2, 5) + '.' + digits.slice(5);
        }
        return digits;
    }

    function normalizePhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        if (digits.length > 7) {
            return digits.slice(0, 4) + '-' + digits.slice(4, 7) + '-' + digits.slice(7);
        }
        if (digits.length > 4) {
            return digits.slice(0, 4) + '-' + digits.slice(4);
        }
        return digits;
    }

    function validateField(name) {
        const field = fields[name];
        if (name === 'nombre' || name === 'direccion') {
            field.value = normalizeSpaces(field.value);
        }
        const value = field.value.trim();
        const message = validationPatterns[name].test(value) ? '' : validationMessages[name];
        setFieldError(name, message);
        return !message;
    }

    function validateForm() {
        let valid = true;
        Object.keys(fields).forEach(function (name) {
            if (!validateField(name)) valid = false;
        });
        return valid;
    }

    function showModal(id) {
        const target = document.getElementById(id);
        if (!target) return;
        target.style.display = 'block';
        target.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        const target = document.getElementById(id || 'clientModal');
        if (!target) return;
        target.style.display = 'none';
        target.setAttribute('aria-hidden', 'true');
        if (target.id === 'deleteClientModal') activeDeleteId = null;
        if (!modalIds.some(function (modalId) {
            const element = document.getElementById(modalId);
            return element && element.style.display === 'block';
        })) {
            document.body.classList.remove('modal-open');
        }
    }

    async function postAction(formData) {
        const response = await fetch(window.location.href.split('#')[0], {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await response.text();
        let result;

        try {
            result = JSON.parse(text);
        } catch (error) {
            console.error('Respuesta no válida del servidor:', text);
            throw new Error('El servidor devolvió una respuesta no válida.');
        }
        if (!response.ok) {
            throw new Error((result && result.message) || 'La solicitud no pudo completarse.');
        }
        return result;
    }

    function showMessage(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'Entendido'
            });
        }
        window.alert(title + (text ? '\n' + text : ''));
        return Promise.resolve();
    }

    function showRequestError(title, error) {
        return showMessage('error', title, error && error.message
            ? error.message
            : 'Inténtelo nuevamente.');
    }

    function resetForm(mode) {
        form.reset();
        document.getElementById('clientId').value = '';
        Object.keys(fields).forEach(function (name) {
            setFieldError(name, '');
        });
        const editing = mode === 'modificar';
        document.getElementById('modalTitle').textContent = editing ? 'Modificar Clientes' : 'Incluir Clientes';
        modal.querySelector('.modal-title-content p').textContent = editing
            ? 'Actualice los datos del cliente seleccionado'
            : 'Complete los datos para incluir un nuevo cliente';
        const labelNode = saveButton.lastChild;
        if (labelNode && labelNode.nodeType === Node.TEXT_NODE) {
            labelNode.nodeValue = editing ? ' Modificar' : ' Registrar';
        }
        const helpButton = modal.querySelector('.btn-help');
        if (helpButton) {
            helpButton.dataset.contexto = editing ? 'modificar' : 'registrar';
            helpButton.title = editing ? 'Ayuda para Modificar Cliente' : 'Ayuda para Incluir Clientes';
        }
    }

    function openClientModal(mode) {
        resetForm(mode === 'modificar' || mode === 'editar' ? 'modificar' : 'registrar');
        showModal('clientModal');
        fields.nombre.focus();
    }

    function findRow(id) {
        const expectedId = String(id);
        const dataTable = getDataTable();
        if (dataTable) {
            const row = dataTable.rows().nodes().toArray().find(function (node) {
                return node.dataset.id === expectedId;
            });
            if (row) return row;
        }
        return table.querySelector('tbody tr[data-id="' + expectedId.replace(/"/g, '\\"') + '"]');
    }

    function createActionButton(className, title, imagePath, id) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-action ' + className;
        button.title = title;
        button.dataset.id = String(id);
        button.dataset.userAction = className === 'btn-modificar' ? 'edit' : 'delete';
        const image = document.createElement('img');
        image.src = imagePath;
        image.alt = '';
        button.appendChild(image);
        return button;
    }

    function createRow(client) {
        const id = Number(client.id_clientes);
        if (!Number.isSafeInteger(id) || id < 1) {
            throw new Error('El servidor devolvió un identificador de cliente no válido.');
        }
        const values = [
            String(client.nombre || ''),
            String(client.cedula || ''),
            String(client.telefono || ''),
            String(client.correo || ''),
            String(client.direccion || '')
        ];
        const row = document.createElement('tr');
        row.dataset.id = String(id);
        row.dataset.nombre = values[0];
        row.dataset.cedula = values[1];
        values.forEach(function (value, index) {
            const cell = document.createElement('td');
            const text = document.createElement('span');
            text.className = 'campo-texto';
            text.textContent = value;
            if (index === 4) {
                cell.style.width = '220px';
                cell.style.maxWidth = '220px';
                cell.style.whiteSpace = 'normal';
                cell.style.overflowWrap = 'anywhere';
                text.style.display = 'block';
                text.style.maxWidth = '220px';
                text.style.whiteSpace = 'normal';
                text.style.overflowWrap = 'anywhere';
            }
            cell.appendChild(text);
            row.appendChild(cell);
        });
        const actions = document.createElement('td');
        actions.className = 'action-buttons';
        actions.append(
            createActionButton('btn-modificar', 'Modificar Clientes', 'assets/img/pencil.svg', id),
            createActionButton('btn-eliminar', 'Eliminar Clientes', 'assets/img/circle-x.svg', id)
        );
        row.appendChild(actions);
        return row;
    }

    function applyPermissions() {
        if (!currentPermissions) return;
        const canConsult = Boolean(currentPermissions.consultar);
        const section = table.closest('.table-section');
        const sectionHeader = section && section.querySelector('.section-header');
        const permissionMessage = document.getElementById('mensaje-permiso');
        const includeButton = section && section.querySelector('.btn-incluir');
        const dataTable = getDataTable();
        const rows = dataTable
            ? dataTable.rows().nodes().toArray()
            : Array.from(table.querySelectorAll('tbody tr'));

        table.hidden = !canConsult;
        if (sectionHeader) sectionHeader.hidden = !canConsult;
        if (permissionMessage) {
            permissionMessage.style.display = canConsult ? 'none' : 'block';
            permissionMessage.textContent = canConsult
                ? ''
                : 'No tiene permiso para consultar los registros.';
        }
        if (includeButton) includeButton.hidden = !currentPermissions.incluir;
        rows.forEach(function (row) {
            row.querySelectorAll('.btn-modificar').forEach(function (button) {
                button.hidden = !currentPermissions.modificar;
            });
            row.querySelectorAll('.btn-eliminar').forEach(function (button) {
                button.hidden = !currentPermissions.eliminar;
            });
        });
    }

    function addRow(client) {
        const row = createRow(client);
        const dataTable = getDataTable();
        if (dataTable) dataTable.row.add(row).draw(false);
        else table.tBodies[0].insertBefore(row, table.tBodies[0].firstChild);
        applyPermissions();
    }

    function updateRow(client) {
        const row = findRow(client.id_clientes);
        if (!row) {
            addRow(client);
            return;
        }
        const replacement = createRow(client);
        row.dataset.id = replacement.dataset.id;
        row.dataset.nombre = replacement.dataset.nombre;
        row.dataset.cedula = replacement.dataset.cedula;
        while (row.firstChild) row.removeChild(row.firstChild);
        Array.from(replacement.cells).forEach(function (cell) {
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

    function showBackendErrors(result) {
        const errors = result && result.errors && typeof result.errors === 'object'
            ? result.errors
            : {};
        Object.keys(fields).forEach(function (name) {
            if (errors[name]) setFieldError(name, String(errors[name]));
        });
        return Object.keys(errors).map(function (name) {
            return String(errors[name]);
        }).join('\n');
    }

    async function saveClient(event) {
        if (event) event.preventDefault();
        if (!validateForm()) {
            await showMessage('warning', 'Verifique los datos', 'Revise los campos señalados antes de continuar.');
            const invalidField = form.querySelector('[aria-invalid="true"]');
            if (invalidField) invalidField.focus();
            return;
        }

        const formData = new FormData(form);
        const id = String(formData.get('id_clientes') || '');
        const editing = Boolean(id);
        formData.set('accion', editing ? 'modificar' : 'registrar');
        Object.keys(fields).forEach(function (name) {
            formData.set(name, fields[name].value.trim());
        });
        saveButton.disabled = true;

        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success' || !result.cliente) {
                const errors = showBackendErrors(result);
                throw new Error(errors || (result && result.message) || 'No se pudo guardar el cliente.');
            }
            const clientForTable = Object.assign({}, result.cliente, {
                nombre: formData.get('nombre'),
                cedula: formData.get('cedula'),
                telefono: formData.get('telefono'),
                correo: formData.get('correo'),
                direccion: formData.get('direccion')
            });
            if (editing) updateRow(clientForTable);
            else addRow(clientForTable);
            closeModal('clientModal');
            resetForm('registrar');
            await showMessage(
                'success',
                editing ? 'Cliente modificado' : 'Cliente registrado',
                result.message || 'La operación se completó correctamente.'
            );
        } catch (error) {
            console.error('Error al guardar el cliente:', error);
            await showRequestError('No se pudo guardar el cliente', error);
        } finally {
            saveButton.disabled = false;
        }
    }

    async function openEditClient(row) {
        const id = row.dataset.id;
        openClientModal('modificar');
        fields.nombre.value = row.dataset.nombre || '';
        fields.cedula.value = row.dataset.cedula || '';

        const formData = new FormData();
        formData.append('accion', 'obtener_clientes');
        formData.append('id_clientes', id);
        saveButton.disabled = true;
        try {
            const client = await postAction(formData);
            if (!client || client.status === 'error' || !client.id_clientes) {
                throw new Error(client && client.message
                    ? client.message
                    : 'No se pudieron cargar los datos del cliente.');
            }
            document.getElementById('clientId').value = client.id_clientes;
            fields.nombre.value = client.nombre || '';
            fields.cedula.value = client.cedula || '';
            fields.telefono.value = client.telefono || '';
            fields.correo.value = client.correo || '';
            fields.direccion.value = client.direccion || '';
        } catch (error) {
            closeModal('clientModal');
            console.error('Error al consultar el cliente:', error);
            await showRequestError('No se pudo consultar el cliente', error);
        } finally {
            saveButton.disabled = false;
        }
    }

    function openDeleteClient(row) {
        activeDeleteId = row.dataset.id;
        document.getElementById('deleteClientCedula').textContent =
            row.dataset.cedula || (row.cells[1] ? row.cells[1].textContent.trim() : '');
        document.getElementById('deleteClientName').textContent =
            row.dataset.nombre || (row.cells[0] ? row.cells[0].textContent.trim() : '');
        showModal('deleteClientModal');
    }

    async function confirmDeleteClient() {
        if (!activeDeleteId) return;
        const button = document.getElementById('confirmDeleteClient');
        button.disabled = true;
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id_clientes', activeDeleteId);

        try {
            const result = await postAction(formData);
            if (!result || result.status !== 'success') {
                const errors = result && result.errors
                    ? Object.values(result.errors).map(String).join('\n')
                    : '';
                throw new Error(errors || (result && result.message) || 'No se pudo eliminar el cliente.');
            }
            removeRow(activeDeleteId);
            closeModal('deleteClientModal');
            await showMessage('success', 'Cliente eliminado', result.message || 'El cliente se eliminó correctamente.');
        } catch (error) {
            console.error('Error al eliminar el cliente:', error);
            await showRequestError('No se pudo eliminar el cliente', error);
        } finally {
            button.disabled = false;
        }
    }

    function loadHelpStyle() {
        if (document.querySelector('link[href*="ayuda/css/modal.css"]')) return Promise.resolve();
        return new Promise(function (resolve, reject) {
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
        if (document.getElementById('modalAyuda')) return;
        const response = await fetch('assets/public/ayuda/cliente.php');
        if (!response.ok) throw new Error('No se pudo cargar el contenido de ayuda.');
        const container = document.createElement('div');
        container.innerHTML = await response.text();
        if (!container.querySelector('#modalAyuda')) {
            throw new Error('El recurso de ayuda no contiene el modal esperado.');
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

    function goToHelpContext(context) {
        if (!context || !helpModalInstance) return;
        const index = typeof helpModalInstance.getSlideIndex === 'function'
            ? helpModalInstance.getSlideIndex(context)
            : helpModalInstance.mapeoContextos[context];
        if (Number.isInteger(index) && index > 0) helpModalInstance.goToSlide(index);
    }

    async function initializeHelp() {
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
            if (!helpModalInstance) {
                if (!helpModalPromise) {
                    helpModalPromise = initializeHelp().catch(function (error) {
                        helpModalPromise = null;
                        throw error;
                    });
                }
                helpModalInstance = await helpModalPromise;
            }
            helpModalInstance.openModal();
            if (context) window.setTimeout(function () { goToHelpContext(context); }, 300);
        } catch (error) {
            console.error('Error al cargar la ayuda de clientes:', error);
            await showRequestError('No se pudo abrir la ayuda', error);
        }
    }

    async function refreshPermissions() {
        if (permissionsRequestInProgress) return;
        permissionsRequestInProgress = true;
        const formData = new FormData();
        formData.append('accion', 'permisos_tiempo_real');
        try {
            const permissions = await postAction(formData);
            if (!permissions || permissions.status === 'error') {
                throw new Error(permissions && permissions.message
                    ? permissions.message
                    : 'No se pudieron consultar los permisos.');
            }
            currentPermissions = permissions;
            applyPermissions();
        } catch (error) {
            console.error('Error al verificar permisos de clientes:', error);
        } finally {
            permissionsRequestInProgress = false;
        }
    }

    Object.keys(fields).forEach(function (name) {
        const field = fields[name];
        let rejectedKey = false;

        field.addEventListener('keypress', function (event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
            if (!allowedCharacters[name].test(event.key)) {
                event.preventDefault();
                rejectedKey = true;
                setFieldError(name, validationMessages[name]);
            }
        });
        field.addEventListener('keyup', function () {
            if (rejectedKey) {
                rejectedKey = false;
                return;
            }
            validateField(name);
        });
        field.addEventListener('input', function () {
            if (name === 'cedula') field.value = normalizeIdentityNumber(field.value);
            else if (name === 'telefono') field.value = normalizePhone(field.value);
            validateField(name);
        });
        field.addEventListener('blur', function () {
            validateField(name);
        });
    });

    form.addEventListener('submit', saveClient);
    saveButton.addEventListener('click', saveClient);
    table.tBodies[0].addEventListener('click', function (event) {
        const button = event.target.closest('.btn-modificar, .btn-eliminar');
        if (!button) return;
        const row = button.closest('tr[data-id]');
        if (!row) return;
        if (button.classList.contains('btn-modificar')) openEditClient(row);
        else openDeleteClient(row);
    });
    document.getElementById('confirmDeleteClient').addEventListener('click', confirmDeleteClient);

    document.querySelectorAll('.btn-ayuda, .btn-help, .btn-ayuda-modal').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openHelp(button.dataset.contexto || '');
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal('clientModal');
    });
    deleteModal.addEventListener('click', function (event) {
        if (event.target === deleteModal) closeModal('deleteClientModal');
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (deleteModal.style.display === 'block') closeModal('deleteClientModal');
            else if (modal.style.display === 'block') closeModal('clientModal');
        }
    });

    window.openModal = openClientModal;
    window.closeModal = closeModal;
    refreshPermissions();
    window.setInterval(refreshPermissions, 10000);
})();
