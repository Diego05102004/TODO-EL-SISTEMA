function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    // Guardar opciones originales de cada select
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

    // Monitorear periódicamente cambios en las opciones
    setInterval(() => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            const opsActuales = Array.from(select.options);
            const opsOriginales = originales[id];
            if (!opsOriginales) return;

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    !opsOriginales[i] ||
                    o.value !== opsOriginales[i].value ||
                    o.textContent !== opsOriginales[i].text
                );

            if (alterado) {
                const valorSeleccionado = select.value;
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });
                if (opsOriginales.some(optData => optData.value === valorSeleccionado)) {
                    select.value = valorSeleccionado;
                }

                console.warn(`⚠ Opciones del <select id="${id}"> fueron alteradas. Restauradas automáticamente.`);
            }
        });
    }, interval);
}

// Helper functions for validation
function validarkeypress(er, e) {
    const key = e.key;
    const keyCode = e.keyCode || e.which;
    const tecla = String.fromCharCode(keyCode);
    const specials = [8, 37, 39, 46]; // Backspace, left arrow, right arrow, delete
    
    if (specials.includes(keyCode)) return true;
    
    const regex = new RegExp(er);
    if (!regex.test(tecla)) {
        e.preventDefault();
        return false;
    }
    return true;
}

function space(str) {
    return str.replace(/\s+/g, ' ').trim();
}

function soloTextoPermitido(e) {
    // Si es el campo de cláusula de garantía, permitir espacios sin restricciones
    if (e.target.id === 'Clausula_garantia') {
        return true; // No aplicar restricciones de caracteres
    }
    
    // Para otros campos: permite letras, números, espacio, @, . y -
    const regex = /^[a-zA-Z0-9@\.\-\s]+$/;
    let valor = e.target.value;
    // Si el valor no cumple, elimina el último caracter ingresado
    if (!regex.test(valor)) {
        e.target.value = valor.replace(/[^a-zA-Z0-9@\.\-\s]/g, '');
    }
}

// Agregar después de la función soloTextoPermitido

const regexNombreProducto = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,40}$/;
const regexDescripcionProducto = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s\b]{2,50}$/;
const regexSerialesProducto = /^[a-zA-Z0-9-]{1,20}$/;
const regexPrecioProducto = /^\d+(\.\d{1,2})?$/;
const regexCaracteristicasTexto = /^[a-zA-Z0-9@\.\-\sÁÉÍÓÚáéíóúñÑ]+$/;

const productoFormConfigs = {
    registrar: {
        tipo: 'registrar',
        formSelector: '#incluirProductoForm',
        campos: {
            nombre: { selector: '#nombre_producto', span: '#snombre_producto', label: 'nombre del producto' },
            descripcion: { selector: '#descripcion_producto', span: '#sdescripcion_producto', label: 'descripción del producto' },
            modelo: { selector: '#modelo', span: '#smodelo', label: 'modelo' },
            imagen: { selector: '#imagen', span: '#simagen', label: 'imagen del producto' },
            stockActual: { selector: '#Stock_Actual', span: '#sStock_Actual', label: 'stock actual' },
            stockMaximo: { selector: '#Stock_Maximo', span: '#sStock_Maximo', label: 'stock máximo' },
            stockMinimo: { selector: '#Stock_Minimo', span: '#sStock_Minimo', label: 'stock mínimo' },
            clausula: { selector: '#Clausula_garantia', span: '#sClausula_garantia', label: 'cláusula de garantía' },
            categoria: { selector: '#Categoria', span: '#sCategoria', label: 'categoría' },
            seriales: { selector: '#Seriales', span: '#sSeriales', label: 'seriales' },
            precio: { selector: '#Precio', span: '#sPrecio', label: 'precio' }
        },
        caracteristicasContenedor: '#caracteristicasCategoria',
        tablaCategoriaHidden: '#tabla_categoria'
    },
    modificar: {
        tipo: 'modificar',
        formSelector: '#modificarProductoForm',
        campos: {
            nombre: { selector: '#modificarNombreProducto', span: '#smodificarNombreProducto', label: 'nombre del producto' },
            descripcion: { selector: '#modificarDescripcionProducto', span: '#smodificarDescripcionProducto', label: 'descripción del producto' },
            modelo: { selector: '#modificarModelo', span: '#smodificarModelo', label: 'modelo' },
            imagen: { selector: '#modificarImagen', span: '#smodificarImagen', label: 'imagen del producto' },
            stockActual: { selector: '#modificarStockActual', span: '#smodificarStockActual', label: 'stock actual' },
            stockMaximo: { selector: '#modificarStockMaximo', span: '#smodificarStockMaximo', label: 'stock máximo' },
            stockMinimo: { selector: '#modificarStockMinimo', span: '#smodificarStockMinimo', label: 'stock mínimo' },
            clausula: { selector: '#modificarClausulaGarantia', span: '#smodificarClausulaGarantia', label: 'cláusula de garantía' },
            categoria: { selector: '#modificarCategoria', span: '#smodificarCategoria', label: 'categoría' },
            seriales: { selector: '#modificarSeriales', span: '#smodificarSeriales', label: 'seriales' },
            precio: { selector: '#modificarPrecio', span: '#smodificarPrecio', label: 'precio' }
        },
        caracteristicasContenedor: '#caracteristicasCategoriaModificar',
        tablaCategoriaHidden: '#modificar_tabla_categoria'
    }
};

function obtenerCampoConfig(campoConfig) {
    return {
        $campo: $(campoConfig.selector),
        $span: campoConfig.span ? $(campoConfig.span) : $()
    };
}

function marcarCampoValido($campo, $span) {
    $campo.removeClass('is-invalid').addClass('is-valid');
    if ($span.length) $span.text('');
}

function marcarCampoInvalido($campo, $span, mensaje) {
    $campo.removeClass('is-valid').addClass('is-invalid');
    if ($span.length) $span.text(mensaje);
}

function validarNombreProductoCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El nombre del producto es obligatorio*');
        error = 'El nombre del producto es obligatorio';
    } else if (!regexNombreProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Solo letras, de 3 a 20 caracteres*');
        error = 'El nombre del producto solo puede contener letras y espacios (3-20 caracteres)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarDescripcionProductoCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*La descripción es obligatoria*');
        error = 'La descripción del producto es obligatoria';
    } else if (!regexDescripcionProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Máximo 50 caracteres*');
        error = 'La descripción debe tener entre 2 y 50 caracteres';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarModeloCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    let error = null;

    if (!valor) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar un modelo*');
        error = 'Debe seleccionar un modelo';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarImagenCampo(campoConfig, requerida = true) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const tieneArchivo = ($campo[0] && $campo[0].files && $campo[0].files.length > 0) || ($campo.val() && $campo.val().trim() !== '');
    let error = null;

    if (!tieneArchivo && requerida) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar una imagen*');
        error = 'Debe seleccionar una imagen';
    } else if (!tieneArchivo && !requerida) {
        // Si no es requerida y no hay archivo, limpiar validación
        marcarCampoValido($campo, $span);
    } else if (tieneArchivo) {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarNumeroNoNegativo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    const numero = parseInt(valor, 10);

    if (valor === '' || isNaN(numero) || numero < 0) {
        marcarCampoInvalido($campo, $span, '*Ingrese un valor válido (0 o mayor)*');
        return `El ${campoConfig.label} debe ser un número válido (0 o mayor)`;
    }

    marcarCampoValido($campo, $span);
    return { numero, error: null };
}

function validarStockCampo(campoConfig, extra = {}) {
    const resultado = validarNumeroNoNegativo(campoConfig);

    if (typeof resultado === 'string') {
        return resultado;
    }

    if (extra.compare && typeof extra.compare === 'function') {
        const compareResult = extra.compare(resultado.numero);
        if (compareResult) {
            const { $campo, $span } = obtenerCampoConfig(campoConfig);
            marcarCampoInvalido($campo, $span, compareResult.spanMensaje || '*Valor inválido*');
            return compareResult.errorMensaje || `El ${campoConfig.label} es inválido`;
        }
    }

    return null;
}

function validarClausulaCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    const longitud = valor.trim().length;
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*La cláusula de garantía es obligatoria*');
        error = 'La cláusula de garantía es obligatoria';
    } else if (longitud < 10 || longitud > 200) {
        marcarCampoInvalido($campo, $span, '*La cláusula debe tener entre 10 y 200 caracteres*');
        error = 'La cláusula de garantía debe tener entre 10 y 200 caracteres';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarCategoriaCampo(campoConfig, hiddenSelector) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    let error = null;

    if (!valor) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar una categoría*');
        error = 'Debe seleccionar una categoría';
    } else {
        marcarCampoValido($campo, $span);
        if (hiddenSelector) {
            $(hiddenSelector).val(valor);
        }
    }

    return error;
}

function validarSerialesCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El campo de seriales es obligatorio*');
        error = 'El campo de seriales es obligatorio';
    } else if (!regexSerialesProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Solo letras, números y guiones*');
        error = 'Los seriales solo pueden contener letras, números y guiones (máx. 20 caracteres)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarPrecioCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim().replace(',', '.');
    const numero = parseFloat(valor);
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El precio es obligatorio*');
        error = 'El precio es obligatorio';
    } else if (isNaN(numero) || !regexPrecioProducto.test(valor) || numero <= 0) {
        marcarCampoInvalido($campo, $span, '*Ingrese un precio válido (ej: 10.99)*');
        error = 'Ingrese un precio válido (ej: 10.99)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarCaracteristicasProducto(config, errores = null) {
    const contenedor = $(config.caracteristicasContenedor);
    if (!contenedor.length) return;

    let numerosInvalidos = false;
    let textosInvalidos = false;

    contenedor.find('input[type="number"]').each(function() {
        const $input = $(this);
        const valor = $input.val().trim();
        const numero = parseFloat(valor);
        const $span = $('#s' + $input.attr('id'));

        if (valor === '' || isNaN(numero) || numero < 0) {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Ingrese un valor válido (0 o mayor)*');
            numerosInvalidos = true;
        } else {
            $input.removeClass('is-invalid').addClass('is-valid');
            if ($span.length) $span.text('');
        }
    });

    contenedor.find('input[type="text"]').each(function() {
        const $input = $(this);
        const valor = $input.val().trim();
        const $span = $('#s' + $input.attr('id'));

        if (valor === '') {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Este campo es obligatorio*');
            textosInvalidos = true;
        } else if (!regexCaracteristicasTexto.test(valor)) {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Solo letras, números, espacios, @, punto y guion*');
            textosInvalidos = true;
        } else {
            $input.removeClass('is-invalid').addClass('is-valid');
            if ($span.length) $span.text('');
        }
    });

    if (errores) {
        if (numerosInvalidos && !errores.includes('Hay campos de características con valores numéricos inválidos')) {
            errores.push('Hay campos de características con valores numéricos inválidos');
        }
        if (textosInvalidos && !errores.includes('Los campos de características de texto solo permiten letras, números, espacios, @, punto y guion')) {
            errores.push('Los campos de características de texto solo permiten letras, números, espacios, @, punto y guion');
        }
    }
}

