/**
 * Utilidades de sanitización para prevenir inyecciones SQL, XSS y otros ataques
 * Importante: Sanitizar SIEMPRE antes de enviar datos a la API
 */

/**
 * Escapa caracteres especiales para prevenir SQL injection
 * @param {string} input - Input a sanitizar
 * @returns {string} - Input sanitizado
 */
export const escapeSQL = (input) => {
  if (!input || typeof input !== 'string') return '';
  
  return input
    .replace(/[\n\r"'\\\%]/g, (char) => {
      switch (char) {
        case '\n':
          return '\\n';
        case '\r':
          return '\\r';
        case '"':
        case "'":
        case '\\':
        case '%':
          return '\\' + char;
        default:
          return char;
      }
    });
};

/**
 * Escapa caracteres HTML para prevenir XSS
 * @param {string} input - Input a sanitizar
 * @returns {string} - Input sanitizado
 */
export const escapeHTML = (input) => {
  if (!input || typeof input !== 'string') return '';
  
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
    '/': '&#x2F;',
  };
  
  return input.replace(/[&<>"'/]/g, (char) => map[char]);
};

/**
 * Elimina caracteres peligrosos para SQL injection
 * @param {string} input - Input a sanitizar
 * @returns {string} - Input sanitizado
 */
export const sanitizeForSQL = (input) => {
  if (!input || typeof input !== 'string') return '';
  
  // Eliminar caracteres peligrosos para SQL
  return input
    .replace(/['";\\]/g, '') // Eliminar comillas y backslash
    .replace(/--/g, '') // Eliminar comentarios SQL
    .replace(/\/\*/g, '') // Eliminar inicio de comentarios multilínea
    .replace(/\*\//g, '') // Eliminar fin de comentarios multilínea
    .replace(/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|ALTER|CREATE|TRUNCATE)\b/gi, '') // Eliminar palabras clave SQL
    .trim();
};

/**
 * Sanitiza input para prevenir XSS
 * @param {string} input - Input a sanitizar
 * @returns {string} - Input sanitizado
 */
export const sanitizeForXSS = (input) => {
  if (!input || typeof input !== 'string') return '';
  
  // Eliminar etiquetas HTML y scripts
  return input
    .replace(/<script[^>]*>.*?<\/script>/gi, '')
    .replace(/<[^>]*>/g, '') // Eliminar todas las etiquetas HTML
    .replace(/javascript:/gi, '')
    .replace(/on\w+\s*=/gi, '') // Eliminar event handlers
    .trim();
};

/**
 * Sanitiza un número de teléfono (solo dígitos)
 * @param {string} phone - Teléfono a sanitizar
 * @returns {string} - Teléfono sanitizado
 */
export const sanitizePhone = (phone) => {
  if (!phone || typeof phone !== 'string') return '';
  
  // Mantener solo dígitos
  return phone.replace(/\D/g, '');
};

/**
 * Sanitiza una referencia de pago (solo dígitos)
 * @param {string} reference - Referencia a sanitizar
 * @returns {string} - Referencia sanitizada
 */
export const sanitizeReference = (reference) => {
  if (!reference || typeof reference !== 'string') return '';
  
  // Mantener solo dígitos
  return reference.replace(/\D/g, '');
};

/**
 * Sanitiza un email (elimina espacios y caracteres peligrosos)
 * @param {string} email - Email a sanitizar
 * @returns {string} - Email sanitizado
 */
export const sanitizeEmail = (email) => {
  if (!email || typeof email !== 'string') return '';
  
  return email
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '') // Eliminar espacios
    .replace(/[<>]/g, ''); // Eliminar caracteres peligrosos
};

/**
 * Sanitiza texto general (elimina caracteres peligrosos)
 * @param {string} text - Texto a sanitizar
 * @returns {string} - Texto sanitizado
 */
export const sanitizeText = (text) => {
  if (!text || typeof text !== 'string') return '';
  
  return text
    .trim()
    .replace(/[<>"'\\]/g, '') // Eliminar caracteres peligrosos
    .replace(/javascript:/gi, '')
    .replace(/on\w+\s*=/gi, '');
};

/**
 * Sanitiza un número (elimina caracteres no numéricos excepto punto y signo)
 * @param {string|number} num - Número a sanitizar
 * @returns {string} - Número sanitizado
 */
export const sanitizeNumber = (num) => {
  if (num === null || num === undefined) return '';
  if (typeof num === 'number') return num.toString();
  
  return num.replace(/[^0-9.\-]/g, '');
};

/**
 * Sanitiza un objeto completo recursivamente
 * @param {Object} obj - Objeto a sanitizar
 * @returns {Object} - Objeto sanitizado
 */
export const sanitizeObject = (obj) => {
  if (!obj || typeof obj !== 'object') return obj;
  
  if (Array.isArray(obj)) {
    return obj.map(item => sanitizeObject(item));
  }
  
  const sanitized = {};
  Object.keys(obj).forEach((key) => {
    const value = obj[key];
    
    if (typeof value === 'string') {
      // Sanitizar strings
      sanitized[key] = sanitizeForSQL(sanitizeForXSS(value));
    } else if (typeof value === 'object' && value !== null) {
      // Recursión para objetos anidados
      sanitized[key] = sanitizeObject(value);
    } else {
      // Mantener otros tipos sin cambios
      sanitized[key] = value;
    }
  });
  
  return sanitized;
};

/**
 * Sanitiza parámetros de URL
 * @param {Object} params - Parámetros a sanitizar
 * @returns {Object} - Parámetros sanitizados
 */
export const sanitizeURLParams = (params) => {
  if (!params || typeof params !== 'object') return {};
  
  const sanitized = {};
  Object.keys(params).forEach((key) => {
    const value = params[key];
    
    if (typeof value === 'string') {
      sanitized[key] = encodeURIComponent(sanitizeText(value));
    } else if (typeof value === 'number') {
      sanitized[key] = value;
    } else if (Array.isArray(value)) {
      sanitized[key] = value.map(v => 
        typeof v === 'string' ? encodeURIComponent(sanitizeText(v)) : v
      );
    } else {
      sanitized[key] = value;
    }
  });
  
  return sanitized;
};

/**
 * Valida y sanitiza un input completo
 * @param {string} input - Input a validar y sanitizar
 * @param {string} type - Tipo de input (email, phone, text, number, etc.)
 * @returns {Object} - Objeto con { valid: boolean, sanitized: string, error: string }
 */
export const validateAndSanitize = (input, type = 'text') => {
  if (!input || typeof input !== 'string') {
    return { valid: false, sanitized: '', error: 'Input inválido' };
  }
  
  let sanitized = '';
  let valid = true;
  let error = '';
  
  switch (type) {
    case 'email':
      sanitized = sanitizeEmail(input);
      valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(sanitized);
      error = valid ? '' : 'Email inválido';
      break;
      
    case 'phone':
      sanitized = sanitizePhone(input);
      valid = /^\d{11}$/.test(sanitized);
      error = valid ? '' : 'Teléfono inválido';
      break;
      
    case 'reference':
      sanitized = sanitizeReference(input);
      valid = /^\d{6}$/.test(sanitized);
      error = valid ? '' : 'Referencia inválida';
      break;
      
    case 'number':
      sanitized = sanitizeNumber(input);
      valid = !isNaN(parseFloat(sanitized));
      error = valid ? '' : 'Número inválido';
      break;
      
    case 'sql':
      sanitized = sanitizeForSQL(input);
      valid = sanitized.length > 0;
      error = valid ? '' : 'Contenido inválido';
      break;
      
    case 'xss':
      sanitized = sanitizeForXSS(input);
      valid = sanitized.length > 0;
      error = valid ? '' : 'Contenido inválido';
      break;
      
    case 'text':
    default:
      sanitized = sanitizeText(input);
      valid = sanitized.length > 0;
      error = valid ? '' : 'Texto inválido';
      break;
  }
  
  return { valid, sanitized, error };
};

/**
 * Sanitiza datos antes de enviar a la API
 * @param {Object} data - Datos a enviar a la API
 * @param {Object} schema - Esquema de sanitización (opcional)
 * @returns {Object} - Datos sanitizados
 */
export const sanitizeForAPI = (data, schema = null) => {
  if (!data || typeof data !== 'object') return {};
  
  const sanitized = sanitizeObject(data);
  
  // Si hay un esquema, aplicar reglas específicas
  if (schema) {
    Object.keys(schema).forEach((key) => {
      if (sanitized[key] !== undefined) {
        const rule = schema[key];

        // Los tokens externos son valores opacos; no deben modificarse.
        if (rule.type === 'token') {
          sanitized[key] = data[key];
          return;
        }
        
        if (rule.type === 'email') {
          sanitized[key] = sanitizeEmail(sanitized[key]);
        } else if (rule.type === 'phone') {
          sanitized[key] = sanitizePhone(sanitized[key]);
        } else if (rule.type === 'reference') {
          sanitized[key] = sanitizeReference(sanitized[key]);
        } else if (rule.type === 'number') {
          sanitized[key] = sanitizeNumber(sanitized[key]);
        } else if (rule.type === 'sql') {
          sanitized[key] = sanitizeForSQL(sanitized[key]);
        } else if (rule.type === 'xss') {
          sanitized[key] = sanitizeForXSS(sanitized[key]);
        }
        
        // Aplicar maxLength si está especificado
        if (rule.maxLength && typeof sanitized[key] === 'string') {
          sanitized[key] = sanitized[key].substring(0, rule.maxLength);
        }
      }
    });
  }
  
  return sanitized;
};

export default {
  escapeSQL,
  escapeHTML,
  sanitizeForSQL,
  sanitizeForXSS,
  sanitizePhone,
  sanitizeReference,
  sanitizeEmail,
  sanitizeText,
  sanitizeNumber,
  sanitizeObject,
  sanitizeURLParams,
  validateAndSanitize,
  sanitizeForAPI,
};
