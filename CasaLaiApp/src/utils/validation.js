/**
 * Utilidades de validación con expresiones regulares
 * Previene envío de datos malformados a la API
 */

// Regex para validar email (estándar RFC 5322)
export const EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;

// Regex para teléfono venezolano (0412, 0414, 0424, 0416, 0426)
export const VENEZUELA_PHONE_REGEX = /^(0412|0414|0424|0416|0426)-?\d{7}$/;

// Regex para número de referencia de pago móvil (6 dígitos)
export const PAYMENT_REFERENCE_REGEX = /^\d{6}$/;

// Regex para cédula venezolana (V- seguido de 7-8 dígitos)
export const VENEZUELA_CI_REGEX = /^[VEJ]-?\d{7,8}$/;

// Regex para RIF venezolano (J- seguido de 8-9 dígitos y un dígito verificador)
export const VENEZUELA_RIF_REGEX = /^[JGVE]-?\d{8,9}-?\d$/;

// Regex para nombre (solo letras, espacios, acentos y ñ)
export const NAME_REGEX = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;

// Regex para contraseña de login (6-15 caracteres, sin requisitos especiales)
export const PASSWORD_LOGIN_REGEX = /^.{6,15}$/;

// Regex para contraseña de registro (6-15 caracteres, al menos una mayúscula, un número y un carácter especial)
export const PASSWORD_REGISTER_REGEX = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"\'<>,.?\/\\]).{6,15}$/;

// Regex para número de cuenta bancaria venezolana (20 dígitos)
export const BANK_ACCOUNT_REGEX = /^\d{20}$/;

// Regex para código de banco venezolano (4 dígitos)
export const BANK_CODE_REGEX = /^\d{4}$/;

// Regex para texto general (previene caracteres peligrosos para SQL injection)
export const SAFE_TEXT_REGEX = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,\-_@]+$/;

// Regex para username (3-50 caracteres, solo letras, números y guiones bajos)
export const USERNAME_REGEX = /^[a-zA-Z0-9_]{3,50}$/;

/**
 * Valida un email
 * @param {string} email - Email a validar
 * @returns {boolean} - True si es válido
 */
export const validateEmail = (email) => {
  if (!email || typeof email !== 'string') return false;
  return EMAIL_REGEX.test(email.trim());
};

/**
 * Valida un username (3-50 caracteres, solo letras, números y guiones bajos)
 * @param {string} username - Username a validar
 * @returns {boolean} - True si es válido
 */
export const validateUsername = (username) => {
  if (!username || typeof username !== 'string') return false;
  return USERNAME_REGEX.test(username.trim());
};

/**
 * Valida un email con dominios específicos permitidos
 * @param {string} email - Email a validar
 * @param {Array<string>} allowedDomains - Lista de dominios permitidos (ej: ['gmail.com', 'yahoo.com'])
 * @returns {boolean} - True si es válido y el dominio está permitido
 */
export const validateEmailWithDomain = (email, allowedDomains = []) => {
  if (!email || typeof email !== 'string') return false;
  
  // Primero validar el formato del email
  if (!validateEmail(email)) return false;
  
  // Si no hay dominios específicos, cualquier formato válido es aceptado
  if (allowedDomains.length === 0) return true;
  
  // Extraer el dominio del email
  const domain = email.trim().split('@')[1]?.toLowerCase();
  
  // Verificar si el dominio está en la lista de permitidos
  return allowedDomains.includes(domain);
};

/**
 * Valida un teléfono venezolano
 * @param {string} phone - Teléfono a validar
 * @returns {boolean} - True si es válido
 */
export const validateVenezuelaPhone = (phone) => {
  if (!phone || typeof phone !== 'string') return false;
  return VENEZUELA_PHONE_REGEX.test(phone.trim());
};

/**
 * Valida una referencia de pago móvil
 * @param {string} reference - Referencia a validar
 * @returns {boolean} - True si es válida
 */
export const validatePaymentReference = (reference) => {
  if (!reference || typeof reference !== 'string') return false;
  return PAYMENT_REFERENCE_REGEX.test(reference.trim());
};

/**
 * Valida una cédula venezolana
 * @param {string} ci - Cédula a validar
 * @returns {boolean} - True si es válida
 */
export const validateVenezuelaCI = (ci) => {
  if (!ci || typeof ci !== 'string') return false;
  return VENEZUELA_CI_REGEX.test(ci.trim().toUpperCase());
};

/**
 * Valida un RIF venezolano
 * @param {string} rif - RIF a validar
 * @returns {boolean} - True si es válido
 */
export const validateVenezuelaRIF = (rif) => {
  if (!rif || typeof rif !== 'string') return false;
  return VENEZUELA_RIF_REGEX.test(rif.trim().toUpperCase());
};

/**
 * Valida un nombre
 * @param {string} name - Nombre a validar
 * @returns {boolean} - True si es válido
 */
export const validateName = (name) => {
  if (!name || typeof name !== 'string') return false;
  return NAME_REGEX.test(name.trim());
};

/**
 * Valida una contraseña de login (6-15 caracteres)
 * @param {string} password - Contraseña a validar
 * @returns {boolean} - True si es válida
 */
