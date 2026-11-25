(function () {
    'use strict';

    const form = document.getElementById('userForm');
    const userTable = document.querySelector('.table-info');
    if (!form || !userTable) return;

    const modalIds = ['userModal', 'userDetailsModal', 'deleteUserModal', 'userStatusModal'];
    const passwordPattern = {
        uppercase: /[A-Z]/,
        lowercase: /[a-z]/,
        number: /\d/,
        special: /[^A-Za-z0-9ñÑáéíóúÁÉÍÓÚüÜ]/
    };
    const fieldRules = {
        nombre_usuario: {
            pattern: /^[a-zA-Z0-9_]{4,20}$/,
            message: 'El usuario debe tener entre 4 y 20 caracteres: letras, números o guion bajo.',
            keypress: /^[a-zA-Z0-9_]$/
        },
        nombre: {
            pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
            message: 'Ingrese de 2 a 50 caracteres, solo letras y espacios.',
            keypress: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]$/
        },
        apellido_usuario: {
            pattern: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
            message: 'Ingrese de 2 a 50 caracteres, solo letras y espacios.',
            keypress: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]$/
        },
        cedula: {
            pattern: /^(?:\d{1}\.\d{3}\.\d{3}|\d{2}\.\d{3}\.\d{3})$/,
            message: 'Use el formato 1.234.567 o 12.345.678.',
            keypress: /^\d$/
        },
        telefono_usuario: {
            pattern: /^\d{4}-\d{3}-\d{4}$/,
            message: 'Use el formato telefónico 0400-000-0000.',
            keypress: /^\d$/
        },
        correo_usuario: {
            pattern: /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/i,
            message: 'Use un correo de Gmail, Outlook, Yahoo o iCloud.',
            keypress: /^[A-Za-z0-9._%+\-@ÁÉÍÓÚáéíóúñÑ]$/
        }
    };
    let activeUserId = null;
    let activeUserName = '';
    let activeUserStatus = '';
    let helpModalInstance = null;
    let helpLoadPromise = null;

    function byId(id) {
        return document.getElementById(id);
    }

    function getDataTable() {
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.DataTable || !$.fn.DataTable.isDataTable(userTable)) return null;
        return $(userTable).DataTable();
    }

    function showModal(id) {
        const modal = byId(id);
        if (!modal) return;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        const modal = byId(id || 'userModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        if (!modalIds.some(function (modalId) {
            const element = byId(modalId);
            return element && element.style.display === 'block';
        })) {
            document.body.classList.remove('modal-open');
        }
    }

    function clearValidation() {
        form.querySelectorAll('.span-value').forEach(function (span) {
            span.textContent = '';
        });
        form.querySelectorAll('.is-valid, .is-invalid').forEach(function (field) {
            field.classList.remove('is-valid', 'is-invalid');
        });
    }

    function setFieldState(field, message) {
        const span = byId('s' + field.id);
        const isValid = !message;
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);
        if (span) span.textContent = message || '';
        field.setAttribute('aria-invalid', String(!isValid));
        return isValid;
    }

    function isEditing() {
        return Boolean(byId('userId').value);
    }

    function validateField(field) {
        if (!field || field.disabled || !field.name) return true;
        if ((field.id === 'clave_usuario' || field.id === 'clave_confirmar') && isEditing()) {
            field.classList.remove('is-valid', 'is-invalid');
            field.removeAttribute('aria-invalid');
            const span = byId('s' + field.id);
            if (span) span.textContent = '';
            return true;
        }

        const value = field.value.trim();
        let message = '';
        if (field.name === 'id_usuario') return true;
        if (!value) {
            message = '*Este campo es obligatorio*';
        } else if (fieldRules[field.name] && !fieldRules[field.name].pattern.test(value)) {
            message = fieldRules[field.name].message;
        } else if (field.id === 'clave_usuario') {
            const passwordIsValid = value.length >= 8 && value.length <= 15 &&
                passwordPattern.uppercase.test(value) &&
                passwordPattern.lowercase.test(value) &&
                passwordPattern.number.test(value) &&
                passwordPattern.special.test(value);
            if (!passwordIsValid) {
                message = 'Use 8-15 caracteres, con mayúscula, minúscula, número y símbolo.';
            }
        } else if (field.id === 'clave_confirmar' && value !== byId('clave_usuario').value) {
            message = 'Las contraseñas no coinciden.';
        }

        return setFieldState(field, message);
    }

    function validateForm() {
        let firstInvalidField = null;
        const fields = Array.from(form.querySelectorAll('input[name], select[name]'))
            .filter(function (field) {
                return field.name !== 'id_usuario' &&
                    (field.id !== 'clave_usuario' && field.id !== 'clave_confirmar' || !isEditing());
            });

        fields.forEach(function (field) {
            if (!validateField(field) && !firstInvalidField) firstInvalidField = field;
        });
        return { valid: !firstInvalidField, firstInvalidField: firstInvalidField };
    }

    function showServerFieldErrors(errors) {
        const fieldMap = {
            username: 'nombre_usuario',
            nombre: 'nombre',
            apellido: 'apellido_usuario',
            correo: 'correo_usuario',
            telefono: 'telefono_usuario',
            id_rol: 'rango',
            clave: 'clave_usuario'
        };
        Object.entries(errors || {}).forEach(function (entry) {
            const fieldId = fieldMap[entry[0]] || entry[0];
            const field = byId(fieldId);
            if (field && form.contains(field)) setFieldState(field, String(entry[1]));
        });
    }

    function formatCedula(field) {
        const digits = field.value.replace(/\D/g, '').slice(0, 8);
        if (digits.length === 7) {
            field.value = digits.slice(0, 1) + '.' + digits.slice(1, 4) + '.' + digits.slice(4);
        } else if (digits.length === 8) {
            field.value = digits.slice(0, 2) + '.' + digits.slice(2, 5) + '.' + digits.slice(5);
        } else {
            field.value = digits;
        }
    }

    function formatPhone(field) {
        const digits = field.value.replace(/\D/g, '').slice(0, 11);
        if (digits.length > 7) {
            field.value = digits.slice(0, 4) + '-' + digits.slice(4, 7) + '-' + digits.slice(7);
        } else if (digits.length > 4) {
            field.value = digits.slice(0, 4) + '-' + digits.slice(4);
        } else {
            field.value = digits;
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    function roleClass(role) {
        return String(role || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9_-]/g, '');
    }

    function getUserCells(user) {
        const id = Number(user.id_usuario);
        const username = escapeHtml(user.username);
        const fullName = escapeHtml([user.nombres, user.apellidos].filter(Boolean).join(' '));
        const role = escapeHtml(user.nombre_rol);
        const status = String(user.estatus || 'habilitado').toLowerCase() === 'habilitado'
            ? 'habilitado'
            : 'inhabilitado';
        const statusClass = status === 'habilitado' ? 'active' : 'inactive';
        const isSuperUser = String(user.nombre_rol || '').toLowerCase() === 'superusuario';
        const safeId = Number.isSafeInteger(id) && id > 0 ? id : 0;
        const statusButton = isSuperUser
            ? 'style="cursor:not-allowed;opacity:.6"'
            : 'data-user-action="status" title="Click para cambiar estado"';
        const actions = isSuperUser
            ? '<span style="color:#999;font-size:.9rem">NO DISPONIBLE</span>'
            : '<button type="button" class="btn-action btn-detallar" data-user-action="details" title="Detallar Usuario"><img src="assets/img/eye.svg" alt=""></button>' +
                '<button type="button" class="btn-action btn-modificar" data-user-action="edit" title="Modificar Usuario"><img src="assets/img/pencil.svg" alt=""></button>' +
                '<button type="button" class="btn-action btn-eliminar" data-user-action="delete" title="Eliminar Usuario"><img src="assets/img/circle-x.svg" alt=""></button>';

        return [
            '<span class="campo-texto">' + username + '</span>',
            '<span class="campo-texto">' + fullName + '</span>',
            '<span class="role-badge ' + roleClass(user.nombre_rol) + '">' + role + '</span>',
            '<span class="status ' + statusClass + '" ' + statusButton + '>' + escapeHtml(status) + '</span>',
            '<div class="action-buttons" data-user-id="' + safeId + '">' + actions + '</div>'
        ];
    }

    function updateSummaryCards() {
        const table = getDataTable();
        const rows = table ? table.rows().nodes().toArray() : Array.from(userTable.tBodies[0].rows);
        let active = 0;
        let inactive = 0;
        rows.forEach(function (row) {
            if (row.querySelector('.status')?.textContent.trim().toLowerCase() === 'habilitado') active += 1;
            else if (row.querySelector('.status')) inactive += 1;
        });
        if (byId('totalUsersCount')) byId('totalUsersCount').textContent = String(active + inactive);
        if (byId('activeUsersCount')) byId('activeUsersCount').textContent = String(active);
        if (byId('inactiveUsersCount')) byId('inactiveUsersCount').textContent = String(inactive);
    }

    function saveUserRow(user) {
        if (!user || !Number(user.id_usuario)) {
            throw new Error('El servidor no devolvió los datos necesarios para actualizar la lista.');
        }
        const table = getDataTable();
        const selector = 'tr[data-id="' + Number(user.id_usuario) + '"]';
        if (table) {
            const existing = table.row(selector);
            if (existing.length) {
                existing.data(getUserCells(user)).draw(false);
                existing.node().setAttribute('data-id', String(user.id_usuario));
            } else {
                const row = table.row.add(getUserCells(user)).draw(false).node();
                row.setAttribute('data-id', String(user.id_usuario));
            }
        } else {
            let row = userTable.querySelector(selector);
            if (!row) {
                row = userTable.tBodies[0].insertRow();
                row.dataset.id = String(user.id_usuario);
            }
            row.innerHTML = getUserCells(user).map(function (cell) {
                return '<td>' + cell + '</td>';
            }).join('');
        }
        updateSummaryCards();
        applyUserFilters();
    }

    function removeUserRow(userId) {
        const table = getDataTable();
        const selector = 'tr[data-id="' + Number(userId) + '"]';
        if (table) table.row(selector).remove().draw(false);
        else userTable.querySelector(selector)?.remove();
        updateSummaryCards();
    }

    function updateUserStatus(userId, status) {
        const table = getDataTable();
        const selector = 'tr[data-id="' + Number(userId) + '"]';
        const row = table ? table.row(selector).node() : userTable.querySelector(selector);
        const badge = row && row.querySelector('.status');
        if (!badge) return;
        badge.textContent = status;
        badge.classList.toggle('active', status === 'habilitado');
        badge.classList.toggle('inactive', status !== 'habilitado');
        if (table) {
            table.row(row).invalidate('dom').draw(false);
        }
        updateSummaryCards();
        applyUserFilters();
    }

    function applyUserFilters() {
        const table = getDataTable();
        if (!table) return;
        const roleValue = byId('roleFilter')?.value || '';
        const roleNames = {
            admin: 'administrador',
            gerente: 'gerente',
            vendedor: 'vendedor',
            almacen: 'almacenista'
        };
        const statusValue = byId('filtro-estatus')?.value || '';
        const roleName = roleNames[roleValue] || '';
        table.column(2).search(roleName, false, false);
        table.column(3).search(
            statusValue === 'active' ? '^habilitado$' :
                statusValue === 'inactive' ? '^inhabilitado$' : '',
            statusValue !== '',
            false
        );
        table.draw();
    }

    async function postUserAction(action, values) {
        const formData = new FormData();
        formData.append('accion', action);
        Object.entries(values || {}).forEach(function (entry) {
            formData.append(entry[0], entry[1] == null ? '' : String(entry[1]));
        });

        const response = await fetch(window.location.href.split('#')[0], {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        let result;
        try {
            result = await response.json();
        } catch (error) {
            throw new Error('El servidor respondió con un formato inesperado. Intente nuevamente.');
        }
        if (!response.ok || !result || result.status === 'error') {
            const validationErrors = result && result.errors
                ? Object.values(result.errors).join('\n')
                : '';
            throw new Error(validationErrors || (result && result.message) || 'La operación no pudo completarse.');
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

    function getRowUserName(row) {
        const username = row.cells[0]?.textContent.trim() || '';
        const fullName = row.cells[1]?.textContent.trim() || '';
        return fullName ? username + ' — ' + fullName : username;
    }

    async function fetchUser(userId) {
        const user = await postUserAction('obtener_usuario', { id_usuario: userId });
        if (!user || user.status === 'error' || !user.id_usuario) {
            throw new Error((user && user.message) || 'No se encontró el usuario solicitado.');
        }
        return user;
    }

    function setPasswordVisibility(button) {
        const input = document.querySelector(button.dataset.target);
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        renderPasswordToggle(button, visible);
    }

    function renderPasswordToggle(button, visible) {
        button.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
        button.setAttribute('aria-label', button.title);
        button.innerHTML = visible
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="m3 3 18 18" stroke="currentColor" stroke-width="2"/></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
    }

    function resetFormForOpen() {
        form.reset();
        byId('userId').value = '';
        clearValidation();
        ['clave_usuario', 'clave_confirmar'].forEach(function (id) {
            const input = byId(id);
            if (input) input.type = 'password';
        });
        document.querySelectorAll('#userModal .toggle-password').forEach(function (button) {
            renderPasswordToggle(button, false);
        });
    }

    async function openModal(type, userId) {
        const title = byId('modalTitle');
        const subtitle = document.querySelector('#userModal .modal-title-content p');
        const helpButton = document.querySelector('#userModal [data-contexto]');
        resetFormForOpen();

        if (type === 'editar') {
            try {
                const user = await fetchUser(userId);
                byId('userId').value = user.id_usuario;
                byId('nombre_usuario').value = user.username || '';
                byId('nombre').value = user.nombres || '';
                byId('apellido_usuario').value = user.apellidos || '';
                byId('cedula').value = user.cedula || '';
                byId('telefono_usuario').value = user.telefono || '';
                byId('correo_usuario').value = user.correo || '';
                byId('rango').value = user.id_rol || '';
                byId('passwordGroup').hidden = true;
                byId('confirmPasswordGroup').hidden = true;
                if (title) title.textContent = 'Modificar Usuario';
                if (subtitle) subtitle.textContent = 'Actualice los datos del usuario seleccionado';
                if (helpButton) {
                    helpButton.dataset.contexto = 'modificar';
                    helpButton.title = 'Ayuda para modificar usuarios';
                }
            } catch (error) {
                await showError('No se pudo consultar el usuario', error);
                return;
            }
        } else {
            byId('passwordGroup').hidden = false;
            byId('confirmPasswordGroup').hidden = false;
            if (title) title.textContent = 'Registrar Usuario';
            if (subtitle) subtitle.textContent = 'Complete los datos para incluir un nuevo usuario';
            if (helpButton) {
                helpButton.dataset.contexto = 'registrar';
                helpButton.title = 'Ayuda para registrar usuarios';
            }
        }
        showModal('userModal');
        const firstField = byId('nombre_usuario');
        if (firstField) firstField.focus();
    }

    async function viewUser(userId) {
        try {
            const user = await fetchUser(userId);
            const fields = {
                detailUsername: user.username,
                detailFirstName: user.nombres,
                detailLastName: user.apellidos,
                detailEmail: user.correo,
                detailCedula: user.cedula,
                detailPhone: user.telefono,
                detailRole: user.nombre_rol,
                detailStatus: user.estatus
            };
            Object.entries(fields).forEach(function (entry) {
                const element = byId(entry[0]);
                if (element) element.textContent = entry[1] || '-';
            });
            showModal('userDetailsModal');
        } catch (error) {
            await showError('No se pudo consultar el usuario', error);
        }
    }

    async function saveUser() {
        const validation = validateForm();
        if (!validation.valid) {
            validation.firstInvalidField.focus();
            await window.Swal.fire({
                icon: 'error',
                title: 'Revisa los datos del usuario',
                text: 'Corrige los campos señalados antes de continuar.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const button = document.querySelector('#userModal .btn-save');
        if (button) button.disabled = true;
        const editing = isEditing();
        try {
            const formData = new FormData(form);
            formData.delete('clave_confirmar');
            formData.set('accion', editing ? 'modificar' : 'registrar');
            const response = await fetch(window.location.href.split('#')[0], {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let result;
            try {
                result = await response.json();
            } catch (error) {
                throw new Error('El servidor respondió con un formato inesperado. Intente nuevamente.');
            }
            if (!response.ok || !result || result.status !== 'success') {
                if (result && result.errors) showServerFieldErrors(result.errors);
                const errors = result && result.errors ? Object.values(result.errors).join('\n') : '';
                throw new Error(errors || (result && result.message) || 'No se pudo guardar el usuario.');
            }
            saveUserRow(result.usuario);
            closeModal('userModal');
            resetFormForOpen();
            await window.Swal.fire({
                icon: 'success',
                title: editing ? 'Usuario modificado' : 'Usuario registrado',
                text: result.message || 'La operación se completó correctamente.',
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo guardar el usuario', error);
        } finally {
            if (button) button.disabled = false;
        }
    }

    function openDeleteUser(userId, row) {
        activeUserId = Number(userId);
        activeUserName = getRowUserName(row);
        byId('deleteUserName').textContent = activeUserName;
        showModal('deleteUserModal');
    }

    async function confirmDeleteUser() {
        const button = byId('confirmDeleteUser');
        if (button) button.disabled = true;
        try {
            await postUserAction('eliminar', { id_usuario: activeUserId });
            removeUserRow(activeUserId);
            closeModal('deleteUserModal');
            await window.Swal.fire({
                icon: 'success',
                title: 'Usuario eliminado',
                text: 'El usuario se eliminó correctamente.',
                confirmButtonText: 'Entendido'
            });
        } catch (error) {
            await showError('No se pudo eliminar el usuario', error);
        } finally {
            if (button) button.disabled = false;
        }
    }

    function openUserStatus(userId, currentStatus, row) {
        activeUserId = Number(userId);
        activeUserName = getRowUserName(row);
        activeUserStatus = String(currentStatus || '').toLowerCase();
        byId('statusUserName').textContent = activeUserName;
        byId('statusUserNext').textContent = activeUserStatus === 'habilitado'
            ? 'Inhabilitado'
            : 'Habilitado';
        showModal('userStatusModal');
    }

    async function confirmUserStatus() {
        const button = byId('confirmUserStatus');
        if (button) button.disabled = true;
        const nextStatus = activeUserStatus === 'habilitado' ? 'inhabilitado' : 'habilitado';
        try {
            await postUserAction('cambiar_estatus', {
                id_usuario: activeUserId,
                nuevo_estatus: nextStatus
            });
            updateUserStatus(activeUserId, nextStatus);
            closeModal('userStatusModal');
            await window.Swal.fire({
                icon: 'success',
                title: 'Estatus actualizado',
                text: 'El estatus del usuario se actualizó correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
        } catch (error) {
            await showError('No se pudo cambiar el estatus', error);
        } finally {
            if (button) button.disabled = false;
        }
    }

    function loadHelpModal(context) {
        const slides = { registrar: 1, modificar: 2, eliminar: 3, estatus: 4, reporte: 5 };
        if (helpModalInstance) {
            helpModalInstance.openModal();
            if (context && slides[context] !== undefined) {
                window.setTimeout(function () {
                    helpModalInstance.goToSlide(slides[context]);
                }, 300);
            }
            return Promise.resolve();
        }
        if (helpLoadPromise) return helpLoadPromise;

        helpLoadPromise = fetch('assets/public/ayuda/usuario.php')
            .then(function (response) {
                if (!response.ok) throw new Error('No se pudo cargar el contenido de ayuda.');
                return response.text();
            })
            .then(function (html) {
                const container = document.createElement('div');
                container.innerHTML = html;
                while (container.firstChild) document.body.appendChild(container.firstChild);
                if (!document.querySelector('link[data-user-help-style]')) {
                    const style = document.createElement('link');
                    style.rel = 'stylesheet';
                    style.href = 'assets/public/ayuda/css/modal.css';
                    style.dataset.userHelpStyle = 'true';
                    document.head.appendChild(style);
                }
                return new Promise(function (resolve, reject) {
                    if (typeof window.inicializarModalAyudaUsuario === 'function') {
                        resolve();
                        return;
                    }
                    const script = document.createElement('script');
                    script.src = 'assets/public/ayuda/js/modal.js';
                    script.onload = resolve;
                    script.onerror = function () {
                        reject(new Error('No se pudo cargar el comportamiento del modal de ayuda.'));
                    };
                    document.body.appendChild(script);
                });
            })
            .then(function () {
                if (typeof window.inicializarModalAyudaUsuario !== 'function') {
                    throw new Error('El modal de ayuda no se pudo inicializar.');
                }
                helpModalInstance = window.inicializarModalAyudaUsuario();
                helpModalInstance.openModal();
                if (context && slides[context] !== undefined) {
                    window.setTimeout(function () {
                        helpModalInstance.goToSlide(slides[context]);
                    }, 300);
                }
            })
            .catch(async function (error) {
                helpLoadPromise = null;
                await showError('No se pudo abrir la ayuda', error);
            });
        return helpLoadPromise;
    }

    function getKeypressPattern(field) {
        if (fieldRules[field.name]) return fieldRules[field.name].keypress;
        if (field.id === 'clave_usuario' || field.id === 'clave_confirmar') {
            return /^\S$/;
        }
        return null;
    }

    function initializeValidation() {
        form.querySelectorAll('input[name], select[name]').forEach(function (field) {
            const keypressPattern = getKeypressPattern(field);
            if (keypressPattern) {
                field.addEventListener('keypress', function (event) {
                    if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
                    if (!keypressPattern.test(event.key)) event.preventDefault();
                });
            }
            field.addEventListener('input', function () {
                if (field.id === 'cedula') formatCedula(field);
                if (field.id === 'telefono_usuario') formatPhone(field);
                validateField(field);
                if (field.id === 'clave_usuario' && byId('clave_confirmar').value) {
                    validateField(byId('clave_confirmar'));
                }
            });
            field.addEventListener('keyup', function () {
                validateField(field);
            });
            if (field.tagName === 'SELECT') {
                field.addEventListener('change', function () {
                    validateField(field);
                });
            }
        });
    }

    function initializeEvents() {
        const $ = window.jQuery;
        if ($) {
            $(userTable).on('init.dt.userModule', function () {
                applyUserFilters();
                updateSummaryCards();
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            saveUser();
        });
        document.querySelectorAll('#userModal .toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                setPasswordVisibility(button);
            });
        });
        document.querySelectorAll('#userModal .btn-save').forEach(function (button) {
            button.addEventListener('click', saveUser);
        });

        userTable.addEventListener('click', function (event) {
            const actionElement = event.target.closest('[data-user-action]');
            if (!actionElement) return;
            const row = actionElement.closest('tr[data-id]');
            if (!row) return;
            const userId = Number(row.dataset.id);
            const action = actionElement.dataset.userAction;
            if (action === 'details') viewUser(userId);
            else if (action === 'edit') openModal('editar', userId);
            else if (action === 'delete') openDeleteUser(userId, row);
            else if (action === 'status') {
                openUserStatus(userId, actionElement.textContent.trim(), row);
            }
        });

        byId('confirmDeleteUser').addEventListener('click', confirmDeleteUser);
        byId('confirmUserStatus').addEventListener('click', confirmUserStatus);
        byId('roleFilter')?.addEventListener('change', applyUserFilters);
        byId('filtro-estatus')?.addEventListener('change', applyUserFilters);

        document.addEventListener('click', function (event) {
            const helpButton = event.target.closest('.btn-ayuda, .btn-ayuda-modal, .btn-ayuda-usuario, [data-ayuda="usuario"]');
            if (helpButton && !helpButton.closest('#modalAyuda')) {
                event.preventDefault();
                loadHelpModal(helpButton.dataset.contexto || '');
                return;
            }
            const modal = event.target.classList && event.target.classList.contains('modal')
                ? event.target
                : null;
            if (modal && modalIds.includes(modal.id)) closeModal(modal.id);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                modalIds.forEach(closeModal);
            }
        });
    }

    function exposePublicFunctions() {
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.viewUser = viewUser;
        window.saveUser = saveUser;
        window.deleteUser = function (userId) {
            const row = userTable.querySelector('tr[data-id="' + Number(userId) + '"]');
            if (row) openDeleteUser(userId, row);
        };
        window.toggleUserStatus = function (userId, status) {
            const row = userTable.querySelector('tr[data-id="' + Number(userId) + '"]');
            if (row) openUserStatus(userId, status, row);
        };
        window.searchUsers = applyUserFilters;
        window.filterByRole = applyUserFilters;
        window.filterByStatus = applyUserFilters;
    }

    function closeViewModal() {
        const modal = document.getElementById('userDetailsModal');
        if (!modal) return;
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }

    function initialize() {
        initializeValidation();
        initializeEvents();
        exposePublicFunctions();
        closeViewModal();
        updateSummaryCards();
        applyUserFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