function validarFormularioProducto(config) {
    if (!config) {
        return { valido: true, errores: [] };
    }

    const errores = [];
    const campos = config.campos;

    if (campos.nombre) {
        const error = validarNombreProductoCampo(campos.nombre);
        if (error) errores.push(error);
    }

    if (campos.descripcion) {
        const error = validarDescripcionProductoCampo(campos.descripcion);
        if (error) errores.push(error);
    }

    if (campos.modelo) {
        const error = validarModeloCampo(campos.modelo);
        if (error) errores.push(error);
    }

    if (campos.imagen) {
        const esRequerida = config.tipo === 'registrar'; // Solo requerida en registro
        const error = validarImagenCampo(campos.imagen, esRequerida);
        if (error) errores.push(error);
    }

    if (campos.stockActual) {
        const error = validarStockCampo(campos.stockActual);
        if (error) errores.push(error);
    }

    if (campos.stockMinimo) {
        const error = validarStockCampo(campos.stockMinimo);
        if (error) errores.push(error);
    }

    if (campos.stockMaximo) {
        const error = validarStockCampo(campos.stockMaximo, {
            compare: (valorActual) => {
                const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                if (!isNaN(minimo) && valorActual <= minimo) {
                    return {
                        spanMensaje: '*Debe ser mayor al stock mínimo*',
                        errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                    };
                }
                return null;
            }
        });
        if (error) errores.push(error);
    }

    if (campos.clausula) {
        const error = validarClausulaCampo(campos.clausula);
        if (error) errores.push(error);
    }

    if (campos.categoria) {
        const error = validarCategoriaCampo(campos.categoria, config.tablaCategoriaHidden);
        if (error) errores.push(error);
    }

    if (campos.seriales) {
        const error = validarSerialesCampo(campos.seriales);
        if (error) errores.push(error);
    }

    if (campos.precio) {
        const error = validarPrecioCampo(campos.precio);
        if (error) errores.push(error);
    }

    if (config.caracteristicasContenedor) {
        validarCaracteristicasProducto(config, errores);
    }

    return {
        valido: errores.length === 0,
        errores
    };
}

function configurarValidacionesTiempoRealProducto() {
    Object.values(productoFormConfigs).forEach((config) => {
        if (!config || !$(config.formSelector).length) return;
        const campos = config.campos;

        if (campos.nombre) {
            $(campos.nombre.selector).on('input blur', () => validarNombreProductoCampo(campos.nombre));
        }

        if (campos.descripcion) {
            $(campos.descripcion.selector).on('input blur', () => validarDescripcionProductoCampo(campos.descripcion));
        }

        if (campos.modelo) {
            $(campos.modelo.selector).on('change blur', () => validarModeloCampo(campos.modelo));
        }

        if (campos.imagen) {
            $(campos.imagen.selector).on('change blur', () => validarImagenCampo(campos.imagen));
        }

        if (campos.stockActual) {
            $(campos.stockActual.selector).on('input blur', () => validarStockCampo(campos.stockActual));
        }

        if (campos.stockMinimo) {
            $(campos.stockMinimo.selector).on('input blur', () => {
                validarStockCampo(campos.stockMinimo);
                if (campos.stockMaximo) {
                    validarStockCampo(campos.stockMaximo, {
                        compare: (valorActual) => {
                            const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                            if (!isNaN(minimo) && valorActual <= minimo) {
                                return {
                                    spanMensaje: '*Debe ser mayor al stock mínimo*',
                                    errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                                };
                            }
                            return null;
                        }
                    });
                }
            });
        }

        if (campos.stockMaximo) {
            $(campos.stockMaximo.selector).on('input blur', () => {
                validarStockCampo(campos.stockMaximo, {
                    compare: (valorActual) => {
                        const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                        if (!isNaN(minimo) && valorActual <= minimo) {
                            return {
                                spanMensaje: '*Debe ser mayor al stock mínimo*',
                                errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                            };
                        }
                        return null;
                    }
                });
            });
        }

        if (campos.clausula) {
            $(campos.clausula.selector).on('input blur', () => validarClausulaCampo(campos.clausula));
        }

        if (campos.categoria) {
            $(campos.categoria.selector).on('change blur', () => validarCategoriaCampo(campos.categoria, config.tablaCategoriaHidden));
        }

        if (campos.seriales) {
            $(campos.seriales.selector).on('input blur', () => validarSerialesCampo(campos.seriales));
        }

        if (campos.precio) {
            $(campos.precio.selector).on('input blur', () => validarPrecioCampo(campos.precio));
        }

        if (config.caracteristicasContenedor) {
            $(document).on('input blur', `${config.caracteristicasContenedor} input`, () => validarCaracteristicasProducto(config));
        }
    });
}

function limpiarValidacionesFormulario(config) {
    if (!config) return;
    Object.values(config.campos).forEach((campo) => {
        const { $campo, $span } = obtenerCampoConfig(campo);
        $campo.removeClass('is-valid is-invalid');
        if ($span.length) $span.text('');
    });

    if (config.caracteristicasContenedor) {
        const $contenedor = $(config.caracteristicasContenedor);
        $contenedor.find('input').removeClass('is-valid is-invalid');
        $contenedor.find('.span-value').text('');
    }
}

$(document).ready(function () {
    var $tabla = $('#tablaConsultas');
    var tablaProductos;

    if ($tabla.length) {
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaProductos = $tabla.DataTable({
                language: {
                    url: 'assets/public/js/es-ES.json'
                },
                scrollX: true,
                scrollCollapse: true,
                order: [[0, 'desc']],
                initComplete: function () {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;
                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirProducto').length) return;

                    // Estilos flex para alinear label + buscador + botón
                    $filter.css({
                        display: 'flex',
                        'align-items': 'center',
                        'justify-content': 'flex-end',
                        gap: '10px'
                    });

                    $filter.find('label').css({ 'margin-bottom': '0' });

                    var $btnWrapper = $('<div>', { 'class': 'space-btn-incluir' });
                    var $btn = $('<button>', {
                        id: 'btnIncluirProducto',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Producto'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaProductos = $tabla.DataTable();
        }

        // Filtro de estatus (Todos / habilitado / inhabilitado)
        var estatusColIndex = $tabla.find('thead th').filter(function () {
            return $(this).text().trim().toLowerCase() === 'estatus';
        }).index();
        if (estatusColIndex < 0) estatusColIndex = 8;

        if (!$tabla.data('estatusFilterAdded')) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (!settings.nTable || settings.nTable !== $tabla.get(0)) return true;
                var desired = ($tabla.data('estatusFilterValue') || 'todos').toLowerCase();
                if (desired === 'todos') return true;
                var row = settings.aoData && settings.aoData[dataIndex];
                var cell = row && row.anCells ? row.anCells[estatusColIndex] : null;
                var text = cell ? $(cell).text().trim().toLowerCase() : '';
                return text === desired;
            });
            $tabla.data('estatusFilterAdded', true);
        }

        var $filtroEstatus = $('#filtro-estatus-productos');
        if ($filtroEstatus.length) {
            $filtroEstatus.off('change.estatus').on('change.estatus', function () {
                var val = ($(this).val() || 'todos').toLowerCase();
                $tabla.data('estatusFilterValue', val);
                if (!tablaProductos) {
                    tablaProductos = $tabla.DataTable();
                }
                tablaProductos.draw();
            });

            $tabla.data('estatusFilterValue', ($filtroEstatus.val() || 'todos').toLowerCase());
            if (!tablaProductos) {
                tablaProductos = $tabla.DataTable();
            }
            tablaProductos.draw();
        }
    }

    if (!document.getElementById('productModal')) {
    configurarValidacionesTiempoRealProducto();

    // Warranty clause validation - sin trim() para permitir espacios
    $("#Clausula_garantia").on("keyup blur", function() {
        const value = $(this).val(); // Eliminado trim() para preservar espacios
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sClausula_garantia").text('*La cláusula de garantía es obligatoria*');
        } else if (value.trim().length < 10 || value.trim().length > 200) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sClausula_garantia").text('*La cláusula debe tener entre 10 y 200 caracteres (sin contar espacios al inicio/fin)*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sClausula_garantia").text('');
        }
    });

    // Stock fields validation
    $("#Stock_Actual, #Stock_Minimo, #Stock_Maximo").on("keyup blur", function() {
        const value = $(this).val().trim();
        const id = $(this).attr('id');
        const spanId = 's' + id;
        
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#" + spanId).text('*Campo obligatorio*');
        } else if (!/^\d+$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#" + spanId).text('*Solo números enteros*');
        } else {
            const numValue = parseInt(value);
            if (numValue < 0) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $("#" + spanId).text('*El valor no puede ser negativo*');
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $("#" + spanId).text('');
            }
        }
    });

    // Price field validation
    $("#Precio").on("keyup blur", function() {
        const value = $(this).val().trim().replace(',', '.');
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*El precio es obligatorio*');
        } else if (!/^\d+(\.\d{1,2})?$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*Formato inválido (ej: 10.99)*');
        } else if (parseFloat(value) <= 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*El precio debe ser mayor a 0*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sPrecio").text('');
        }
    });

    // Category validation on change
    $("#Categoria").on("change", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sCategoria").text('*Debe seleccionar una categoría*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sCategoria").text('');
        }
    });

    // Serial number validation
    $("#Seriales").on("keyup blur", function() {
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sSeriales").text('*Campo obligatorio*');
        } else if (!/^[a-zA-Z0-9-]+$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sSeriales").text('*Solo letras, números y guiones*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sSeriales").text('');
        }
    });

    // Product name validation
    $("#nombre_producto").on("keypress", function(e){
        return validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
    });

    $("#nombre_producto").on("keyup", function(){
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#snombre_producto").text('*El nombre del producto es obligatorio*');
        } else if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,40}$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#snombre_producto").text('*Solo letras, de 3 a 40 caracteres*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#snombre_producto").text('');
        }
    });

    // Model validation
    $("#modelo").on("change blur", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#smodelo").text('*Debe seleccionar un modelo*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#smodelo").text('');
        }
    });

    // Image validation
    $("#imagen").on("change blur", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#simagen").text('*Debe seleccionar una imagen*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#simagen").text('');
        }
    });

    // Description validation
    $("#descripcion_producto").on("keypress", function(e){
        return validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
    });

    $("#descripcion_producto").on("keyup", function(){
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sdescripcion_producto").text('*La descripción es obligatoria*');
        } else if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,50}$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sdescripcion_producto").text('*Máximo 50 caracteres*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sdescripcion_producto").text('');
        }
    });

    $("#Stock_Actual").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Actual").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Actual"),
            "*El formato solo permite números*"
        );
    });

    $("#Stock_Maximo").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Maximo").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Maximo"),
            "*El formato solo permite números*"
        );
    });

    $("#Stock_Minimo").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Minimo").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Minimo"),
            "*El formato solo permite números*"
        );
    });

    $("#Clausula_garantia").on("keypress", function(e){
        validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let descripcion = document.getElementById("Clausula_garantia");
    });

    $("#Clausula_garantia").on("keyup", function(){
        validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s]{2,50}$/,
            $(this),
            $("#smodificarClausulaGarantia"),
            "*El formato permite letras y números*"
        );
    });

    $("#Categoria").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#sCategoria"),
            "*Debe seleccionar una categoria*"
        );
    });

    $("#Seriales").on("keypress", function (e) {
        validarkeypress(/^[a-zA-Z0-9\b]*$/, e); 
    });

    $("#Seriales").on("keyup", function () {
        validarkeyup(
            /^[A-Z0-9]{1,20}$/,
            $(this),
            $("#sSeriales"),
            "*El formato solo permite letras y números*"
        );
    });
    $("#Seriales").on("input", function () {
        const limpio = this.value
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
        this.value = limpio;
    });

    $("#Precio").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Precio").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,10}$/,
            $(this),
            $("#sPrecio"),
            "*El formato solo permite números*"
        );
    });

    // Abrir modal de registro (botón Incluir Producto dentro del DataTable)
    $(document).on('click', '#btnIncluirProducto', function() {
        const config = productoFormConfigs.registrar;
        if (config) {
            $('#incluirProductoForm')[0].reset();
            
            validarFormularioProducto(config);
            limpiarValidacionesFormulario(config);
        }
        $('#registrarProductoModal').modal('show');
    });

    $(document).on('click', '#registrarProductoModal .close, #registrarProductoModal [data-dismiss="modal"]', function() {
        $('#registrarProductoModal').modal('hide');
    });

    // Al abrir el modal de modificar, carga los datos del producto y sus características
    $(document).on('click', '.btn-modificar', function () {
        // 1. Resetear el formulario primero
        $('#modificarProductoForm')[0].reset();
    
    // 2. Datos generales
        const $this = $(this);
        const dataset = $this.data();
    
        // Establecer los valores de los campos
        $('#modificarIdProducto').val(dataset.id);
        $('#modificarNombreProducto').val(dataset.nombre);
        $('#modificarDescripcionProducto').val(dataset.descripcion);
        $('#modificarMarca').val(dataset.marca);
        $('#modificarStockActual').val(dataset.stockactual);
        $('#modificarStockMaximo').val(dataset.stockmaximo);
        $('#modificarStockMinimo').val(dataset.stockminimo);
        $('#modificarClausulaGarantia').val(dataset.clausula);
        $('#modificarSeriales').val(dataset.seriales);
        $('#modificarPrecio').val(dataset.precio);
        
    // Establecer el valor del modelo usando el ID del modelo
    setTimeout(() => {
        const $selectModelo = $('#modificarModelo');
        const modeloId = dataset.modeloid; // Usamos el ID del modelo del data-attribute
        
        if (modeloId) {
            // Buscar la opción que coincida con el ID del modelo
            $selectModelo.val(modeloId).trigger('change');
        }
    }, 100);

    // 3. Categoría y tabla dinámica
    const tablaCategoria = dataset.tabla_categoria || dataset.categoria;
    $('#modificarCategoria').val(tablaCategoria).trigger('change');
    $('#modificar_tabla_categoria').val(tablaCategoria);

    // 4. Imagen
    const preview = document.getElementById('modificarImagenPreview');
    if (dataset.imagen) {
        preview.src = dataset.imagen;
        preview.style.display = 'block';
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }

    // 5. Cargar características dinámicas
    const categoriaObj = categoriasDinamicas.find(cat => cat.tabla === tablaCategoria);
    if (categoriaObj) {
        // Esperar un momento para que se generen los campos dinámicos
        setTimeout(() => {
            categoriaObj.caracteristicas.forEach(carac => {
                // Procesar el nombre para que coincida con el dataset (camelCase)
                const camelName = carac.nombre.replace(/_([a-z])/g, g => g[1].toUpperCase());
                const valor = dataset[camelName];
                const $campo = $(`#modificar_${carac.nombre}`);
                
                if ($campo.length && valor !== undefined) {
                    // Manejar campos de voltaje específicamente
                    if (carac.nombre.toLowerCase().includes('voltaje')) {
                        // Asegurarse de que el valor sea numérico y tenga el formato correcto
                        const valorNumerico = parseFloat(valor);
                        if (!isNaN(valorNumerico)) {
                            $campo.val(valorNumerico);
                        }
                    } else {
                        $campo.val(valor);
                    }
                }
            });
        }, 200);
    }
    
    // Mostrar el modal
    $('#modificarProductoModal').modal('show');
});

