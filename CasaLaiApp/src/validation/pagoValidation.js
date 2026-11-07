import { validatePhone } from './profileValidation';

export const BANCOS_VENEZUELA = [
  'Banco de Venezuela',
  'Banco Mercantil',
  'Banesco Banco Universal',
  'Banco Provincial',
  'Banco Nacional de Crédito (BNC)',
  'Banco del Tesoro',
  'Banco Bicentenario',
  'BANFANB',
  'Banco Agrícola de Venezuela',
  'Banco del Pueblo Soberano',
  'Banco de Desarrollo Económico y Social (BANDES)',
  'Banco de Comercio Exterior (BANCOEX)',
  'Banco Exterior',
  'Banco Fondo Común (BFC)',
  'Banco Caroní',
  'Banco Activo',
  'Banco Plaza',
  '100% Banco',
  'Bancamiga',
  'Bancaribe',
  'Banplus',
  'Mi Banco',
  'N58 Banco Digital',
  'DelSur Banco Universal',
  'Sofitasa',
  'BANGENTE',
  'Banco Internacional de Desarrollo (BID)',
  'Banco Venezolano de Crédito',
  'Citibank Venezuela',
];

/**
 * Convierte cadenas formateadas o numéricas a float.
 */
export const parsearNumeroFormateado = (texto) => {
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
};

export const limpiarCampoPago = (campo, texto) => {
  const valor = String(texto ?? '');

  if (campo === 'monto') {
    return valor.replace(/[^0-9.,]/g, '');
  }

  if (campo === 'referencia' || campo === 'telefono') {
    return valor.replace(/[^0-9]/g, '').slice(0, campo === 'telefono' ? 11 : 30);
  }

  if (campo === 'remitente') {
    return valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '').replace(/\s{2,}/g, ' ');
  }

  return valor;
};

/**
 * Valida los campos del formulario de pago según el método seleccionado y el pedido.
 * @param {Object} paymentForm - Datos del formulario
 * @param {string} selectedMethod - Método de pago ('Pago Movil', 'Transferencia', 'Zelle')
 * @param {Object} pedido - Objeto del pedido con el monto/total
 * @returns {Array<string>} Lista de errores encontrados
 */
export const validarFormularioPago = (paymentForm, selectedMethod, pedido) => {
  const errores = [];
  const totalPedido = parsearNumeroFormateado(pedido?.total || pedido?.monto || 0);
  const montoIngresado = parsearNumeroFormateado(paymentForm?.monto);
  const montoTexto = String(paymentForm?.monto ?? '').trim();

  // 1. Validar Monto
  if (!paymentForm?.monto || montoTexto === '' || !/^\d+(?:[.,]\d{1,2})?$/.test(montoTexto) || montoIngresado <= 0) {
    errores.push('• Debe ingresar un monto válido.');
  } else if (montoIngresado > totalPedido + 0.01) {
    errores.push(`• El monto no puede superar el total a pagar (${totalPedido.toFixed(2)} Bs).`);
  } else if (Math.abs(montoIngresado - totalPedido) > 0.01) {
    errores.push(`• El monto (${montoIngresado.toFixed(2)} Bs) debe coincidir con el total a pagar (${totalPedido.toFixed(2)} Bs).`);
  }

  // 2. Validar Referencia
  const ref = (paymentForm?.referencia || '').trim();
  if (!ref) {
    errores.push('• Debe ingresar el número de referencia u operación.');
  } else if (ref.length < 4) {
    errores.push('• La referencia debe tener al menos 4 caracteres.');
  } else if (!/^\d+$/.test(ref)) {
    errores.push('• La referencia debe contener solo números.');
  }

  // 3. Validaciones específicas por método de pago
  if (selectedMethod === 'Pago Movil') {
    const tel = (paymentForm?.telefono || '').trim();
    if (!tel) {
      errores.push('• Debe ingresar el número de teléfono del pagador.');
    } else {
      const errorTelefono = validatePhone(tel);
      if (errorTelefono) {
        errores.push(`• ${errorTelefono.replace(/^\*/, '').replace(/\*$/, '')}`);
      }
    }
  } else if (selectedMethod === 'Transferencia') {
    const banco = (paymentForm?.banco_origen || '').trim();
    if (!banco) {
      errores.push('• Debe indicar el banco de origen.');
    } else if (!BANCOS_VENEZUELA.includes(banco)) {
      errores.push('• Seleccione un banco de origen válido de la lista.');
    }
  } else if (selectedMethod === 'Zelle') {
    const remitente = (paymentForm?.remitente || '').trim();

    if (!remitente) {
      errores.push('• Debe ingresar el nombre del remitente.');
    } else if (remitente.length < 3 || !/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/.test(remitente)) {
      errores.push('• Ingrese un nombre de remitente válido.');
    }
  }

  // 4. Comprobante obligatorio
  if (!paymentForm?.comprobante) {
    errores.push('• Debe adjuntar la imagen del comprobante de pago.');
  }

  return errores;
};