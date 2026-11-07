    $('#formularioPago').on('submit', function(event) {
        event.preventDefault();

        // Validación previa antes de enviar (puedes mejorarla según tus reglas)
        let isValid = true;
        let errorMessages = [];

        // Parser robusto: usa el último separador como decimal
        function parsearNumeroFormateado(texto) {
            if (texto === null || texto === undefined) return 0;
            let s = String(texto).trim();
            if (s === '') return 0;
            s = s.replace(/[^0-9.,\-]/g, '');
            const lastDot = s.lastIndexOf('.');
            const lastComma = s.lastIndexOf(',');
            const decIdx = Math.max(lastDot, lastComma);
            if (decIdx >= 0) {
                const integerPart = s.slice(0, decIdx).replace(/[^0-9\-]/g, '');
                const fracPart = s.slice(decIdx + 1).replace(/[^0-9]/g, '');
                const composed = integerPart + '.' + fracPart;
                const n = parseFloat(composed);
                return isNaN(n) ? 0 : n;
            } else {
                const n = parseFloat(s.replace(/[^0-9\-]/g, ''));
                return isNaN(n) ? 0 : n;
            }
        }

        // Validar que la suma de montos sea igual al total
let montoTotal = parsearNumeroFormateado($('#monto_total').val());

let totalRegistrado = 0;
$('.monto-pago').each(function() {
    totalRegistrado += parsearNumeroFormateado($(this).val());
});

        if (Math.abs(totalRegistrado - montoTotal) > 0.01) {
            isValid = false;
            errorMessages.push(`La suma de los montos (${totalRegistrado.toFixed(2)} Bs) no coincide con el total a pagar (${montoTotal.toFixed(2)} Bs).`);
        }

        $('.bloque-pago').each(function(index) {
            const tipoPago = $(this).find('select[id^="tipo-"]').val();
            if (!tipoPago) {
                isValid = false;
                errorMessages.push(`Debe seleccionar un tipo de pago para el método ${index + 1}.`);
            }
            if (!$(this).find('.monto-pago').val()) {
                isValid = false;
                errorMessages.push(`Debe ingresar el monto para el método de pago ${index + 1}.`);
            }
            if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') {
                const cuenta = $(this).find('select[id^="cuenta-"]').val();
                if (!cuenta) {
                    isValid = false;
                    errorMessages.push(`Debe seleccionar una cuenta para el método de pago ${index + 1}.`);
                }
                if (!$(this).find('input[id^="referencia-"]').val()) {
                    isValid = false;
                    errorMessages.push(`Debe ingresar la referencia para el método de pago ${index + 1}.`);
                }
                if (!$(this).find('input[id^="comprobante-"]')[0].files.length) {
                    isValid = false;
                    errorMessages.push(`Debe adjuntar el comprobante para el método de pago ${index + 1}.`);
                }
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errorMessages.join('<br>'),
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // Si pasa la validación, envía el formulario por AJAX
        const formData = new FormData(this);

            enviarAjaxPago(formData, function(data) {
        if (data.status === 'success') {
            Swal.fire({
                title: 'Éxito',
                text: data.message || 'Pago registrado exitosamente',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = '?pagina=pasarela'; // Cambia la redirección si lo necesitas
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Error al registrar el pago',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    });
    });


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

function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}


document.getElementById('cuenta').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];

    if (selectedOption.value !== "") {
      // Obtener los datos del atributo data-*
      const banco = selectedOption.getAttribute('data-nombre');
      const numero = selectedOption.getAttribute('data-numero');
      const rif = selectedOption.getAttribute('data-rif');
      const telefono = selectedOption.getAttribute('data-telefono');
      const correo = selectedOption.getAttribute('data-correo');

      // Insertar los datos en la tabla
      document.getElementById('datoBanco').textContent = banco;
      document.getElementById('datoNumero').textContent = numero;
      document.getElementById('datoRif').textContent = rif;
      document.getElementById('datoTelefono').textContent = telefono;
      document.getElementById('datoCorreo').textContent = correo;

      // Mostrar la tabla y el título
      const contenedor = document.getElementById('tablaDatosCuenta');
      contenedor.style.display = 'block';

      // Agregar título si no existe aún
      if (!document.getElementById('tituloDatosPago')) {
        const titulo = document.createElement('h4');
        titulo.id = 'tituloDatosPago';
        titulo.textContent = 'DATOS PARA REALIZAR EL PAGO';
        titulo.style.marginBottom = '10px';
        titulo.style.marginTop = '20px';
        contenedor.insertBefore(titulo, contenedor.firstChild);
      }
    }
  });

// ...existing code...

function enviarAjaxPago(datos, callback) {
    $.ajax({
        async: true,
        url: "", // mismo archivo PHP
        type: "POST",
        data: datos,
        contentType: false,
        processData: false,
        cache: false,
        beforeSend: function () {
            // Puedes mostrar un loader aquí si lo deseas
        },
        timeout: 10000,
        success: function (respuesta) {
            let data;
            try {
                data = typeof respuesta === "object" ? respuesta : JSON.parse(respuesta);
            } catch (e) {
                Swal.fire("Error", "Respuesta inesperada del servidor. Intente de nuevo.", "error");
                console.error("Error en JSON:", respuesta);
                return;
            }
            if (callback) callback(data);
        },
        error: function (xhr, status, error) {
            Swal.fire("Error", "Error en la solicitud AJAX: " + (xhr.responseText || error), "error");
            console.error("AJAX error:", status, error, xhr.responseText);
        },
        complete: function () {
            // Oculta loader si lo usaste
        }
    });
}