function verificarPermisosEnTiempoRealProductos() {
    var datos = new FormData();
    datos.append('accion', 'permisos_tiempo_real');
    enviarAjax(datos, function(permisos) {
        // Si no tiene permiso de consultar
        if (!permisos.consultar) {
            $('#tablaConsultas').hide();
            $('.space-btn-incluir').hide();
            if ($('#mensaje-permiso').length === 0) {
                $('.contenedor-tabla').prepend('<div id="mensaje-permiso" style="color:red; text-align:center; margin:20px 0;">No tiene permiso para consultar los registros.</div>');
            }
            return;
        } else {
            $('#tablaConsultas').show();
            $('.space-btn-incluir').show();
            $('#mensaje-permiso').remove();
        }

        // Mostrar/ocultar botón de incluir
        if (permisos.incluir) {
            $('#btnIncluirProducto').show();
        } else {
            $('#btnIncluirProducto').hide();
        }

        // Mostrar/ocultar botones de modificar/eliminar
        $('.btn-modificar').each(function() {
            if (permisos.modificar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $('.btn-eliminar').each(function() {
            if (permisos.eliminar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Ocultar columna Acciones si ambos permisos son falsos
        if (!permisos.modificar && !permisos.eliminar) {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').hide();
        } else {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
        }
    });
}

// Llama la función al cargar la página y luego cada 10 segundos
$(document).ready(function() {
    verificarPermisosEnTiempoRealProductos();
    setInterval(verificarPermisosEnTiempoRealProductos, 10000); // 10 segundos
});
    
$('#modificarProductoForm').on('submit', function(e) {
    e.preventDefault();
    
    const config = productoFormConfigs.modificar;
    const resultado = validarFormularioProducto(config);

    if (!resultado.valido) {
        const $primerError = $(`${config.formSelector} .is-invalid`).first();
        if ($primerError.length) {
            $('html, body').animate({ scrollTop: $primerError.offset().top - 120 }, 500);
            $primerError.focus();
        }

        Swal.fire({
            icon: 'error',
            title: 'Error en el formulario',
            html: resultado.errores.join('<br>'),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    $(config.tablaCategoriaHidden).val($(config.campos.categoria.selector).val());

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Preparar datos del formulario
    var formData = new FormData(this);
    formData.append('accion', 'modificar');


    // Enviar petición AJAX
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor (modificación):', response);
            if (response && response.status === 'success') {
                Swal.fire({
                    title: 'Éxito',
                    text: 'Producto ingresado exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                console.error('Error al registrar producto:', response && response.message);
                console.log('Datos completos recibidos (modificación):', response);
                console.log('¿Tiene errors? (modificación)', response && response.errors);
                // Mostrar errores específicos si están disponibles
                if (response && response.errors && Object.keys(response.errors).length > 0) {
                    const erroresHtml = Object.entries(response.errors)
                        .map(([campo, mensaje]) => `<strong>${campo}:</strong> ${mensaje}`)
                        .join('<br>');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en los datos del producto',
                        html: erroresHtml,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (response && response.message) || 'No se pudo registrar el producto'
                    });
                }
            }
        },
error: function(xhr, status, error) {
    console.error("Error AJAX:", status, error, xhr.responseText);
}
    });
});
$('#incluirProductoForm').on('submit', function(e) {
    console.log('🚀 Formulario de registro enviado');
    e.preventDefault();
    
    const config = productoFormConfigs.registrar;
    console.log('📋 Configuración del formulario:', config);
    const resultado = validarFormularioProducto(config);

    if (!resultado.valido) {
        const $primerError = $(`${config.formSelector} .is-invalid`).first();
        if ($primerError.length) {
            $('html, body').animate({ scrollTop: $primerError.offset().top - 120 }, 500);
            $primerError.focus();
        }

        Swal.fire({
            icon: 'error',
            title: 'Error en el formulario',
            html: resultado.errores.join('<br>'),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    $(config.tablaCategoriaHidden).val($(config.campos.categoria.selector).val());

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Preparar datos del formulario
    var formData = new FormData(this);
    formData.append('accion', 'ingresar');
    
    // Depuración: mostrar datos que se enviarán
    console.log(' Enviando datos al servidor:');
    for (let pair of formData.entries()) {
        console.log(`  ${pair[0]}: ${pair[1]}`);
    }

    // Enviar petición AJAX
    console.log(' Iniciando petición AJAX...');
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor (registro):', response);
            if (response && response.status === 'success') {
                Swal.fire({
                    title: 'Éxito',
                    text: 'Producto ingresado exitosamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                console.error('Error al registrar producto:', response && response.message);
                console.log('Datos completos recibidos (registro):', response);
                console.log('¿Tiene errors? (registro)', response && response.errors);
                // Mostrar errores específicos si están disponibles
                if (response && response.errors && Object.keys(response.errors).length > 0) {
                    const erroresHtml = Object.entries(response.errors)
                        .map(([campo, mensaje]) => `<strong>${campo}:</strong> ${mensaje}`)
                        .join('<br>');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en los datos del producto',
                        html: erroresHtml,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (response && response.message) || 'No se pudo registrar el producto'
                    });
                }
            }
        },
error: function(xhr, status, error) {
    console.error("Error AJAX:", status, error, xhr.responseText);
}
    });
});
/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
document.getElementById('imagen').addEventListener('change', function (event) {
  const input = event.target;
  const preview = document.getElementById('imagenPreview');

  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };

    reader.readAsDataURL(input.files[0]);
  } else {
    preview.src = '#';
    preview.style.display = 'none';
  }
});

$('#Precio').on('input', function() {
    let precioInput = $(this).val().trim().replace(',', '.');
    const precioRegex = /^\d+(\.\d{0,2})?$/;
    if (!precioRegex.test(precioInput) && precioInput !== "") {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});
    // Cerrar modal de modificación
    $(document).on('click', '#modificarProductoModal .close', function() {
        $('#modificarProductoModal').modal('hide');
    });
    
    // Delegación para el despliegue de opciones (modificar/eliminar)
    $('#tablaConsultas').on('click', '.vertical', function(e) {
        e.stopPropagation(); // Prevenir cierre inmediato

        // Cerrar todos los menús primero
        $('.desplegable').not($(this).next('.desplegable')).hide();

        // Alternar el menú actual
        const menuActual = $(this).next('.desplegable');
        menuActual.toggle();
    });

    // Cerrar el menú si se hace clic fuera
    $(document).on('click', function() {
        $('.desplegable').hide();
    });
    }
});

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
    function enviarAjax(datos, callback) {
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            success: function (respuesta) {
                if (typeof respuesta === "string") {
                    respuesta = JSON.parse(respuesta);
                }
                if(callback) callback(respuesta);
            },
            error: function () {
                Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
            }
        });
    }