export const validatePassword = (password) => {
  if (!password || typeof password !== 'string') return false;
  return PASSWORD_LOGIN_REGEX.test(password);
};

/**
 * Valida una contraseña de registro (6-15 caracteres, al menos una mayúscula, un número y un carácter especial)
 * @param {string} password - Contraseña a validar
 * @returns {boolean} - True si es válida
 */
export const validatePasswordRegister = (password) => {
  if (!password || typeof password !== 'string') return false;
  return PASSWORD_REGISTER_REGEX.test(password);
};

/**
 * Valida un número de cuenta bancaria
 * @param {string} account - Número de cuenta a validar
 * @returns {boolean} - True si es válido
 */
export const validateBankAccount = (account) => {
  if (!account || typeof account !== 'string') return false;
  return BANK_ACCOUNT_REGEX.test(account.replace(/-/g, ''));
};

/**
 * Valida un código de banco
 * @param {string} code - Código de banco a validar
 * @returns {boolean} - True si es válido
 */
export const validateBankCode = (code) => {
  if (!code || typeof code !== 'string') return false;
  return BANK_CODE_REGEX.test(code.trim());
};

/**
 * Valida texto seguro (previene caracteres peligrosos)
 * @param {string} text - Texto a validar
 * @returns {boolean} - True si es seguro
 */
export const validateSafeText = (text) => {
  if (!text || typeof text !== 'string') return false;
  return SAFE_TEXT_REGEX.test(text.trim());
};

/**
 * Valida longitud mínima y máxima
 * @param {string} value - Valor a validar
 * @param {number} min - Longitud mínima
 * @param {number} max - Longitud máxima
 * @returns {boolean} - True si está dentro del rango
 */
export const validateLength = (value, min, max) => {
  if (!value || typeof value !== 'string') return false;
  const len = value.trim().length;
  return len >= min && len <= max;
};

/**
 * Valida que un valor no esté vacío
 * @param {string} value - Valor a validar
 * @returns {boolean} - True si no está vacío
 */
export const validateNotEmpty = (value) => {
  if (value === null || value === undefined) return false;
  if (typeof value === 'string') return value.trim().length > 0;
  if (typeof value === 'number') return !isNaN(value);
  return false;
};

/**
 * Valida múltiples campos y retorna errores
 * @param {Object} fields - Objeto con campos a validar
 * @param {Object} rules - Objeto con reglas de validación
 * @returns {Object} - Objeto con errores por campo
 */
export const validateFields = (fields, rules) => {
  const errors = {};

  Object.keys(rules).forEach((fieldName) => {
    const fieldRules = rules[fieldName];
    const value = fields[fieldName];

    for (const rule of fieldRules) {
      if (rule.required && !validateNotEmpty(value)) {
        errors[fieldName] = rule.message || 'Este campo es requerido';
        break;
      }

      if (rule.minLength && !validateLength(value, rule.minLength, Infinity)) {
        errors[fieldName] = rule.message || `Mínimo ${rule.minLength} caracteres`;
        break;
      }

      if (rule.maxLength && !validateLength(value, 0, rule.maxLength)) {
        errors[fieldName] = rule.message || `Máximo ${rule.maxLength} caracteres`;
        break;
      }

      if (rule.email && !validateEmail(value)) {
        errors[fieldName] = rule.message || 'Email inválido';
        break;
      }

      if (rule.phone && !validateVenezuelaPhone(value)) {
        errors[fieldName] = rule.message || 'Teléfono venezolano inválido';
        break;
      }

      if (rule.reference && !validatePaymentReference(value)) {
        errors[fieldName] = rule.message || 'Referencia inválida (6 dígitos)';
        break;
      }

      if (rule.password && !validatePassword(value)) {
        errors[fieldName] = rule.message || 'Contraseña inválida (mínimo 8 caracteres, mayúscula, minúscula y número)';
        break;
      }

      if (rule.name && !validateName(value)) {
        errors[fieldName] = rule.message || 'Nombre inválido';
        break;
      }

      if (rule.safeText && !validateSafeText(value)) {
        errors[fieldName] = rule.message || 'Contiene caracteres no permitidos';
        break;
      }

      if (rule.custom && !rule.custom(value)) {
        errors[fieldName] = rule.message || 'Valor inválido';
        break;
      }
    }
  });

  return errors;
};

export default {
  validateEmail,
  validateVenezuelaPhone,
  validatePaymentReference,
  validateVenezuelaCI,
  validateVenezuelaRIF,
  validateName,
  validatePassword,
  validatePasswordRegister,
  validateUsername,
  validateBankAccount,
  validateBankCode,
  validateSafeText,
  validateLength,
  validateNotEmpty,
  validateFields,
  EMAIL_REGEX,
  VENEZUELA_PHONE_REGEX,
  PAYMENT_REFERENCE_REGEX,
  VENEZUELA_CI_REGEX,
  VENEZUELA_RIF_REGEX,
  NAME_REGEX,
  PASSWORD_LOGIN_REGEX,
  PASSWORD_REGISTER_REGEX,
  USERNAME_REGEX,
  BANK_ACCOUNT_REGEX,
  BANK_CODE_REGEX,
  SAFE_TEXT_REGEX,
};
