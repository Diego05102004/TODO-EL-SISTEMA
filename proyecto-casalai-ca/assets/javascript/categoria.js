(function () {
    'use strict';

    const ENDPOINT = '?pagina=categoria';
    const MAX_CARACTERISTICAS = 5;
    const MAX_NOMBRE = 20;
    const MAX_NOMBRE_CARACTERISTICA = 20;
    const MAX_LONGITUD_STRING = 255;
    const REGEX_NOMBRE = /^[a-zA-ZÁÉÍÓÚÜÑáéíóúüñ0-9\s]*$/;

    const form = document.getElementById('categoriaForm');
    const modal = document.getElementById('categoriaModal');
    const deleteModal = document.getElementById('deleteCategoriaModal');
    const table = document.getElementById('tablaCategorias');
    const nameInput = document.getElementById('nombre_categoria');
    const nameError = document.getElementById('snombre_categoria');
    const featuresContainer = document.getElementById('caracteristicasContainer');
    const featuresError = document.getElementById('scaracteristicas');
    const addFeatureButton = document.getElementById('btn-agregar-caracteristica');
    const saveButton = form && form.querySelector('.btn-save');
    let activeDeleteId = null;
    let helpInstance = null;
    let currentPermissions = null;
    let rejectedCategoryNameKey = false;

    function getDataTable() {
        const $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.DataTable ||
            !$.fn.DataTable.isDataTable(table)) {
            return null;
        }
        return $(table).DataTable();
    }

    async function postAction(formData) {
        const response = await fetch(ENDPOINT, { method: 'POST', body: formData });
        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error('Respuesta no válida del servidor:', text);
            throw new Error('El servidor devolvió una respuesta no válida.');
        }

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo completar la solicitud.');
        }

        return data;
    }

    function showMessage(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({ icon: icon, title: title, text: text });
        }
        window.alert(title + (text ? '\n' + text : ''));
        return Promise.resolve();
    }

    function setFieldError(field, errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message || '';
        }
        if (field) {
            field.classList.toggle('input-error', Boolean(message));
            field.classList.toggle('is-invalid', Boolean(message));
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
        }
    }

    function normalizeSpaces(value) {
        return value.replace(/\s{2,}/g, ' ');
    }

    function validateCategoryName() {
        const value = nameInput.value.trim();
        let message = '';

        if (!value) {
            message = '*Ingrese el nombre de la categoría*';
        } else if (value.length < 2) {
            message = '*El nombre debe tener al menos 2 caracteres*';
        } else if (value.length > MAX_NOMBRE) {
            message = '*El nombre no puede superar ' + MAX_NOMBRE + ' caracteres*';
        } else if (!REGEX_NOMBRE.test(value)) {
            message = '*Use solo letras, números y espacios*';
        }

        setFieldError(nameInput, nameError, message);
        return !message;
    }

    function updateFeatureAddButton() {
        const count = featuresContainer.querySelectorAll('.caracteristica-item').length;
        addFeatureButton.disabled = count >= MAX_CARACTERISTICAS;
    }

    function featureItems() {
        return Array.from(featuresContainer.querySelectorAll('.caracteristica-item'));
    }

    function featureField(item, name) {
        return item.querySelector('.carac-' + name);
    }

    function validateFeatureName(item) {
        const field = featureField(item, 'nombre');
        const errorElement = item.querySelector('.error-nombre');
        const value = field.value.trim();
        let message = '';

        if (!value) {
            message = '*Ingrese el nombre de la característica*';
        } else if (value.length < 2) {
            message = '*Use al menos 2 caracteres*';
        } else if (value.length > MAX_NOMBRE_CARACTERISTICA) {
            message = '*No supere ' + MAX_NOMBRE_CARACTERISTICA + ' caracteres*';
        } else if (!REGEX_NOMBRE.test(value)) {
            message = '*Use solo letras, números y espacios*';
        }

        setFieldError(field, errorElement, message);
        return !message;
    }

    function validateFeatureType(item) {
        const field = featureField(item, 'tipo');
        const errorElement = item.querySelector('.error-tipo');
        const message = ['int', 'float', 'string'].includes(field.value)
            ? ''
            : '*Seleccione un tipo*';

        setFieldError(field, errorElement, message);
        const maxField = featureField(item, 'max');
        maxField.closest('.caracteristica-campo').hidden = field.value !== 'string';
        maxField.required = field.value === 'string';
        return !message;
    }

    function validateFeatureMax(item) {
        const typeField = featureField(item, 'tipo');
        const field = featureField(item, 'max');
        const errorElement = item.querySelector('.error-max');

        if (typeField.value !== 'string') {
            setFieldError(field, errorElement, '');
            return true;
        }

        const value = Number(field.value);
        let message = '';
        if (!field.value.trim() || !Number.isInteger(value) || value < 1 || value > MAX_LONGITUD_STRING) {
            message = '*Ingrese un máximo entre 1 y 255*';
        }

        setFieldError(field, errorElement, message);
        return !message;
    }

    function validateFeatures() {
        const items = featureItems();
        if (!items.length) {
            featuresError.textContent = '*Agregue al menos una característica*';
            return false;
        }

        let valid = true;
        items.forEach(function (item) {
            if (!validateFeatureName(item)) valid = false;
            if (!validateFeatureType(item)) valid = false;
            if (!validateFeatureMax(item)) valid = false;
        });

        featuresError.textContent = valid ? '' : '*Revise los campos de características marcados*';
        return valid;
    }

    function updateRemoveButtons() {
        const items = featureItems();
        items.forEach(function (item) {
            const button = item.querySelector('.btn-eliminar-caracteristica');
            if (button) {
                button.hidden = items.length <= 1;
            }
        });
    }

    function addFeature(data) {
        const item = document.createElement('div');
        item.className = 'caracteristica-item';

        const nameGroup = document.createElement('div');
        nameGroup.className = 'form-group caracteristica-campo caracteristica-nombre';
        nameGroup.style.flex = '1';
        nameGroup.style.marginBottom = '0';
        const name = document.createElement('input');
        name.type = 'text';
        name.className = 'input-textnum carac-nombre';
        name.placeholder = 'Nombre';
        name.maxLength = MAX_NOMBRE_CARACTERISTICA;
        name.value = data && data.nombre ? data.nombre : '';
        name.setAttribute('aria-label', 'Nombre de característica');

        const nameErrorElement = document.createElement('span');
        nameErrorElement.className = 'span-value error-nombre';
        nameGroup.appendChild(name);
        nameGroup.appendChild(nameErrorElement);

        const typeGroup = document.createElement('div');
        typeGroup.className = 'form-group caracteristica-campo caracteristica-tipo';
        typeGroup.style.flex = '1';
        typeGroup.style.marginBottom = '0';
        const type = document.createElement('select');
        type.className = 'input-select carac-tipo';
        type.setAttribute('aria-label', 'Tipo de característica');
        [
            ['', 'Tipo'],
            ['int', 'Número entero'],
            ['float', 'Número decimal'],
            ['string', 'Texto']
        ].forEach(function (optionData) {
            const option = document.createElement('option');
            option.value = optionData[0];
            option.textContent = optionData[1];
            type.appendChild(option);
        });
        type.value = data && ['int', 'float', 'string'].includes(data.tipo) ? data.tipo : '';

        const typeErrorElement = document.createElement('span');
        typeErrorElement.className = 'span-value error-tipo';
        typeGroup.appendChild(type);
        typeGroup.appendChild(typeErrorElement);

        const maxGroup = document.createElement('div');
        maxGroup.className = 'caracteristica-campo caracteristica-max';
        maxGroup.style.marginBottom = '0';
        const max = document.createElement('input');
        max.type = 'number';
        max.className = 'input-textnum carac-max';
        max.placeholder = 'Máximo de caracteres';
        max.min = '1';
        max.max = String(MAX_LONGITUD_STRING);
        max.maxLength = 3;
        max.value = data && data.max ? data.max : '';
        max.required = type.value === 'string';
        max.setAttribute('aria-label', 'Máximo de caracteres');

        const maxErrorElement = document.createElement('span');
        maxErrorElement.className = 'span-value error-max';
        maxGroup.appendChild(max);
        maxGroup.appendChild(maxErrorElement);
        typeGroup.appendChild(maxGroup);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn-remove-caracteristica btn-eliminar-caracteristica';
        remove.title = 'Eliminar característica';
        remove.setAttribute('aria-label', 'Eliminar característica');
        remove.innerHTML = '<i class="fas fa-times"></i>';

        item.appendChild(nameGroup);
        item.appendChild(typeGroup);
        item.appendChild(remove);
        featuresContainer.appendChild(item);

        let rejectedNameKey = false;
        name.addEventListener('keypress', function (event) {
            if (event.key.length === 1 && !REGEX_NOMBRE.test(event.key)) {
                event.preventDefault();
                rejectedNameKey = true;
                setFieldError(name, nameErrorElement, '*Use solo letras, números y espacios*');
            }
        });
        name.addEventListener('keyup', function () {
            if (rejectedNameKey) {
                rejectedNameKey = false;
                return;
            }
            this.value = normalizeSpaces(this.value);
            validateFeatureName(item);
        });
        name.addEventListener('input', function () {
            this.value = normalizeSpaces(this.value);
            validateFeatureName(item);
        });
        name.addEventListener('blur', function () {
            validateFeatureName(item);
        });

        type.addEventListener('keypress', function () {
            validateFeatureType(item);
        });
        type.addEventListener('keyup', function () {
            validateFeatureType(item);
            validateFeatureMax(item);
        });
        type.addEventListener('change', function () {
            validateFeatureType(item);
            validateFeatureMax(item);
        });

        let rejectedMaxKey = false;
        max.addEventListener('keypress', function (event) {
            if (event.key.length === 1 && !/[0-9]/.test(event.key)) {
                event.preventDefault();
                rejectedMaxKey = true;
                setFieldError(max, maxErrorElement, '*Ingrese solo números*');
            }
        });
        max.addEventListener('keyup', function () {
            if (rejectedMaxKey) {
                rejectedMaxKey = false;
                return;
            }
            validateFeatureMax(item);
        });
        max.addEventListener('input', function () {
            validateFeatureMax(item);
        });
        max.addEventListener('blur', function () {
            validateFeatureMax(item);
        });

        remove.addEventListener('click', function () {
            if (featureItems().length <= 1) return;
            item.remove();
            updateFeatureAddButton();
            updateRemoveButtons();
            validateFeatures();
        });

        maxGroup.hidden = type.value !== 'string';
        max.required = type.value === 'string';
        updateFeatureAddButton();
        updateRemoveButtons();
    }

    function getFeatures() {
        return featureItems().map(function (item) {
            const type = featureField(item, 'tipo').value;
            return {
                nombre: featureField(item, 'nombre').value.trim(),
                tipo: type,
                max: type === 'string' ? Number(featureField(item, 'max').value) : null
            };
        });
    }

    function resetForm(mode) {
        form.reset();
        document.getElementById('accionForm').value = mode;
        document.getElementById('id_categoria').value = '';
        nameInput.value = '';
        setFieldError(nameInput, nameError, '');
        featuresContainer.replaceChildren();
        featuresError.textContent = '';
        addFeature({});

        const editing = mode === 'modificar';
        document.getElementById('modalTitle').textContent = editing ? 'Modificar Categoría' : 'Incluir Categoría';
        document.getElementById('modalSubtitle').textContent = editing
            ? 'Actualice el nombre y las características de la categoría'
            : 'Complete los datos para incluir una nueva categoría';
        const labelNode = saveButton.lastChild;
        if (labelNode && labelNode.nodeType === Node.TEXT_NODE) {
            labelNode.nodeValue = editing ? ' Modificar' : ' Registrar';
        }
        const helpButton = modal.querySelector('.btn-help');
        if (helpButton) {
            helpButton.dataset.contexto = editing ? 'modificar' : 'registrar';
            helpButton.title = editing ? 'Ayuda para modificar categorías' : 'Ayuda para registrar categorías';
        }
    }

    function openModal(mode) {
        const actualMode = mode === 'modificar' ? 'modificar' : 'registrar';
        resetForm(actualMode);
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        nameInput.focus();
    }

    function closeModal(id) {
        const target = id ? document.getElementById(id) : modal;
        if (!target) return;
        target.style.display = 'none';
        target.setAttribute('aria-hidden', 'true');
        if (target === deleteModal) {
            activeDeleteId = null;
        }
        if (modal.style.display !== 'block' && deleteModal.style.display !== 'block') {
            document.body.classList.remove('modal-open');
        }
    }

    function rowForId(id) {
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

    function renderRow(category) {
        const row = document.createElement('tr');
        row.dataset.id = category.id_categoria;
        row.dataset.nombreCategoria = category.nombre_categoria || '';

        const idCell = document.createElement('td');
        const idText = document.createElement('span');
        idText.className = 'campo-texto';
        idText.textContent = category.id_categoria;
        idCell.appendChild(idText);

        const nameCell = document.createElement('td');
        const nameText = document.createElement('span');
        nameText.className = 'campo-texto';
        nameText.textContent = category.nombre_categoria || '';
        nameCell.appendChild(nameText);

        const actionsCell = document.createElement('td');
        actionsCell.className = 'action-buttons';
        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn-action btn-modificar';
        editButton.title = 'Modificar Categoría';
        editButton.dataset.userAction = 'edit';
        editButton.innerHTML = '<img src="assets/img/pencil.svg" alt="">';
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn-action btn-eliminar';
        deleteButton.title = 'Eliminar Categoría';
        deleteButton.dataset.userAction = 'delete';
        deleteButton.innerHTML = '<img src="assets/img/circle-x.svg" alt="">';
        actionsCell.appendChild(editButton);
        actionsCell.appendChild(deleteButton);

        row.appendChild(idCell);
        row.appendChild(nameCell);
        row.appendChild(actionsCell);
        return row;
    }

    function insertCategoryRow(category) {
        const row = renderRow(category);
        const dataTable = getDataTable();
        if (dataTable) {
            dataTable.row.add(row).draw(false);
        } else {
            table.tBodies[0].insertBefore(row, table.tBodies[0].firstChild);
        }
        applyPermissions();
    }

    function updateCategoryRow(category) {
        const row = rowForId(category.id_categoria);
        if (!row) {
            insertCategoryRow(category);
            return;
        }
        const updatedRow = renderRow(category);
        row.dataset.id = updatedRow.dataset.id;
        row.dataset.nombreCategoria = updatedRow.dataset.nombreCategoria;
        while (row.firstChild) row.removeChild(row.firstChild);
        Array.from(updatedRow.cells).forEach(function (cell) {
            row.appendChild(cell);
        });
        const dataTable = getDataTable();
        if (dataTable) dataTable.row(row).invalidate('dom').draw(false);
        applyPermissions();
    }

    function removeCategoryRow(id) {
        const row = rowForId(id);
        if (!row) return;
        const dataTable = getDataTable();
        if (dataTable) dataTable.row(row).remove().draw(false);
        else row.remove();
    }

    function applyPermissions() {
        if (!currentPermissions) return;
        const canConsult = Boolean(currentPermissions.consultar);
        const sectionHeader = document.querySelector('#contenedor-categorias .section-header');
        const permissionMessage = document.getElementById('mensaje-permiso');
        const includeButton = document.querySelector('#contenedor-categorias .btn-incluir');
        const tableWrapper = table.closest('.dataTables_wrapper, .dt-container');

        table.hidden = !canConsult;
        if (tableWrapper) tableWrapper.hidden = !canConsult;
        if (sectionHeader) sectionHeader.hidden = !canConsult;
        if (permissionMessage) {
            permissionMessage.style.display = canConsult ? 'none' : 'block';
            permissionMessage.textContent = canConsult
                ? ''
                : 'No tiene permiso para consultar los registros.';
        }
        if (includeButton) includeButton.hidden = !currentPermissions.incluir;
        table.querySelectorAll('.btn-modificar').forEach(function (button) {
            button.hidden = !currentPermissions.modificar;
        });
        table.querySelectorAll('.btn-eliminar').forEach(function (button) {
            button.hidden = !currentPermissions.eliminar;
        });
    }

    async function refreshPermissions() {
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
            console.error('Error al verificar permisos de categorías:', error);
        }
    }

    function showServerErrors(data) {
        const errors = data && data.errors && typeof data.errors === 'object' ? data.errors : {};
        let hasFeatureError = false;
        Object.keys(errors).forEach(function (key) {
            const message = String(errors[key]);
            if (key === 'nombre_categoria') {
                setFieldError(nameInput, nameError, message);
                return;
            }
            const match = key.match(/^caracteristica_(\d+)_(nombre|tipo|max)$/);
            if (match) {
                const item = featureItems()[Number(match[1])];
                if (item) {
                    const fieldName = match[2];
                    setFieldError(
                        featureField(item, fieldName),
                        item.querySelector('.error-' + fieldName),
                        message
                    );
                }
                hasFeatureError = true;
            }
        });
        if (hasFeatureError) {
            featuresError.textContent = '*Revise los campos de características marcados*';
        }
        return Object.keys(errors).map(function (key) {
            return String(errors[key]);
        }).join('\n');
    }

    async function saveCategory(event) {
        if (event) event.preventDefault();
        const nameValid = validateCategoryName();
        const featuresValid = validateFeatures();
        if (!nameValid || !featuresValid) {
            await showMessage('warning', 'Verifique los datos', 'Complete correctamente los campos marcados.');
            const invalidField = form.querySelector('[aria-invalid="true"]');
            if (invalidField) invalidField.focus();
            return;
        }

        const mode = document.getElementById('accionForm').value;
        const id = document.getElementById('id_categoria').value;
        const features = getFeatures();
        const formData = new FormData();
        formData.append('accion', mode);
        formData.append('nombre_categoria', nameInput.value.trim());
        if (mode === 'modificar') {
            formData.append('id_categoria', id);
            formData.append('caracteristicas', JSON.stringify(features));
        } else {
            features.forEach(function (feature, index) {
                formData.append('caracteristicas[' + index + '][nombre]', feature.nombre);
                formData.append('caracteristicas[' + index + '][tipo]', feature.tipo);
                if (feature.tipo === 'string') {
                    formData.append('caracteristicas[' + index + '][max]', String(feature.max));
                }
            });
        }

        saveButton.disabled = true;
        try {
            const data = await postAction(formData);
            if (data.status !== 'success' || !data.categoria || !data.categoria.id_categoria) {
                const detail = showServerErrors(data);
                await showMessage('error', 'No se pudo guardar la categoría', detail || data.message || 'Inténtelo de nuevo.');
                return;
            }

            if (mode === 'modificar') {
                updateCategoryRow(data.categoria);
            } else {
                insertCategoryRow(data.categoria);
            }
            closeModal();
            await showMessage('success', 'Operación exitosa', data.message || 'Los cambios se guardaron correctamente.');
        } catch (error) {
            console.error('Error al guardar la categoría:', error);
            await showMessage('error', 'Error', error.message || 'No se pudo guardar la categoría.');
        } finally {
            saveButton.disabled = false;
        }
    }

    async function openEditCategory(row) {
        const id = row.dataset.id;
        openModal('modificar');
        document.getElementById('id_categoria').value = id;
        nameInput.value = row.dataset.nombreCategoria || row.cells[1].textContent.trim();
        featuresContainer.textContent = 'Cargando características...';
        addFeatureButton.disabled = true;
        saveButton.disabled = true;

        const formData = new FormData();
        formData.append('accion', 'obtener_categoria');
        formData.append('id_categoria', id);
        try {
            const data = await postAction(formData);
            if (!data || data.status === 'error' || !data.id_categoria) {
                throw new Error(data && data.message ? data.message : 'No se pudieron cargar las características.');
            }
            nameInput.value = data.nombre_categoria || nameInput.value;
            featuresContainer.replaceChildren();
            const features = Array.isArray(data.caracteristicas) ? data.caracteristicas : [];
            if (features.length) {
                features.forEach(addFeature);
            } else {
                addFeature({});
            }
            validateCategoryName();
        } catch (error) {
            console.error('Error al cargar la categoría:', error);
            featuresContainer.replaceChildren();
            addFeature({});
            await showMessage('error', 'Error', error.message || 'No se pudieron cargar los datos de la categoría.');
        } finally {
            saveButton.disabled = false;
        }
    }

    async function requestDeleteCategory() {
        if (!activeDeleteId) return;
        const button = document.getElementById('confirmDeleteCategoria');
        button.disabled = true;
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id_categoria', activeDeleteId);
        try {
            const data = await postAction(formData);
            if (data.status !== 'success') {
                await showMessage('error', 'No se pudo eliminar la categoría', data.message || 'Inténtelo de nuevo.');
                return;
            }
            removeCategoryRow(activeDeleteId);
            closeModal('deleteCategoriaModal');
            await showMessage('success', 'Operación exitosa', data.message || 'La categoría se eliminó correctamente.');
        } catch (error) {
            console.error('Error al eliminar la categoría:', error);
            await showMessage('error', 'Error', error.message || 'No se pudo eliminar la categoría.');
        } finally {
            button.disabled = false;
        }
    }

    function openDeleteCategory(row) {
        activeDeleteId = row.dataset.id;
        document.getElementById('deleteCategoriaName').textContent =
            row.dataset.nombreCategoria || row.cells[1].textContent.trim();
        deleteModal.style.display = 'block';
        deleteModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function loadHelpResource(type, url) {
        return new Promise(function (resolve, reject) {
            const existing = type === 'css'
                ? document.querySelector('link[href*="' + url + '"]')
                : document.querySelector('script[src*="' + url + '"]');
            if (existing) {
                resolve();
                return;
            }

            const element = document.createElement(type === 'css' ? 'link' : 'script');
            if (type === 'css') {
                element.rel = 'stylesheet';
                element.href = url;
            } else {
                element.src = url;
            }
            element.onload = resolve;
            element.onerror = function () {
                reject(new Error('No se pudo cargar el recurso de ayuda: ' + url));
            };
            document.head.appendChild(element);
        });
    }

    async function openHelp(context) {
        try {
            await loadHelpResource('css', 'assets/public/ayuda/css/modal.css');
            if (!document.getElementById('modalAyuda')) {
                const response = await fetch('assets/public/ayuda/categorias.php');
                if (!response.ok) throw new Error('No se pudo cargar el contenido de ayuda.');
                document.body.insertAdjacentHTML('beforeend', await response.text());
            }
            await loadHelpResource('js', 'assets/public/ayuda/js/modal.js');
            if (typeof window.inicializarModalAyudaUsuario !== 'function') {
                throw new Error('La función de inicialización de ayuda no está disponible.');
            }
            helpInstance = window.inicializarModalAyudaUsuario();
            document.body.style.overflow = '';
            if (context && helpInstance.mapeoContextos) {
                const slideIndex = helpInstance.mapeoContextos[context];
                if (slideIndex !== undefined) {
                    window.setTimeout(function () {
                        helpInstance.goToSlide(slideIndex);
                    }, 300);
                }
            }
            helpInstance.openModal();
        } catch (error) {
            console.error('Error al cargar la ayuda de categorías:', error);
            await showMessage('error', 'Error', error.message || 'No se pudo cargar el contenido de ayuda.');
        }
    }

    if (!form || !modal || !deleteModal || !table || !nameInput || !featuresContainer) {
        console.error('No se encontraron los elementos requeridos para el módulo de categorías.');
        return;
    }

    nameInput.addEventListener('keypress', function (event) {
        if (event.key.length === 1 && !REGEX_NOMBRE.test(event.key)) {
            event.preventDefault();
            rejectedCategoryNameKey = true;
            setFieldError(nameInput, nameError, '*Use solo letras, números y espacios*');
        }
    });
    nameInput.addEventListener('keyup', function () {
        if (rejectedCategoryNameKey) {
            rejectedCategoryNameKey = false;
            return;
        }
        validateCategoryName();
    });
    nameInput.addEventListener('input', function () {
        this.value = normalizeSpaces(this.value);
        validateCategoryName();
    });
    nameInput.addEventListener('blur', validateCategoryName);

    addFeatureButton.addEventListener('click', function () {
        if (featureItems().length < MAX_CARACTERISTICAS) addFeature({});
    });
    form.addEventListener('submit', saveCategory);
    saveButton.addEventListener('click', saveCategory);

    table.querySelector('tbody').addEventListener('click', function (event) {
        const button = event.target.closest('.btn-modificar, .btn-eliminar');
        if (!button) return;
        const row = button.closest('tr[data-id]');
        if (!row) return;
        if (button.classList.contains('btn-modificar')) {
            openEditCategory(row);
        } else {
            openDeleteCategory(row);
        }
    });

    document.getElementById('confirmDeleteCategoria').addEventListener('click', requestDeleteCategory);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    deleteModal.addEventListener('click', function (event) {
        if (event.target === deleteModal) closeModal('deleteCategoriaModal');
    });

    document.querySelectorAll('.btn-ayuda, .btn-help, .btn-ayuda-modal').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openHelp(button.dataset.contexto || '');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (deleteModal.style.display === 'block') closeModal('deleteCategoriaModal');
            else if (modal.style.display === 'block') closeModal();
        }
    });

    window.openModal = openModal;
    window.closeModal = closeModal;
    refreshPermissions();
    window.setInterval(refreshPermissions, 10000);
})();