function cambiarEstatus(idUsuario) {
    const span = $(`span[onclick*="cambiarEstatus(${idUsuario}"]`);
    const estatusActual = span.text().trim().toLowerCase();
    const nuevoEstatus = estatusActual === 'habilitado' ? 'inhabilitado' : 'habilitado';
    
    // Feedback visual inmediato
    span.addClass('cambiando');
    
    $.ajax({
        url: '',
        type: 'POST',
        dataType: 'json',
        data: {
            accion: 'cambiar_estatus',
            id_producto: idUsuario,
            nuevo_estatus: nuevoEstatus
        },
        success: function(data) {
            span.removeClass('cambiando');
            
            if (data.status === 'success') {
                span.text(nuevoEstatus);
                span.removeClass('habilitado inhabilitado').addClass(nuevoEstatus);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Estatus actualizado!',
                    showConfirmButton: false,
                    timer: 2000
                });
                var table = $('#tablaConsultas').DataTable();
                table.row(span.closest('tr')).invalidate();
                table.draw(false);
            } else {
                // Revertir visualmente
                span.text(estatusActual);
                span.removeClass('habilitado inhabilitado').addClass(estatusActual);
                Swal.fire('Error', data.message || 'Error al cambiar el estatus', 'error');
            }
        },
        error: function(xhr, status, error) {
            span.removeClass('cambiando');
            // Revertir visualmente
            span.text(estatusActual);
            span.removeClass('habilitado inhabilitado').addClass(estatusActual);
            Swal.fire('Error', 'Error en la conexión', 'error');
        }
    });
}
function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalDetallesProducto');
    const cerrar = document.getElementById('cerrarModalDetalles');

    if (modal && cerrar) {
        document.querySelectorAll('.btn-detalle').forEach(btn => {
            btn.addEventListener('click', function () {
                const imagen = this.dataset.imagendtl;
                const contenedorImagen = document.getElementById('detalle-imagen');
                contenedorImagen.innerHTML = imagen
                    ? `<img src="${imagen}" alt="Imagen del Producto" style="width: 150px; height: 150px;">`
                    : 'Sin imagen';
                document.getElementById('detalle-id').textContent = this.dataset.iddtl;
                document.getElementById('detalle-nombre').textContent = this.dataset.nombredtl;
                document.getElementById('detalle-modelo').textContent = this.dataset.modelodtl;
                document.getElementById('detalle-marca').textContent = this.dataset.marcadtl;
                document.getElementById('detalle-descripcion').textContent = this.dataset.descripciondtl;
                document.getElementById('detalle-stockactual').textContent = this.dataset.stockactualdtl;
                document.getElementById('detalle-stockmaximo').textContent = this.dataset.stockmaximodtl;
                document.getElementById('detalle-stockminimo').textContent = this.dataset.stockminimodtl;
                document.getElementById('detalle-serial').textContent = this.dataset.serialdtl;
                document.getElementById('detalle-clausula').textContent = this.dataset.clausuladtl;
                document.getElementById('detalle-categoria').textContent = this.dataset.categoriadtl;
                document.getElementById('detalle-precio').textContent = this.dataset.preciodtl;
                document.getElementById('detalle-estatus').textContent = this.dataset.estatusdtl;
                modal.classList.add('mostrar');
            });
        });

        cerrar.addEventListener('click', () => {
            modal.classList.remove('mostrar');
        });

        window.addEventListener('click', event => {
            if (event.target === modal) {
                modal.classList.remove('mostrar');
            }
        });
    }

    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
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
        $.get('assets/public/ayuda/producto.php')
            .done(function(html) {
                // Solo agregar modal si no existe
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                }

                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            if (typeof inicializarModalAyudaUsuario === 'function') {
                                modalAyudaInstance = inicializarModalAyudaUsuario();
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) modalAyudaInstance.goToSlide(slideIndex);
                                    }, 300);
                                }
                                modalAyudaInstance.openModal();
                            } else {
                                console.error('La función inicializarModalAyudaUsuario no está disponible');
                            }
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else if (typeof inicializarModalAyudaUsuario === 'function') {
                    modalAyudaInstance = inicializarModalAyudaUsuario();
                    if (contexto) {
                        setTimeout(() => {
                            const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                            if (slideIndex !== undefined) modalAyudaInstance.goToSlide(slideIndex);
                        }, 300);
                    }
                    modalAyudaInstance.openModal();
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

    $('.btn-ayuda').off('click.ayuda-modal').on('click.ayuda-modal', function(event) {
        event.preventDefault();
        cargarYMostrarModalAyuda();
    });

    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(event) {
        event.preventDefault();
        cargarYMostrarModalAyuda($(this).data('contexto'));
    });
});
// Evento para eliminar producto
$(document).on('click', '.btn-eliminar', function (e) {
    e.preventDefault();
    Swal.fire({
        title: '¿Está seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo!'
    }).then((result) => {
        if (result.isConfirmed) {
            var id_producto = $(this).data('id');
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_producto', id_producto);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: datos,
                contentType: false,
                processData: false,
                cache: false,
                success: function(respuesta) {
                    if (typeof respuesta === "string") {
                        respuesta = JSON.parse(respuesta);
                    }
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminado!',
                            'El producto ha sido eliminado.',
                            'success'
                        );
                        eliminarFilaProducto(id_producto);
                    } else {
                        // Mostrar mensaje específico si hay error
                        Swal.fire('Error', respuesta.message || 'Error al eliminar el producto', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error en la conexión', 'error');
                }
            });
        }
    });
});

