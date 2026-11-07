$(function(){
  const btn = $('#btnAddToCart');
  const qtyInput = $('#detalleCantidad');
  const stockMax = parseInt(qtyInput.attr('max')) || 1;

  $('.qty-decrease').on('click', function(){
    let v = parseInt(qtyInput.val()||'1');
    v = isNaN(v)?1:v;
    if (v>1) qtyInput.val(v-1);
  });
  $('.qty-increase').on('click', function(){
    let v = parseInt(qtyInput.val()||'1');
    v = isNaN(v)?1:v;
    if (v<stockMax) qtyInput.val(v+1);
  });
  qtyInput.on('input', function(){
    let v = parseInt($(this).val()||'1');
    if (isNaN(v) || v<1) v=1;
    if (v>stockMax) v=stockMax;
    $(this).val(v);
  });

  btn.on('click', function(){
    const idProducto = $(this).data('id-producto');
    const cantidad = parseInt(qtyInput.val()||'1');

    if (typeof usuarioNoLogueado !== 'undefined' && usuarioNoLogueado) {
      solicitarLogin();
      return;
    }

    const original = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');

    // Validar stock en tiempo real
    $.ajax({
      url: '?pagina=catalogo',
      type: 'POST',
      data: { accion: 'validar_stock', id_producto: idProducto, cantidad: cantidad },
      success: function(resStock){
        try{
          const v = typeof resStock === 'object' ? resStock : JSON.parse(resStock);
          if (v.status !== 'success') throw new Error(v.message || 'Error validando stock');
          if (!v.suficiente){
            Swal.fire('Stock insuficiente', `Solo hay ${v.stock_disponible} disponibles`, 'warning');
            return;
          }
        }catch(e){
          console.error('validar_stock parse error', e, resStock);
          Swal.fire('Error', 'No fue posible validar el stock', 'error');
          return;
        }

        // Si hay stock suficiente, agregar al carrito
        $.ajax({
          url: '?pagina=catalogo',
          type: 'POST',
          data: { accion: 'agregar_al_carrito', id_producto: idProducto, cantidad: cantidad },
          success: function(resp){
            try{
              const data = typeof resp === 'object' ? resp : JSON.parse(resp);
              if (data.status === 'success'){
                Swal.fire({ icon: 'success', title: 'Agregado', text: data.message || 'Producto agregado al carrito', timer: 1200, showConfirmButton: false});
              } else {
                Swal.fire('Error', data.message || 'No se pudo agregar', 'error');
              }
            }catch(e){
              console.error('parse error', e, resp);
              Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
            }
          },
          error: function(xhr){
            Swal.fire('Error', 'Error de conexión', 'error');
          },
          complete: function(){
            btn.prop('disabled', false).html(original);
          }
        });
      },
      error: function(){
        Swal.fire('Error', 'No fue posible validar el stock', 'error');
        btn.prop('disabled', false).html(original);
      }
    });
  });
});
