/**
 * Módulo de Validaciones para Inicio de Sesión y Registro (App Móvil)
 * Adaptado a partir de la lógica del sistema web (login.js).
 */

export const REGEXES = {
  nombre: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
  apellido: /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
  nombre_usuario: /^[a-zA-Z0-9_]{4,20}$/,
  cedula: /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
  telefono: /^\d{4}-\d{3}-\d{4}$/,
  correo: /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
  direccion: /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s]{4,100}$/,
  clave: /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/,
  loginUsername: /^[a-zA-Z0-9_]{4,20}$/,
};

export const cleanSpaces = (text) => text.replace(/\s{2,}/g, ' ');

export const filterKeyPress = {
  nombre: (text) => cleanSpaces(text.replace(/[^a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]/g, '')),
  apellido: (text) => cleanSpaces(text.replace(/[^a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]/g, '')),
  nombre_usuario: (text) => text.replace(/[^a-zA-Z0-9_]/g, ''),
  correo: (text) => text.replace(/[^A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@]/g, ''),
  direccion: (text) => cleanSpaces(text.replace(/[^a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s]/g, '')),
  clave: (text) => text.replace(/[^A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\"]/g, ''),
  
  cedula: (text) => {
    const digits = text.replace(/\D/g, '').slice(0, 8);
    if (digits.length === 7) {
      return `${digits.slice(0, 1)}.${digits.slice(1, 4)}.${digits.slice(4, 7)}`;
    } else if (digits.length === 8) {
      return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}`;
    }
    return digits;
  },

  telefono: (text) => {
    const digits = text.replace(/\D/g, '').slice(0, 11);
    if (digits.length > 7) {
      return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7, 11)}`;
    } else if (digits.length > 4) {
      return `${digits.slice(0, 4)}-${digits.slice(4, 7)}`;
    }
    return digits;
  },
};

export const validateField = (field, value, extraValue = null) => {
  const val = (value || '').trim();

  if (!val) {
    return '*Este campo es obligatorio*';
  }

  switch (field) {
    case 'usernameLogin':
      if (!REGEXES.loginUsername.test(val)) {
        return '*Ingrese su nombre de usuario (4-20 caracteres)*';
      }
      break;

    case 'passwordLogin':
      if (val.length < 6 || val.length > 15) {
        return '*Ingrese su contraseña de seguridad (6-15 caracteres)*';
      }
      break;

    case 'nombre':
      if (!REGEXES.nombre.test(val)) {
        return '*Solo letras, de 2 a 50 caracteres*';
      }
      break;

    case 'apellido':
      if (!REGEXES.apellido.test(val)) {
        return '*Solo letras, de 2 a 50 caracteres*';
      }
      break;

    case 'nombre_usuario':
      if (!REGEXES.nombre_usuario.test(val)) {
        return '*4-20 caracteres alfanuméricos*';
      }
      break;

    case 'cedula':
      if (!REGEXES.cedula.test(val)) {
        return '*Formato válido: 1.234.567 o 12.345.678*';
      }
      break;

    case 'telefono':
      if (!REGEXES.telefono.test(val)) {
        return '*Formato válido: 0400-000-0000*';
      }
      break;

    case 'correo':
      if (!REGEXES.correo.test(val)) {
        return '*Debe terminar en, por ejemplo: @gmail.com*';
      }
      break;

    case 'direccion':
      if (val.length < 4) {
        return '*Mínimo 4 caracteres*';
      }
      if (!REGEXES.direccion.test(val)) {
        return '*El formato permite letras y números*';
      }
      break;

    case 'clave':
      if (!REGEXES.clave.test(val)) {
        return '*6-15 caracteres, mínimo una mayúscula, número y caracter especial*';
      }
      break;

    case 'clave_confirmar':
      if (!REGEXES.clave.test(val)) {
        return '*Ingrese la contraseña nuevamente*';
      }
      if (extraValue && val !== extraValue) {
        return '*Las contraseñas no coinciden*';
      }
      break;

    default:
      break;
  }

  return '';
};

export const validateLoginForm = (data) => {
  const errors = {};
  errors.username = validateField('usernameLogin', data.username);
  errors.password = validateField('passwordLogin', data.password);

  const isValid = !errors.username && !errors.password;
  return { isValid, errors };
};

export const validateRegisterForm = (data) => {
  const errors = {};

  errors.nombre = validateField('nombre', data.nombre);
  errors.apellido = validateField('apellido', data.apellido);
  errors.nombre_usuario = validateField('nombre_usuario', data.nombre_usuario);
  errors.cedula = validateField('cedula', data.cedula);
  errors.telefono = validateField('telefono', data.telefono);
  errors.correo = validateField('correo', data.correo);
  errors.direccion = validateField('direccion', data.direccion);
  errors.clave = validateField('clave', data.clave);
  errors.claveConfirmar = validateField('clave_confirmar', data.claveConfirmar, data.clave);

  const isValid = Object.values(errors).every((err) => err === '');
  return { isValid, errors };
};