// Función para eliminar la fila de la tabla
function eliminarFilaProducto(id_producto) {
    var table = $('#tablaConsultas').DataTable();
    var row = table.row(`tr[data-id="${id_producto}"]`);
    if (row) {
        row.remove().draw(false);
    }
}

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
(function initializeVistaNewProducts() {
    function initialize() {
        const productModal = document.getElementById('productModal');
        if (!productModal) return;

        const productForm = document.getElementById('productForm');
        const categoriasDinamicasProducto = JSON.parse(productModal.dataset.categories || '[]');
        let productosDisponibles = JSON.parse(productModal.dataset.products || '[]');
        const productCategory = document.getElementById('productCategory');
        const productCategoryTable = document.getElementById('productCategoryTable');
        const productCategoryFields = document.getElementById('productCategoryFields');
        const productDetailsModal = document.getElementById('productDetailsModal');
        const productImageInput = document.getElementById('productImage');
        const productImagePreview = document.getElementById('productImagePreview');
        const productImagePreviewPlaceholder = document.getElementById('productImagePreviewPlaceholder');
        let temporaryProductImageUrl = null;
        const allowedModelValues = new Set(Array.from(productForm.querySelector('#modelo').options, option => option.value));
        const allowedCategoryValues = new Set(categoriasDinamicasProducto.map(category => category.tabla));
        const productFormConfig = {
            tipo: 'registrar',
            formSelector: '#productForm',
            campos: {
                nombre: { selector: '#nombre_producto', span: '#snombre_producto', label: 'nombre del producto' },
                descripcion: { selector: '#descripcion_producto', span: '#sdescripcion_producto', label: 'descripción del producto' },
                modelo: { selector: '#modelo', span: '#smodelo', label: 'modelo' },
                imagen: { selector: '#productImage', span: '#simagen', label: 'imagen del producto' },
                stockActual: { selector: '#Stock_Actual', span: '#sStock_Actual', label: 'stock actual' },
                stockMaximo: { selector: '#Stock_Maximo', span: '#sStock_Maximo', label: 'stock máximo' },
                stockMinimo: { selector: '#Stock_Minimo', span: '#sStock_Minimo', label: 'stock mínimo' },
                clausula: { selector: '#Clausula_garantia', span: '#sClausula_garantia', label: 'cláusula de garantía' },
                categoria: { selector: '#productCategory', span: '#sCategoria', label: 'categoría' },
                seriales: { selector: '#Seriales', span: '#sSeriales', label: 'seriales' },
                precio: { selector: '#Precio', span: '#sPrecio', label: 'precio' }
            },
            caracteristicasContenedor: '#productCategoryFields',
            tablaCategoriaHidden: '#productCategoryTable'
        };

        protegerSelects(['modelo', 'productCategory']);

        function validateProductForm() {
            if (!allowedModelValues.has(productForm.querySelector('#modelo').value)) {
                Swal.fire({ icon: 'error', title: 'Modelo no válido', text: 'Selecciona un modelo disponible.' });
                return false;
            }
            if (!allowedCategoryValues.has(productCategory.value)) {
                Swal.fire({ icon: 'error', title: 'Categoría no válida', text: 'Selecciona una categoría disponible.' });
                return false;
            }
            const result = validarFormularioProducto(productFormConfig);
            if (!result.valido) {
                const firstInvalid = productForm.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa los datos del producto',
                    text: result.errores.join('. ')
                });
            }
            return result.valido;
        }

        const textInputRules = [
            ['#nombre_producto', /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, validarNombreProductoCampo, productFormConfig.campos.nombre],
            ['#descripcion_producto', /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s\b]*$/, validarDescripcionProductoCampo, productFormConfig.campos.descripcion],
            ['#Clausula_garantia', /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s\b]*$/, validarClausulaCampo, productFormConfig.campos.clausula],
            ['#Seriales', /^[a-zA-Z0-9-]*$/, validarSerialesCampo, productFormConfig.campos.seriales]
        ];
        textInputRules.forEach(([selector, keypressPattern, validate, config]) => {
            const input = productForm.querySelector(selector);
            input.addEventListener('keypress', event => validarkeypress(keypressPattern, event));
            input.addEventListener('keyup', () => validate(config));
            input.addEventListener('blur', () => validate(config));
        });

        [
            ['#Stock_Actual', productFormConfig.campos.stockActual],
            ['#Stock_Maximo', productFormConfig.campos.stockMaximo],
            ['#Stock_Minimo', productFormConfig.campos.stockMinimo],
            ['#Precio', productFormConfig.campos.precio]
        ].forEach(([selector, config]) => {
            const input = productForm.querySelector(selector);
            input.addEventListener('keypress', event => validarkeypress(/^[0-9\b]*$/, event));
            input.addEventListener('keyup', () => {
                if (config === productFormConfig.campos.precio) {
                    validarPrecioCampo(config);
                } else {
                    validarStockCampo(config);
                    if (config === productFormConfig.campos.stockMinimo) {
                        validarStockCampo(productFormConfig.campos.stockMaximo, {
                            compare: maximo => maximo <= Number(input.value)
                                ? { spanMensaje: '*Debe ser mayor al stock mínimo*', errorMensaje: 'El stock máximo debe ser mayor al stock mínimo' }
                                : null
                        });
                    }
                }
            });
            input.addEventListener('blur', () => {
                if (config === productFormConfig.campos.precio) validarPrecioCampo(config);
                else validarStockCampo(config);
            });
        });

        productForm.querySelector('#modelo').addEventListener('change', () => validarModeloCampo(productFormConfig.campos.modelo));
        productCategory.addEventListener('change', () => validarCategoriaCampo(productFormConfig.campos.categoria, productFormConfig.tablaCategoriaHidden));
        productImageInput.addEventListener('change', () => validarImagenCampo(productFormConfig.campos.imagen, productFormConfig.tipo === 'registrar'));
        productForm.addEventListener('input', event => {
            if (event.target.closest('#productCategoryFields')) validarCaracteristicasProducto(productFormConfig);
        });

        function showProductImagePreview(source) {
            if (!source) {
                productImagePreview.removeAttribute('src');
                productImagePreview.hidden = true;
                productImagePreviewPlaceholder.hidden = false;
                return;
            }

            productImagePreview.src = source;
            productImagePreview.hidden = false;
            productImagePreviewPlaceholder.hidden = true;
        }

        function clearTemporaryProductImageUrl() {
            if (temporaryProductImageUrl) {
                URL.revokeObjectURL(temporaryProductImageUrl);
                temporaryProductImageUrl = null;
            }
        }

        productImageInput.addEventListener('change', function () {
            clearTemporaryProductImageUrl();
            const selectedImage = this.files && this.files[0];
            if (!selectedImage) {
                return;
            }
            temporaryProductImageUrl = URL.createObjectURL(selectedImage);
            showProductImagePreview(temporaryProductImageUrl);
        });

        function renderProductCategoryFields(tabla, valores = {}) {
            const categoria = categoriasDinamicasProducto.find(item => item.tabla === tabla);
            productCategoryFields.replaceChildren();
            productCategoryTable.value = tabla || '';

            if (!categoria) {
                return;
            }

            categoria.caracteristicas.forEach(caracteristica => {
                const group = document.createElement('div');
                group.className = 'form-group';

                const label = document.createElement('label');
                label.htmlFor = `productCaracteristica_${caracteristica.nombre}`;
                label.textContent = caracteristica.nombre.replace(/_/g, ' ');

                const input = document.createElement('input');
                input.id = label.htmlFor;
                input.name = `carac[${caracteristica.nombre}]`;
                input.type = ['int', 'float'].includes(caracteristica.tipo) ? 'number' : 'text';
                input.className = 'input-textnum';
                input.required = true;
                if (input.type === 'number') {
                    input.min = '0';
                    if (caracteristica.tipo === 'float') input.step = 'any';
                } else {
                    input.maxLength = Number(caracteristica.max) || 255;
                }
                input.addEventListener('keypress', event => {
                    const allowed = input.type === 'number' ? /^[0-9\b]*$/ : /^[a-zA-Z0-9@.\-\sÁÉÍÓÚáéíóúñÑ]*$/;
                    validarkeypress(allowed, event);
                });
                input.addEventListener('keyup', () => validarCaracteristicasProducto(productFormConfig));
                if (Object.prototype.hasOwnProperty.call(valores, caracteristica.nombre)) {
                    input.value = valores[caracteristica.nombre] ?? '';
                }

                group.append(label, input);
                productCategoryFields.appendChild(group);
            });
        }

        productCategory.addEventListener('change', function () {
            renderProductCategoryFields(this.value);
        });

        function openModal(type, productId = null) {
            clearTemporaryProductImageUrl();
            productForm.reset();
            productFormConfig.tipo = type === 'editar' ? 'modificar' : 'registrar';
            document.getElementById('productId').value = '';
            document.getElementById('productAction').value = 'ingresar';
            productCategoryFields.replaceChildren();
            productCategoryTable.value = '';
            showProductImagePreview('');
            limpiarValidacionesFormulario(productFormConfig);

            if (type === 'registrar') {
                document.getElementById('modalTitle').textContent = 'Nuevo Producto';
                productImageInput.required = true;
                document.querySelector('#productModal .btn-save').textContent = 'Registrar';
            } else if (type === 'editar') {
                const producto = productosDisponibles.find(item => Number(item.id_producto) === Number(productId));
                if (!producto) {
                    Swal.fire({ icon: 'error', title: 'Producto no encontrado', text: 'Actualiza la página e inténtalo de nuevo.' });
                    return;
                }

                document.getElementById('modalTitle').textContent = 'Modificar Producto';
                productImageInput.required = false;
                document.querySelector('#productModal .btn-save').textContent = 'Guardar cambios';
                document.getElementById('productId').value = producto.id_producto;
                document.getElementById('productAction').value = 'modificar';
                document.getElementById('nombre_producto').value = producto.nombre_producto || '';
                document.getElementById('descripcion_producto').value = producto.descripcion_producto || '';
                document.getElementById('modelo').value = producto.id_modelo || '';
                document.getElementById('Precio').value = producto.precio || '';
                document.getElementById('Stock_Actual').value = producto.stock_actual ?? producto.stock ?? 0;
                document.getElementById('Stock_Minimo').value = producto.stock_minimo ?? 0;
                document.getElementById('Stock_Maximo').value = producto.stock_maximo ?? 0;
                document.getElementById('Clausula_garantia').value = producto.clausula_garantia || '';
                document.getElementById('Seriales').value = producto.serial || '';
                showProductImagePreview(producto.imagen || '');

                const normalizarCategoria = value => String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[\s_-]+/g, '');
                const categoria = categoriasDinamicasProducto.find(item => normalizarCategoria(item.nombre_categoria) === normalizarCategoria(producto.nombre_categoria));
                productCategory.value = categoria ? categoria.tabla : '';
                productForm.querySelector('#modelo').dispatchEvent(new Event('change'));
                productCategory.dispatchEvent(new Event('change'));
                renderProductCategoryFields(productCategory.value, producto.caracteristicas || {});
            }
            productModal.style.display = 'block';
        }

        function closeModal() {
            productModal.style.display = 'none';
            clearTemporaryProductImageUrl();
            productForm.reset();
            productCategoryFields.replaceChildren();
            productCategoryTable.value = '';
            showProductImagePreview('');
        }

        async function enviarAccionProducto(datos) {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin'
            });
            const responseText = await response.text();
            let resultado;
            try {
                resultado = JSON.parse(responseText);
            } catch (error) {
                throw new Error('El servidor devolvió una respuesta no válida. Inténtalo nuevamente.');
            }
            if (!response.ok || resultado.status === 'error') {
                throw new Error(resultado.message || resultado.mensaje || 'No se pudo completar la operación.');
            }
            return resultado;
        }

        function readImageAsDataUrl(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(new Error('No se pudo leer la imagen seleccionada.'));
                reader.readAsDataURL(file);
            });
        }

        function makeProductFromForm(existingProduct, idProducto, result) {
            const modelSelect = productForm.querySelector('#modelo');
            const modelOption = modelSelect.options[modelSelect.selectedIndex];
            const modelParts = modelOption.textContent.match(/^(.*?)\s+\((.*)\)$/);
            const categoryOption = productCategory.options[productCategory.selectedIndex];
            const product = {
                ...(existingProduct || {}),
                id_producto: idProducto,
                nombre_producto: productForm.querySelector('#nombre_producto').value.trim(),
                descripcion_producto: productForm.querySelector('#descripcion_producto').value.trim(),
                id_modelo: modelSelect.value,
                nombre_modelo: modelParts ? modelParts[1] : modelOption.textContent,
                nombre_marca: modelParts ? modelParts[2] : '',
                stock_actual: Number(productForm.querySelector('#Stock_Actual').value),
                stock: Number(productForm.querySelector('#Stock_Actual').value),
                stock_maximo: Number(productForm.querySelector('#Stock_Maximo').value),
                stock_minimo: Number(productForm.querySelector('#Stock_Minimo').value),
                clausula_garantia: productForm.querySelector('#Clausula_garantia').value,
                serial: productForm.querySelector('#Seriales').value.trim(),
                precio: Number(productForm.querySelector('#Precio').value),
                nombre_categoria: (categoriasDinamicasProducto.find(item => item.tabla === productCategory.value) || {}).nombre_categoria || categoryOption.textContent,
                caracteristicas: Object.fromEntries(Array.from(productCategoryFields.querySelectorAll('input')).map(input => [
                    input.name.match(/^carac\[(.+)\]$/)?.[1] || input.name,
                    input.value
                ])),
                estado: (existingProduct && existingProduct.estado) || 'habilitado'
            };
            const selectedImage = productImageInput.files && productImageInput.files[0];
            product.imagen = result.imagen || (existingProduct && existingProduct.imagen) || '';
            return selectedImage ? readImageAsDataUrl(selectedImage).then(image => ({ ...product, imagen: image })) : Promise.resolve(product);
        }

        function createProductAction(action, id) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `btn-action btn-${action}`;
            button.dataset.productAction = action;
            button.dataset.productId = String(id);
            const titles = { edit: 'Modificar Producto', delete: 'Eliminar Producto', view: 'Ver Producto' };
            const icons = { edit: 'fa-edit', delete: 'fa-trash', view: 'fa-eye' };
            button.title = titles[action];
            button.setAttribute('aria-label', titles[action]);
            const icon = document.createElement('i');
            icon.className = `fas ${icons[action]}`;
            button.appendChild(icon);
            return button;
        }

        function updateProductCard(product) {
            const grid = document.querySelector('.products-grid');
            const id = String(product.id_producto);
            let card = grid.querySelector(`.product-card[data-product-id="${id}"]`);
            if (!card) {
                card = document.createElement('div');
                card.className = 'product-card';
                card.dataset.productId = id;
                const imageFrame = document.createElement('div');
                imageFrame.className = 'product-image';
                const info = document.createElement('div');
                info.className = 'product-info';
                const name = document.createElement('h3');
                const category = document.createElement('p');
                category.className = 'product-category';
                const brand = document.createElement('p');
                brand.className = 'product-brand';
                const price = document.createElement('div');
                price.className = 'product-price';
                const stock = document.createElement('div');
                stock.className = 'product-stock';
                stock.innerHTML = '<span class="stock-label">Stock:</span> <span class="stock-value"></span>';
                const actions = document.createElement('div');
                actions.className = 'product-actions';
                if (productModal.dataset.canModify === 'true') actions.append(createProductAction('edit', id));
                if (productModal.dataset.canDelete === 'true') actions.append(createProductAction('delete', id));
                actions.append(createProductAction('view', id));
                info.append(name, category, brand, price, stock, actions);
                card.append(imageFrame, info);
            }

            card.dataset.productId = id;
            const imageFrame = card.querySelector('.product-image');
            imageFrame.replaceChildren();
            if (product.imagen) {
                const image = document.createElement('img');
                image.src = product.imagen;
                image.alt = product.nombre_producto || 'Producto';
                image.className = 'product-img';
                imageFrame.appendChild(image);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-placeholder';
                placeholder.innerHTML = '<i class="fas fa-box"></i>';
                imageFrame.appendChild(placeholder);
            }

            const stockActual = Number(product.stock_actual ?? product.stock ?? 0);
            const stockMinimo = Number(product.stock_minimo || 0);
            if (stockActual <= stockMinimo || stockActual < 10) {
                const badge = document.createElement('span');
                badge.className = `product-badge ${stockActual <= stockMinimo ? 'low-stock' : 'critical'}`;
                badge.textContent = stockActual <= stockMinimo ? 'Stock Bajo' : 'Crítico';
                imageFrame.appendChild(badge);
            }

            card.querySelector('h3').textContent = product.nombre_producto || '';
            card.querySelector('.product-category').textContent = product.nombre_categoria || 'Sin categoría';
            card.querySelector('.product-brand').textContent = product.nombre_marca || 'Sin marca';
            card.querySelector('.product-price').textContent = `$${Number(product.precio || 0).toFixed(2)}`;
            const stockValue = card.querySelector('.stock-value');
            stockValue.textContent = `${stockActual} unidades`;
            stockValue.className = `stock-value ${stockActual <= stockMinimo ? 'low' : stockActual < 10 ? 'critical' : stockActual > 30 ? 'high' : 'medium'}`;
            card.querySelectorAll('[data-product-id]').forEach(button => { button.dataset.productId = id; });
            if (!card.isConnected) {
                grid.querySelector('.no-products')?.remove();
                grid.appendChild(card);
            }
        }

        function updateAnalyticsProductOptions() {
            const selectedValue = analyticsProduct.value;
            analyticsProduct.replaceChildren(new Option('Seleccionar producto', ''));
            productosDisponibles.forEach(product => {
                analyticsProduct.add(new Option(product.nombre_producto || 'Producto', String(product.id_producto)));
            });
            analyticsProduct.value = productosDisponibles.some(product => String(product.id_producto) === selectedValue) ? selectedValue : '';
        }

        function updateStockSummary() {
            const total = productosDisponibles.length;
            const low = productosDisponibles.filter(product => Number(product.stock_actual ?? product.stock ?? 0) <= Number(product.stock_minimo || 0)).length;
            const stable = productosDisponibles.filter(product => {
                const stock = Number(product.stock_actual ?? product.stock ?? 0);
                return stock > Number(product.stock_minimo || 0) && stock < Number(product.stock_maximo || 0);
            }).length;
            const exceeded = productosDisponibles.filter(product => Number(product.stock_actual ?? product.stock ?? 0) >= Number(product.stock_maximo || 0)).length;
            const metrics = [low, stable, exceeded];
            const cards = document.querySelectorAll('.summary-card');
            metrics.forEach((value, index) => {
                const card = cards[index];
                if (!card) return;
                card.querySelector('.card-value').textContent = String(value);
                const percentage = total ? Math.round(value / total * 100) : 0;
                card.querySelector('.percentage').textContent = `${percentage}%`;
                card.querySelector('.circle').setAttribute('stroke-dasharray', `${percentage}, 100`);
            });
        }

        function updateCategorySummary() {
            const normalize = value => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[\s_-]+/g, '');
            document.querySelectorAll('.category-card').forEach(card => {
                const category = normalize(card.querySelector('h3').textContent);
                const count = productosDisponibles.filter(product => normalize(product.nombre_categoria) === category).length;
                card.querySelector('p').textContent = `${count} productos`;
            });
        }

        productForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!validateProductForm() || !productForm.reportValidity()) return;

            productCategoryTable.value = productCategory.value;
            const datos = new FormData(productForm);
            datos.set('tabla_categoria', productCategory.value);
            const submitButton = document.querySelector('#productModal .btn-save');
            const action = document.getElementById('productAction').value;
            const editing = action === 'modificar';
            const productId = document.getElementById('productId').value;
            const existingProduct = productosDisponibles.find(product => String(product.id_producto) === String(productId));
            if (submitButton) submitButton.disabled = true;
            try {
                const productDraft = await makeProductFromForm(existingProduct, productId, {});
                const resultado = await enviarAccionProducto(datos);
                const savedId = editing ? productId : resultado.id_producto;
                const savedProduct = {
                    ...productDraft,
                    id_producto: savedId,
                    imagen: resultado.imagen || productDraft.imagen
                };
                const existingIndex = productosDisponibles.findIndex(product => String(product.id_producto) === String(savedId));
                if (existingIndex < 0) productosDisponibles.unshift(savedProduct);
                else productosDisponibles[existingIndex] = savedProduct;
                productModal.dataset.products = JSON.stringify(productosDisponibles);
                updateProductCard(savedProduct);
                updateAnalyticsProductOptions();
                updateStockSummary();
                updateCategorySummary();
                closeModal();
                await Swal.fire({
                    icon: 'success',
                    title: editing ? 'Producto modificado' : 'Producto registrado',
                    text: resultado.mensaje || resultado.message || 'Los cambios se guardaron correctamente.'
                });
                loadAnalytics();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: error.message });
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        });

        async function deleteProduct(productId) {
            const confirmacion = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar producto?',
                text: 'La operación será validada por el sistema antes de aplicarse.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmacion.isConfirmed) return;

            const datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_producto', String(productId));
            try {
                const resultado = await enviarAccionProducto(datos);
                productosDisponibles = productosDisponibles.filter(product => String(product.id_producto) !== String(productId));
                productModal.dataset.products = JSON.stringify(productosDisponibles);
                Array.from(document.querySelectorAll('.product-card')).find(card => card.dataset.productId === String(productId))?.remove();
                if (!productosDisponibles.length) {
                    const empty = document.createElement('div');
                    empty.className = 'no-products';
                    empty.innerHTML = '<i class="fas fa-box-open"></i><p>No hay productos registrados en el sistema</p>';
                    document.querySelector('.products-grid').appendChild(empty);
                }
                updateAnalyticsProductOptions();
                updateStockSummary();
                updateCategorySummary();
                await Swal.fire({ icon: 'success', title: 'Producto eliminado', text: resultado.message || 'Se eliminó correctamente.' });
                loadAnalytics();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: error.message });
            }
        }

        function viewProduct(productId) {
            const producto = productosDisponibles.find(item => Number(item.id_producto) === Number(productId));
            if (!producto) return;

            const setDetail = (id, value) => {
                document.getElementById(id).textContent = value === null || value === undefined || value === '' ? '-' : String(value);
            };

            document.getElementById('detailProductTitle').textContent = producto.nombre_producto || 'Detalles del Producto';
            setDetail('detailProductId', producto.id_producto);
            setDetail('detailProductName', producto.nombre_producto);
            setDetail('detailProductCategory', producto.nombre_categoria);
            setDetail('detailProductModel', producto.nombre_modelo);
            setDetail('detailProductBrand', producto.nombre_marca);
            setDetail('detailProductPrice', `$${Number(producto.precio || 0).toFixed(2)}`);
            setDetail('detailProductStock', producto.stock_actual ?? producto.stock);
            setDetail('detailProductMinStock', producto.stock_minimo);
            setDetail('detailProductMaxStock', producto.stock_maximo);
            setDetail('detailProductSerial', producto.serial);
            setDetail('detailProductStatus', producto.estado);
            setDetail('detailProductDescription', producto.descripcion_producto);
            setDetail('detailProductWarranty', producto.clausula_garantia);

            const image = document.getElementById('detailProductImage');
            const placeholder = document.getElementById('detailProductImagePlaceholder');
            if (producto.imagen) {
                image.src = producto.imagen;
                image.hidden = false;
                placeholder.hidden = true;
            } else {
                image.removeAttribute('src');
                image.hidden = true;
                placeholder.hidden = false;
            }

            const characteristics = document.getElementById('detailProductCharacteristics');
            characteristics.replaceChildren();
            Object.entries(producto.caracteristicas || {}).forEach(([name, value]) => {
                if (['id', 'id_producto'].includes(name)) return;
                const item = document.createElement('div');
                item.className = 'product-detail';
                const label = document.createElement('span');
                label.textContent = name.replace(/_/g, ' ');
                const detailValue = document.createElement('strong');
                detailValue.textContent = value === null || value === '' ? '-' : String(value);
                item.append(label, detailValue);
                characteristics.appendChild(item);
            });

            productDetailsModal.style.display = 'block';
            productDetailsModal.setAttribute('aria-hidden', 'false');
        }

        function closeProductDetails() {
            productDetailsModal.style.display = 'none';
            productDetailsModal.setAttribute('aria-hidden', 'true');
        }

        function openFilterModal() {
            Swal.fire({
                title: 'Filtrar productos',
                input: 'text',
                inputPlaceholder: 'Nombre, categoría, modelo o marca',
                showCancelButton: true,
                confirmButtonText: 'Filtrar',
                cancelButtonText: 'Limpiar'
            }).then(result => {
                const filtro = result.isConfirmed ? (result.value || '').trim().toLocaleLowerCase() : '';
                document.querySelectorAll('.product-card').forEach(card => {
                    card.hidden = filtro !== '' && !card.textContent.toLocaleLowerCase().includes(filtro);
                });
            });
        }

        function clearProductForm() {
            const action = document.getElementById('productAction').value;
            const productId = document.getElementById('productId').value;
            productForm.reset();
            document.getElementById('productAction').value = action;
            document.getElementById('productId').value = productId;
            productCategoryFields.replaceChildren();
            productCategoryTable.value = '';
            showProductImagePreview('');
            clearTemporaryProductImageUrl();
            limpiarValidacionesFormulario(productFormConfig);
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-product-action]');
            if (!button) return;

            const productId = button.dataset.productId;
            switch (button.dataset.productAction) {
                case 'register':
                    openModal('registrar');
                    break;
                case 'edit':
                    openModal('editar', productId);
                    break;
                case 'delete':
                    deleteProduct(productId);
                    break;
                case 'view':
                    viewProduct(productId);
                    break;
                case 'filter':
                    openFilterModal();
                    break;
                case 'clear':
                    clearProductForm();
                    break;
            }
        });

        const analyticsProduct = document.getElementById('analyticsProduct');
        const analyticsCategory = document.getElementById('analyticsCategory');
        const analyticsStatus = document.getElementById('analyticsStatus');
        const analyticsKpis = document.getElementById('analyticsKpis');
        const analyticsRecommendations = document.getElementById('analyticsRecommendations');
        const analyticsCanvas = document.getElementById('analyticsChart');
        let analyticsChart = null;

        function setAnalyticsStatus(message, type = '') {
            analyticsStatus.textContent = message;
            analyticsStatus.className = `analytics-status ${type}`.trim();
        }

        async function postAnalytics(action, values = {}) {
            const form = new FormData();
            form.append('accion', action);
            Object.entries(values).forEach(([key, value]) => form.append(key, String(value)));
            const response = await fetch(window.location.href, { method: 'POST', body: form });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.detail || result.message || 'No se pudo completar la solicitud.');
            }
            return result;
        }

        function displayMetric(value, suffix = '') {
            if (value === null || value === undefined || !Number.isFinite(Number(value))) {
                return 'No disponible';
            }
            return `${Number(value).toLocaleString('es-VE', { maximumFractionDigits: 2 })}${suffix}`;
        }

        function renderAnalyticsChart(data) {
            if (typeof Chart === 'undefined') {
                setAnalyticsStatus('Chart.js no está disponible; los datos del análisis sí se recibieron.', 'error');
                return;
            }
            const historical = Array.isArray(data.historico) ? data.historico : [];
            const forecast = Array.isArray(data.prediccion) ? data.prediccion : [];
            const labels = [...historical.map(item => item.fecha), ...forecast.map(item => item.fecha)];
            const empty = Array(historical.length).fill(null);
            const missingForecast = Array(forecast.length).fill(null);
            const datasets = [
                {
                    label: 'Ventas reales',
                    data: [...historical.map(item => item.valor), ...missingForecast],
                    borderColor: '#2563eb',
                    backgroundColor: '#2563eb',
                    borderWidth: 2,
                    pointRadius: 1,
                    tension: 0.2
                },
                {
                    label: 'Demanda estimada',
                    data: [...empty, ...forecast.map(item => item.valor)],
                    borderColor: '#f97316',
                    backgroundColor: '#f97316',
                    borderWidth: 2,
                    borderDash: [7, 5],
                    pointRadius: 1,
                    tension: 0.2
                },
                {
                    label: 'Límite inferior',
                    data: [...empty, ...forecast.map(item => item.limite_inferior)],
                    borderColor: 'rgba(249, 115, 22, 0)',
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Intervalo estimado',
                    data: [...empty, ...forecast.map(item => item.limite_superior)],
                    borderColor: 'rgba(249, 115, 22, 0)',
                    backgroundColor: 'rgba(249, 115, 22, 0.15)',
                    pointRadius: 0,
                    fill: 2
                }
            ];

            if (analyticsChart) analyticsChart.destroy();
            analyticsChart = new Chart(analyticsCanvas.getContext('2d'), {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.dataset.label}: ${context.parsed.y === null ? '—' : `${context.parsed.y.toLocaleString('es-VE', { maximumFractionDigits: 2 })} unidades`}`
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Unidades' } },
                        x: { title: { display: true, text: `Fecha · horizonte ${data.horizonte_dias} días` } }
                    }
                }
            });
        }

        function renderAnalyticsRecommendations(items) {
            analyticsRecommendations.replaceChildren();
            if (!items.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;  // 6 columnas (sin explicación)
                cell.textContent = 'No hay acciones recomendadas con la información disponible.';
                row.appendChild(cell);
                analyticsRecommendations.appendChild(row);
                return;
            }

            items.forEach(item => {
                // Fila principal con los datos del combo
                const row = document.createElement('tr');
                const values = [
                    item.producto || 'Producto',
                    item.senal || '—',
                    item.cantidad_sugerida === null || item.cantidad_sugerida === undefined ? '—' : item.cantidad_sugerida,
                    `${item.nivel_confianza || '—'} (${Math.round((item.confianza || 0) * 100)}%)`,
                    item.calculado_en ? new Date(item.calculado_en).toLocaleString('es-VE') : '—'
                ];
                values.forEach(value => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.appendChild(cell);
                });

                const ratingCell = document.createElement('td');
                const rating = document.createElement('div');
                rating.className = 'analytics-rating';
                rating.setAttribute('role', 'group');
                rating.setAttribute('aria-label', `Valorar ${item.producto || 'recomendación'}`);
                for (let stars = 1; stars <= 5; stars += 1) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = '★';
                    button.dataset.recommendationId = item.recomendacion_id;
                    button.dataset.recommendationType = item.tipo;
                    button.dataset.stars = String(stars);
                    button.setAttribute('aria-label', `${stars} estrellas`);
                    button.title = `${stars} estrellas`;
                    rating.appendChild(button);
                }
                ratingCell.appendChild(rating);
                row.appendChild(ratingCell);
                analyticsRecommendations.appendChild(row);

                // Fila secundaria para la explicación
                if (item.explicacion) {
                    const explanationRow = document.createElement('tr');
                    explanationRow.className = 'explanation-row';

                    // Celda para la explicación que abarca las primeras 5 columnas
                    const explanationCell = document.createElement('td');
                    explanationCell.colSpan = 6;
                    explanationCell.style.padding = '0';
                    explanationCell.style.backgroundColor = '#ffffff';
                    explanationCell.style.borderTop = '1px solid #e0e0e0';

                    // Contenedor principal de la explicación
                    const explanationContainer = document.createElement('div');
                    explanationContainer.style.padding = '16px';
                    explanationContainer.style.display = 'flex';
                    explanationContainer.style.flexDirection = 'column';
                    explanationContainer.style.gap = '12px';

                    // Parsear la explicación por secciones
                    const sections = item.explicacion.split('\n\n');

                    // Agrupar secciones por tipo
                    const problemaSection = sections.find(s => s.includes('PROBLEMA IDENTIFICADO'));
                    const solucionSection = sections.find(s => s.includes('SOLUCIÓN ESTRATÉGICA'));
                    const evidenciaSection = sections.find(s => s.includes('EVIDENCIA HISTÓRICA'));
                    const impactoSection = sections.find(s => s.includes('IMPACTO FINANCIERO'));
                    const ejecucionSection = sections.find(s => s.includes('RECOMENDACIÓN DE EJECUCIÓN'));
                    const metricasSection = sections.find(s => s.includes('MÉTRICAS DE SEGUIMIENTO'));



                    // 2. Contenedor para las 3 tarjetas intermedias (grid de 3 columnas)
                    const middleContainer = document.createElement('div');
                    middleContainer.style.display = 'grid';
                    middleContainer.style.gridTemplateColumns = 'repeat(3, 1fr)';
                    middleContainer.style.gap = '12px';

                                                // 1. Tarjeta PROBLEMA IDENTIFICADO (ancho completo)
                    if (problemaSection) {
                        const card = document.createElement('div');
                        card.style.padding = '12px';
                        card.style.borderRadius = '8px';
                        card.style.border = '1px solid #fdba74';
                        card.style.backgroundColor = '#fff7ed';
                        card.style.fontSize = '1.5em';
                        card.style.lineHeight = '1.4';
                        card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:700;font-size:1.1em;color:#9a3412;">⚠️ PROBLEMA IDENTIFICADO</div>' +
                                       '<div style="color:#9a3412;">' + problemaSection.replace('⚠️ **PROBLEMA IDENTIFICADO:**', '').trim() + '</div>';
                        middleContainer.appendChild(card);
                    }
                    // EVIDENCIA HISTÓRICA
                    if (evidenciaSection) {
                        const card = document.createElement('div');
                        card.style.padding = '12px';
                        card.style.borderRadius = '8px';
                        card.style.border = '1px solid #93c5fd';
                        card.style.backgroundColor = '#eff6ff';
                        card.style.fontSize = '1.5em';
                        card.style.lineHeight = '1.4';
                        card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#1e40af;">📊 EVIDENCIA HISTÓRICA</div>' +
                                       '<div style="color:#1e40af;">' + evidenciaSection.replace('📊 **EVIDENCIA HISTÓRICA:**', '').trim() + '</div>';
                        middleContainer.appendChild(card);
                    }

                    // IMPACTO FINANCIERO
                    if (impactoSection) {
                        const card = document.createElement('div');
                        card.style.padding = '12px';
                        card.style.borderRadius = '8px';
                        card.style.border = '1px solid #fde047';
                        card.style.backgroundColor = '#fef9c3';
                        card.style.fontSize = '1.5em';
                        card.style.lineHeight = '1.4';
                        card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#854d0e;">💰 IMPACTO FINANCIERO</div>' +
                                       '<div style="color:#854d0e;">' + impactoSection.replace('💰 **IMPACTO FINANCIERO:**', '').trim() + '</div>';
                        middleContainer.appendChild(card);
                    }

                    // RECOMENDACIÓN DE EJECUCIÓN
                    /*
                    if (ejecucionSection) {
                        const card = document.createElement('div');
                        card.style.padding = '12px';
                        card.style.borderRadius = '8px';
                        card.style.border = '1px solid #86efac';
                        card.style.backgroundColor = '#f0fdf4';
                        card.style.fontSize = '0.85em';
                        card.style.lineHeight = '1.4';
                        card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#166534;">🎯 RECOMENDACIÓN DE EJECUCIÓN</div>' +
                                       '<div style="color:#166534;">' + ejecucionSection.replace('🎯 **RECOMENDACIÓN DE EJECUCIÓN:**', '').trim() + '</div>';
                        middleContainer.appendChild(card);
                    }
                    */
                    explanationContainer.appendChild(middleContainer);

                    // 3. Tarjeta SOLUCIÓN ESTRATÉGICA (ancho completo) que incluye MÉTRICAS como nota
                    if (solucionSection) {
                        const card = document.createElement('div');
                        card.style.padding = '16px';
                        card.style.borderRadius = '8px';
                        card.style.border = '1px solid #86efac';
                        card.style.backgroundColor = '#f0fdf4';
                        card.style.fontSize = '1.5em';
                        card.style.lineHeight = '1.5';

                        const solucionText = (solucionSection || ejecucionSection || item.explicacion)
                            .replace(/^(?:💡 )?\*\*SOLUCIÓN ESTRATÉGICA:\*\*/i, '').trim();
                        const metricasText = metricasSection
                            ? metricasSection.replace('📈 **MÉTRICAS DE SEGUIMIENTO:**', '').trim()
                            : '';

                        let innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;font-weight:700;font-size:1.1em;color:#166534;">💡 SOLUCIÓN ESTRATÉGICA</div>' +
                                       '<div style="color:#166534;margin-bottom:16px;">' + solucionText + '</div>' +
                                       '<button class="add-combo-btn" data-combo-info=\'' + JSON.stringify({
                                            productos: item.productos,
                                            cantidad_congelada: item.cantidad_congelada,
                                            cantidad_popular: item.cantidad_popular,
                                            descuento: item.descuento_sugerido,
                                            recomendacion_id: item.recomendacion_id
                                        }).replace(/'/g, "\\'") + '\' style="background:#22c55e;color:white;border:none;padding:12px 24px;border-radius:6px;cursor:pointer;font-size:1em;font-weight:600;margin-bottom:16px;transition:background 0.2s;" onmouseover="this.style.background=\'#16a34a\'" onmouseout="this.style.background=\'#22c55e\'">+ Agregar Combo Recomendado</button>';
                        /*
                        if (metricasText) {
                            innerHTML += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #86efac;">' +
                                        '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#166534;font-size:0.9em;">📈 Nota: MÉTRICAS DE SEGUIMIENTO</div>' +
                                        '<div style="color:#166534;font-size:0.85em;">' + metricasText + '</div></div>';
                        }
                        */
                        card.innerHTML = innerHTML;
                        explanationContainer.appendChild(card);
                    }

                    explanationCell.appendChild(explanationContainer);
                    explanationRow.appendChild(explanationCell);

                    // Celda vacía para la columna de valoración
                    const emptyRatingCell = document.createElement('td');
                    emptyRatingCell.style.padding = '0';
                    emptyRatingCell.style.border = 'none';
                    emptyRatingCell.style.backgroundColor = '#ffffff';
                    emptyRatingCell.style.borderTop = '1px solid #e0e0e0';
                    explanationRow.appendChild(emptyRatingCell);

                    analyticsRecommendations.appendChild(explanationRow);
                }
            });
        }

        async function loadAnalytics() {
            const productId = analyticsProduct.value;
            const categoryId = analyticsCategory.value;

            if (productId && categoryId) {
                setAnalyticsStatus('Selecciona un producto o una categoría, pero no ambos.', 'error');
                return;
            }

            const button = document.getElementById('runAnalytics');
            button.disabled = true;
            setAnalyticsStatus('Consultando ventas y calculando el análisis…');
            try {
                const result = await postAnalytics('analitica_dashboard', {
                    producto_id: productId || '',
                    categoria_id: categoryId || '',
                    frecuencia: document.getElementById('analyticsFrequency').value,
                    horizonte: document.getElementById('analyticsHorizon').value,
                });
                if (result.calidad?.estado === 'sin_datos') {
                    analyticsKpis.hidden = true;
                    renderAnalyticsRecommendations([]);
                    setAnalyticsStatus('El producto o categoría seleccionado no tiene productos activos.');
                    return;
                }
                if (result.calidad?.estado === 'sin_ventas') {
                    analyticsKpis.hidden = true;
                    renderAnalyticsChart(result);
                    renderAnalyticsRecommendations([]);
                    setAnalyticsStatus('No hay ventas de facturas pagadas para este filtro en los últimos 365 días.');
                    return;
                }

                analyticsKpis.hidden = false;
                const metrics = result.metricas || {};
                analyticsKpis.querySelector('[data-metric="venta_proyectada"]').textContent = displayMetric(metrics.venta_proyectada);
                analyticsKpis.querySelector('[data-metric="dias_estimados_agotamiento"]').textContent = displayMetric(metrics.dias_estimados_agotamiento, ' días');
                analyticsKpis.querySelector('[data-metric="punto_reorden"]').textContent = displayMetric(metrics.punto_reorden, ' unidades');
                analyticsKpis.querySelector('[data-metric="cantidad_sugerida_compra"]').textContent = displayMetric(metrics.cantidad_sugerida_compra, ' unidades');
                analyticsKpis.querySelector('[data-metric="rotacion_proyectada"]').textContent = displayMetric(metrics.rotacion_proyectada, 'x');

                // Mostrar explicaciones de manera especial
                /*
                const explicacionesElement = analyticsKpis.querySelector('[data-metric="explicaciones"]');
                if (metrics.explicaciones && typeof metrics.explicaciones === 'object') {
                    // Crear un tooltip o texto expandible con las explicaciones
                    const explicacionesText = Object.values(metrics.explicaciones).filter(e => e).join('\n\n');
                    explicacionesElement.textContent = explicacionesText.length > 100 ? explicacionesText.substring(0, 100) + '...' : explicacionesText;
                    explicacionesElement.title = explicacionesText; // Tooltip completo
                    explicacionesElement.style.cursor = 'help';
                } else {
                    explicacionesElement.textContent = 'No disponible';
                }
                    */
                renderAnalyticsChart(result);
                renderAnalyticsRecommendations(result.recomendaciones || []);

                const quality = result.calidad || {};
                if (quality.estado === 'insuficiente_historial') {
                    setAnalyticsStatus('Historial insuficiente: se requieren al menos 24 semanas y seis semanas con ventas para entrenar el modelo.');
                } else if (quality.estado === 'modelo_pendiente') {
                    setAnalyticsStatus('Se muestran ventas reales. Entrena el modelo local para habilitar predicciones.');
                } else {
                    setAnalyticsStatus(`Modelo ${quality.modelo || ''} · entrenamiento ${quality.fecha_entrenamiento || 'sin fecha'} · ${result.horizonte_dias} días.`);
                }
            } catch (error) {
                setAnalyticsStatus(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        }

        document.getElementById('runAnalytics').addEventListener('click', loadAnalytics);

        // Sin filtros, el microservicio analiza el inventario completo y genera combos estratégicos.
        loadAnalytics();

        // Event listener para botones "Agregar Combo Recomendado"
        document.addEventListener('click', async function(event) {
            if (event.target.classList.contains('add-combo-btn')) {
                const comboInfo = JSON.parse(event.target.dataset.comboInfo);
                const descuentoSugerido = Number(comboInfo.descuento);
                if (
                    comboInfo.descuento === null ||
                    comboInfo.descuento === undefined ||
                    String(comboInfo.descuento).trim() === '' ||
                    !Number.isFinite(descuentoSugerido) ||
                    descuentoSugerido < 0 ||
                    descuentoSugerido > 100
                ) {
                    alert('La recomendación no incluye un descuento válido. Vuelve a consultar el análisis antes de crear el combo.');
                    return;
                }

                // Pedir nombre del combo al usuario
                const nombreCombo = prompt('Ingresa el nombre para el combo:');
                if (!nombreCombo || nombreCombo.trim() === '') {
                    alert('El nombre del combo es obligatorio');
                    return;
                }

                try {
                    // Generar descripción automática
                    const descripcion = "Combo especial que Incluye " + comboInfo.cantidad_popular + " unidad(es) del producto principal y " + comboInfo.cantidad_congelada + " unidad(es) del producto congelado. Descuento aplicado: " + descuentoSugerido + "%.";

                    // Formatear productos como array de objetos con id y cantidad
                    const productosFormateados = comboInfo.productos.map((productoId, index) => ({
                        id: productoId,  // Usar 'id' en lugar de 'id_producto'
                        cantidad: index === 0 ? comboInfo.cantidad_popular : comboInfo.cantidad_congelada
                    }));

                    // Enviar solicitud al controlador de combos
                    const formData = new FormData();
                    formData.append('accion', 'crear_combo');
                    formData.append('nombre_combo', nombreCombo.trim());
                    formData.append('descripcion', descripcion);
                    formData.append('descuento', String(descuentoSugerido));
                    formData.append('productos', JSON.stringify(productosFormateados));

                    const response = await fetch('?pagina=combo', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (!response.ok || result.status === 'error') {
                        throw new Error(result.message || 'Error al crear el combo');
                    }

                    alert('Combo creado exitosamente: ' + nombreCombo);
                } catch (error) {
                    alert('Error al crear el combo: ' + error.message);
                }
            }
        });

        analyticsProduct.addEventListener('change', function () {
            if (this.value) analyticsCategory.value = '';
        });
        analyticsCategory.addEventListener('change', function () {
            if (this.value) analyticsProduct.value = '';
        });

        const trainAnalyticsButton = document.getElementById('trainAnalytics');
        if (trainAnalyticsButton) {
            trainAnalyticsButton.addEventListener('click', async function () {
                this.disabled = true;
                setAnalyticsStatus('Entrenando el modelo local con las 24 semanas completas más recientes…');
                try {
                    const result = await postAnalytics('analitica_entrenar');
                    if (result.estado === 'insuficiente_historial') {
                        setAnalyticsStatus('No se actualizó el modelo: ningún producto alcanzó seis semanas con ventas dentro de la ventana. El modelo anterior se conservó.');
                    } else {
                        setAnalyticsStatus(
                            `Entrenamiento completado: ${result.modelos_entrenados} modelos guardados; ${result.productos_sin_historial_suficiente} productos sin historial suficiente.`,
                            'success'
                        );
                    }
                    if (analyticsProduct.value || analyticsCategory.value) await loadAnalytics();
                } catch (error) {
                    setAnalyticsStatus(error.message, 'error');
                } finally {
                    this.disabled = false;
                }
            });
        }

        analyticsRecommendations.addEventListener('click', async function (event) {
            const button = event.target.closest('button[data-recommendation-id]');
            if (!button || button.disabled) return;
            const group = button.parentElement;
            group.querySelectorAll('button').forEach(star => { star.disabled = true; });
            try {
                await postAnalytics('analitica_feedback', {
                    recomendacion_id: button.dataset.recommendationId,
                    tipo: button.dataset.recommendationType,
                    estrellas: button.dataset.stars
                });
                group.querySelectorAll('button').forEach(star => {
                    if (Number(star.dataset.stars) <= Number(button.dataset.stars)) star.classList.add('selected');
                });
                setAnalyticsStatus('Valoración guardada. Se tendrá en cuenta al priorizar futuras recomendaciones.', 'success');
            } catch (error) {
                group.querySelectorAll('button').forEach(star => { star.disabled = false; });
                setAnalyticsStatus(error.message, 'error');
            }
        });

        // Event listeners para cerrar modal
        productModal.querySelector('.close-modal').addEventListener('click', closeModal);
        productDetailsModal.querySelector('.close-modal').addEventListener('click', closeProductDetails);
        document.getElementById('closeProductDetails').addEventListener('click', closeProductDetails);

        window.addEventListener('click', function(event) {
            if (event.target === productModal) {
                closeModal();
            }
            if (event.target === productDetailsModal) {
                closeProductDetails();
            }
        });

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
