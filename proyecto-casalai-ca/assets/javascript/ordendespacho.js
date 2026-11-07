function descargarOrdenDespacho(idOrden, event) {
    console.log('=== INICIO DE DESCARGA DE ORDEN ===');
    console.log('ID de Orden:', idOrden);
    
    // Obtener el botón que activó el evento
    const btn = event.target.closest('.btn-descargar');
    if (!btn) {
        console.error('No se encontró el botón .btn-descargar');
        return;
    }
    
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generando...';
    btn.disabled = true;

    // Crear formulario para enviar la solicitud
    const formData = new FormData();
    formData.append('accion', 'descargarOrden');
    formData.append('descargarOrden', idOrden);

    console.log('Enviando solicitud al servidor...');
    
    // Enviar solicitud
    fetch('Modelo/Controlador/ordendespacho.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        console.log('Respuesta recibida. Estado:', response.status, response.statusText);
        console.log('Headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            console.error('Error en la respuesta del servidor:', response.status, response.statusText);
            return response.text().then(text => {
                console.error('Contenido de la respuesta de error:', text);
                // Check if it's a session error
                try {
                    const jsonError = JSON.parse(text);
                    if (jsonError.message === 'Sesión no iniciada') {
                        throw new Error('Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
                    }
                } catch (e) {
                    // Not JSON, use original text
                }
                throw new Error(`Error del servidor: ${response.status} - ${response.statusText}\n${text}`);
            });
        }
        return response.blob();
    })
    .then(blob => {
        if (!blob || blob.size === 0) {
            throw new Error('El archivo PDF recibido está vacío');
        }
        console.log('Tamaño del archivo recibido:', blob.size, 'bytes');
        console.log('Tipo MIME del archivo:', blob.type);
        
        // Crear enlace de descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `orden_despacho_${idOrden}.pdf`;
        document.body.appendChild(a);
        
        console.log('Iniciando descarga...');
        a.click();
        
        // CORRECCIÓN: Retrasar la revocación del objeto URL para evitar el error de Chromium
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            console.log('Recurso temporal de URL liberado.');
        }, 250); // 250 milisegundos son más que suficientes
    })
    /*.then(blob => {
        if (!blob || blob.size === 0) {
            throw new Error('El archivo PDF recibido está vacío');
        }
        console.log('Tamaño del archivo recibido:', blob.size, 'bytes');
        console.log('Tipo MIME del archivo:', blob.type);
        
        // Crear enlace de descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `orden_despacho_${idOrden}.pdf`;
        document.body.appendChild(a);
        console.log('Iniciando descarga...');
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    })*/
    .catch(error => {
        console.error('Error en la descarga:', error);
        console.error('Stack:', error.stack);
        alert('Error al generar el PDF: ' + error.message);
    })
    .finally(() => {
        console.log('=== FIN DEL PROCESO DE DESCARGA ===');
        // Restaurar botón
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    });
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si la tabla existe
    const tabla = document.getElementById('tablaConsultas');
    if (!tabla) {
        console.error('No se encontró la tabla con ID "tablaConsultas"');
        return;
    }

    // Verificar que la tabla tenga la estructura correcta
    const thead = tabla.querySelector('thead');
    const tbody = tabla.querySelector('tbody');
    
    if (!thead || !tbody) {
        console.error('La tabla no tiene la estructura correcta (falta thead o tbody)');
        return;
    }

    // Contar columnas en el encabezado
    const columnCount = thead.querySelectorAll('th').length;
    console.log('Número de columnas en la tabla:', columnCount);

    // Verificar que todas las filas tengan el mismo número de celdas
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length !== columnCount) {
            console.error(`La fila ${index + 1} tiene ${cells.length} celdas, se esperaban ${columnCount}`);
        }
    });

    // Inicializar DataTable solo si no está ya inicializada
    if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
        try {
            const table = $('#tablaConsultas').DataTable({
                "language": {
                    "url": "assets/public/js/es-ES.json"
                },
                "scrollX": true,
                "scrollCollapse": true,
                "order": [[0, "desc"]],
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "responsive": true,
                "columnDefs": [
                    { 
                        "targets": -1, // Última columna (acciones)
                        "orderable": false,
                        "searchable": false
                    }
                ],
                "initComplete": function() {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    console.log('DataTable inicializada correctamente');
                }
            });

            console.log('DataTable inicializada con éxito');
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    $("#correlativo").on("keypress",function(e){
        validarkeypress(/^[0-9]*$/,e);
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value);
    });
    
    $("#correlativo").on("keyup",function(){
        validarkeyup(
            /^[0-9]{4,10}$/,
            $(this),
            $("#scorrelativo"),
            "Se permite de 4 a 10 dígitos"
        );
    });

    let hoy = new Date();
    let yyyy = hoy.getFullYear();
    let mm = String(hoy.getMonth() + 1).padStart(2, '0');
    let dd = String(hoy.getDate()).padStart(2, '0');
    let fechaMax = `${yyyy}-${mm}-${dd}`;
    $("#fecha").attr("max", fechaMax);
    
    $("#fecha").on("change keyup", function() {
        let fechaInput = $(this).val();
        let hoy = new Date();
        let fechaIngresada = new Date(fechaInput);

        hoy.setHours(0,0,0,0);

        if (fechaInput === "") {
            $("#sfecha").text("Debe ingresar una fecha");
        } else if (fechaIngresada > hoy) {
            $("#sfecha").text("No se permite una fecha futura");
            $(this).addClass("input-error");
        } else {
            $("#sfecha").text("");
            $(this).removeClass("input-error");
        }
    });

    $("#factura").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#sfactura"),
            "Debe seleccionar una factura"
        );
    });

    function validarEnvioOrden(){
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value).trim();
        
        let fecha = $("#fecha").val();
        let hoy = new Date();
        let fechaIngresada = new Date(fecha);
        hoy.setHours(0,0,0,0);

        if(validarkeyup(
            /^[0-9]{4,10}$/,
            $("#correlativo"),
            $("#scorrelativo"),
            "*El correlativo debe tener de 4 a 10 dígitos*"
        )==0){
            mensajes('error', 'Verifique el correlativo', 'Le faltan dígitos al correlativo');
            return false;
        }
        else if(validarkeyup(
            /^.+$/,
            $("#fecha"),
            $("#sfecha"),
            "*Debe ingresar una fecha completa (día, mes y año)*"
        )==0){
            mensajes('error', 'Verifique la fecha', 'La fecha está vacía, incompleta o no es válida');
            return false;
        } else if (fechaIngresada > hoy) {
            $("#sfecha").text("*Solo se permite una fecha actual o una fecha anterior*");
            mensajes('error', 'Verifique la fecha', 'No se permiten fechas futuras');
            return false;
        } else {
            $("#sfecha").text("");
        }

        if($("#factura").val() === null || $("#factura").val() === "") {
            $("#sfactura").text("*Debe seleccionar una factura*");
            mensajes('error', 'Verifique la factura', 'El campo esta vacio');
            return false;
        } else {
            $("#sfactura").text("");
        }

        return true;
    }

    function agregarFilaOrden(orden) {
        const nuevaFila = [
            `<span class="campo-numeros">${orden.correlativo}</span>`,
            `<span class="campo-nombres">${orden.fecha_despacho}</span>`,
            `<span class="campo-numeros">${orden.id_factura}</span>`,
            `<ul>
                <button class="btn-anular"
                    title="Anular Orden de Despacho"
                    data-id="${orden.id_orden_despachos}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const tabla = $('#tablaConsultas').DataTable();
        const rowIdx = tabla.row.add(nuevaFila).draw(false).index();
        $(tabla.row(rowIdx).node()).attr('data-id', orden.id_orden_despachos);
        tabla.page('last').draw('page');
    }

    function resetOrden() {
        $('#correlativo').val('');
        $('#fecha').val('');
        $('#factura').val('');
        $('#scorrelativo').text('');
        $('#sfecha').text('');
        $('#sfactura').text('');
    }

    $('#btnIncluirOrden').on('click', function() {
        $('#ingresarOrdenDespacho')[0].reset();
        $('#scorrelativo').text('');
        $('#sfecha').text('');
        $('#sfactura').text('');
        $('#registrarOrdenModal').modal('show');
    });

    $('#ingresarOrdenDespacho').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioOrden()){
            var datos = new FormData(this);
            datos.append("accion", "ingresar");
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || 'Orden de despacho registrada correctamente'
                    });
                    agregarFilaOrden(respuesta.orden);
                    resetOrden();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || 'No se pudo registrar la orden de despacho'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarOrdenModal .close', function() {
        $('#registrarOrdenModal').modal('hide');
    });

    $(document).on('click', '.btn-marcar', function () {
        const $boton = $(this);
        const $fila = $boton.closest('tr');
        const id = $fila.data('id');
        const estado_actual = $fila.find('.campo-rango').text().trim();

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                accion: 'cambiar_estado_orden',
                id: id,
                estado_actual: estado_actual
            },
            success: function (resp) {
                let r = JSON.parse(resp);
                if (r.status === 'success') {
                    $fila.find('.campo-rango').text(r.nuevo_estado);

                    if (r.nuevo_estado === 'Entregada') {
                        $fila.find('.btn-marcar').remove();
                        $fila.find('.btn-anular').remove();
                    }

                    Swal.fire('Estado cambiado a "' + r.nuevo_estado + '"');

                    const tabla = $('#tablaConsultas').DataTable();
                    tabla.order([
                        [4, 'asc'],
                        [0, 'asc'] 
                    ]).draw();
                } else {
                    Swal.fire('Error', r.message || 'No se pudo cambiar el estado', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo cambiar el estado', 'error');
            }
        });
    });

    function anularFilaOrdenDespacho(idOrden) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${idOrden}"]`).addClass('anulada');
        tabla.row(fila).remove().draw();
    }

    $(document).on('click', '.btn-anular', function (e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Está seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, anularla!'
        }).then((result) => {
            if (result.isConfirmed) {

                var idOrden = $(this).data('id-orden');

                var datos = new FormData();
                datos.append('accion', 'anularOrden');
                datos.append('id_orden_despachos', idOrden);

                $.ajax({
                    url: '', // ruta correcta aquí
                    type: 'POST',
                    data: datos,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (respuesta) {
                        if (respuesta.status === 'success') {
                            Swal.fire(
                                'Anulada!',
                                'La orden de despacho ha sido anulada.',
                                'success'
                            );
                            anularFilaOrdenDespacho(idOrden);
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al anular la orden', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
                    }
                });
            }
        });
    });

    function mensajes(icono, titulo, mensaje){
        Swal.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            showConfirmButton: true,
            confirmButtonText: 'Aceptar',
        });
    }

    function validarkeypress(er, e) {
        key = e.keyCode;
        tecla = String.fromCharCode(key);
        a = er.test(tecla);

        if (!a) {
            e.preventDefault();
        }
    }

    function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
        a = er.test(etiqueta.val());

        if (a) {
            etiquetamensaje.text("");
            return 1;
        } else {
            etiquetamensaje.text(mensaje);
            return 0;
        }
    }

    function space(str) {
        const regex = /\s{2,}/g;
        var str = str.replace(regex, ' ');
        return str;
    }
    
    function muestraMensaje(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: mensaje
        });
    }

    function mostrarDatosFormData(formData) {
        console.log('Datos enviados en FormData:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
    }

    function enviarAjax(datos, callback) {
        console.log("Enviando datos AJAX: ", datos);
        $.ajax({
            url: '', 
            type: 'POST',
            contentType: false,
            data: datos,
            processData: false,
            cache: false,
            success: function (respuesta) {
                console.log("Respuesta del servidor: ", respuesta); 
                callback(JSON.parse(respuesta));
            },
            error: function () {
                console.error('Error en la solicitud AJAX');
                muestraMensaje('Error en la solicitud AJAX');
            }
        });
    }

    let modalAyudaInstance = null;

    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
        // Si ya existe una instancia, solo abrir y navegar
        if (modalAyudaInstance) {
            modalAyudaInstance.openModal(contexto);

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
        $.get('assets/public/ayuda/ordendespacho.php')
            .done(function(html) {
                // Eliminar cualquier modal existente para evitar conflictos
                $('#modalAyuda').remove();
                
                // Agregar el nuevo modal
                $('body').append(html);

                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            // Inicializar modal
                            if (typeof inicializarModalAyuda === 'function') {
                                modalAyudaInstance = inicializarModalAyuda();

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
                                console.error('La función inicializarModalAyuda no está disponible');
                            }
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else {
                    // Si el JS ya está cargado, solo abrir el modal existente
                    if (typeof inicializarModalAyuda === 'function') {
                        modalAyudaInstance = inicializarModalAyuda();

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

    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda-modal', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });

const tablaOrden = document.getElementById('tablaConsultas');
const modalOrden = document.getElementById('modalDetallesOrden');
const cerrarOrden = document.getElementById('cerrarModalDetallesOrden');

tablaOrden.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-detalle');
    if (!btn) return;
    let productos = JSON.parse(btn.dataset.productos);

    // Cargar datos principales
    document.getElementById('detalleCliente').textContent = btn.dataset.cliente;
    document.getElementById('detalleCedula').textContent = btn.dataset.cedula;
    document.getElementById('detalleFecha').textContent = btn.dataset.fecha;

    // Productos
    let prodHtml = '';
    productos.forEach(p => {
        let total = (parseFloat(p.precio_unitario) * parseFloat(p.cantidad)).toFixed(2);
        prodHtml += `
            <tr>
                <td><span class="campo-numeros">${p.codigo}</span></td>
                <td><span class="campo-nombres">${p.producto}</span></td>
                <td><span class="campo-nombres">${p.modelo}</span></td>
                <td><span class="campo-nombres">${p.marca}</span></td>
                <td><span class="campo-numeros">${p.serial}</span></td>
                <td><span class="campo-numeros">${p.cantidad}</span></td>
                <td><span class="campo-numeros">${p.precio_unitario}</span></td>
                <td><span class="campo-tex-num">${total}</span></td>
            </tr>
        `;
    });
    document.getElementById('detalleProductos').innerHTML = prodHtml;

    // Mostrar modal
    modalOrden.classList.add('mostrar');
});
// Cierre al hacer clic en el botón "X"
cerrarOrden.addEventListener('click', () => {
    modalOrden.classList.remove('mostrar');
});

// Cierre al hacer clic fuera del contenido del modal
window.addEventListener('click', (e) => {
    if (e.target === modalOrden) {
        modalOrden.classList.remove('mostrar');
    }
})});