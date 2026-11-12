/**
 * Utilidades para llamadas API seguras
 * Previene inyecciones SQL y otros ataques al comunicarse con la API
 */

import { sanitizeForAPI } from '../sanitization';
import { API_CONFIG as IMPORTED_API_CONFIG } from '../config/apiConfig';
import { encryption } from './encryption';
import { getStoredToken } from './jwt';

// Configuración de la API (usando configuración centralizada)
let API_CONFIG = { ...IMPORTED_API_CONFIG };

const getRequestHeaders = async () => {
  const token = await getStoredToken();
  return {
    ...API_CONFIG.headers,
    ...(token ? { Authorization: `Bearer ${token.replace(/^Bearer\s+/i, '')}` } : {}),
  };
};

/**
 * Crea un AbortSignal con timeout compatible con todos los navegadores
 * @param {number} timeout - Tiempo en milisegundos
 * @returns {AbortSignal} - Signal para cancelar la petición
 */
const createTimeoutSignal = (timeout) => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);
  return { signal: controller.signal, timeoutId };
};

/**
 * Campos que deben encriptarse antes de enviar a la API
 */
const SENSITIVE_FIELDS = [
  'password',
  'pass',
  'contrasena',
  'clave',
  'currentpassword',
  'newpassword',
  'confirmpassword',
  'email',
  'correo',
  'telefono',
  'phone',
  'direccion',
  'address',
  'nombre',
  'nombres',
  'name',
  'apellido',
  'apellidos',
  'lastname',
  'cedula',
  'identificacion',
  'dni',
  'tarjeta',
  'card',
  'cvv',
  'expiracion',
  'expiry',
  'username',
];

/**
 * Determina qué campos de un objeto deben encriptarse
 * @param {Object} data - Datos a analizar
 * @returns {Array<string>} - Lista de campos a encriptar
 */
const getFieldsToEncrypt = (data) => {
  if (!data || typeof data !== 'object') {
    return [];
  }

  return Object.keys(data).filter(key => 
    SENSITIVE_FIELDS.some(sensitiveField => 
      key.toLowerCase().includes(sensitiveField.toLowerCase())
    )
  );
};

/**
 * Sanitiza y prepara los datos para enviar a la API
 * @param {Object} data - Datos a enviar
 * @param {Object} schema - Esquema de sanitización
 * @param {boolean} encryptSensitive - Si se deben encriptar campos sensibles
 * @returns {Object} - Datos sanitizados y potencialmente encriptados
 */
const prepareAPIData = (data, schema = null, encryptSensitive = true) => {
  if (!data || typeof data !== 'object') {
    return {};
  }

  let sanitizedData = sanitizeForAPI(data, schema);

  // Encriptar campos sensibles si se solicita
  if (encryptSensitive) {
    const fieldsToEncrypt = getFieldsToEncrypt(sanitizedData);
    if (fieldsToEncrypt.length > 0) {
      sanitizedData = encryption.encryptArray(sanitizedData, fieldsToEncrypt);
    }
  }

  return sanitizedData;
};

/**
 * Realiza una petición GET segura
 * @param {string} endpoint - Endpoint de la API
 * @param {Object} params - Parámetros de la query
 * @param {Object} schema - Esquema de sanitización
 * @returns {Promise} - Respuesta de la API
 */
export const secureGet = async (endpoint, params = {}, schema = null) => {
  try {
    // Sanitizar parámetros
    const sanitizedParams = prepareAPIData(params, schema);

    // Construir URL con parámetros sanitizados
    const url = new URL(`${API_CONFIG.baseURL}${endpoint}`);
    Object.keys(sanitizedParams).forEach(key => {
      if (sanitizedParams[key] !== undefined && sanitizedParams[key] !== null) {
        url.searchParams.append(key, sanitizedParams[key]);
      }
    });

    const { signal, timeoutId } = createTimeoutSignal(API_CONFIG.timeout);
    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: await getRequestHeaders(),
      signal,
    });

    clearTimeout(timeoutId);
    return await handleResponse(response);
  } catch (error) {
    throw handleAPIError(error);
  }
};

/**
 * Realiza una petición POST segura
 * @param {string} endpoint - Endpoint de la API
 * @param {Object} data - Datos a enviar
 * @param {Object} schema - Esquema de sanitización
 * @returns {Promise} - Respuesta de la API
 */
export const securePost = async (endpoint, data = {}, schema = null, requestTimeout = API_CONFIG.timeout, encryptSensitive = true) => {
  try {
    // Sanitizar datos y encriptar campos sensibles
    const sanitizedData = prepareAPIData(data, schema, encryptSensitive);

    const url = `${API_CONFIG.baseURL}${endpoint}`;
    console.log('API POST Request:', {
      url,
      endpoint,
      baseURL: API_CONFIG.baseURL,
      encrypted: encryptSensitive,
    });

    const { signal, timeoutId } = createTimeoutSignal(requestTimeout);
    const response = await fetch(url, {
      method: 'POST',
      headers: await getRequestHeaders(),
      body: JSON.stringify(sanitizedData),
      signal,
    });

    clearTimeout(timeoutId);
    console.log('API POST Response:', {
      status: response.status,
      ok: response.ok,
      statusText: response.statusText,
      headers: Object.fromEntries(response.headers.entries())
    });

    return await handleResponse(response);
  } catch (error) {
    console.error('API POST Error:', error);
    console.error('Error details:', {
      name: error.name,
      message: error.message,
      stack: error.stack
    });
    throw handleAPIError(error);
  }
};

/**
 * Realiza una petición PUT segura
 * @param {string} endpoint - Endpoint de la API
 * @param {Object} data - Datos a enviar
 * @param {Object} schema - Esquema de sanitización
 * @returns {Promise} - Respuesta de la API
 */
export const securePut = async (endpoint, data = {}, schema = null, encryptSensitive = true) => {
  try {
    // Sanitizar datos y encriptar campos sensibles
    const sanitizedData = prepareAPIData(data, schema, encryptSensitive);

    const { signal, timeoutId } = createTimeoutSignal(API_CONFIG.timeout);
    const response = await fetch(`${API_CONFIG.baseURL}${endpoint}`, {
      method: 'PUT',
      headers: await getRequestHeaders(),
      body: JSON.stringify(sanitizedData),
      signal,
    });

    clearTimeout(timeoutId);
    return await handleResponse(response);
  } catch (error) {
    throw handleAPIError(error);
  }
};

/**
 * Realiza una petición DELETE segura
 * @param {string} endpoint - Endpoint de la API
 * @param {Object} params - Parámetros de la query
 * @param {Object} schema - Esquema de sanitización
 * @returns {Promise} - Respuesta de la API
 */
export const secureDelete = async (endpoint, params = {}, schema = null) => {
  try {
    // Sanitizar parámetros
    const sanitizedParams = prepareAPIData(params, schema);

    // Construir URL con parámetros sanitizados
    const url = new URL(`${API_CONFIG.baseURL}${endpoint}`);
    Object.keys(sanitizedParams).forEach(key => {
      if (sanitizedParams[key] !== undefined && sanitizedParams[key] !== null) {
        url.searchParams.append(key, sanitizedParams[key]);
      }
    });

    const { signal, timeoutId } = createTimeoutSignal(API_CONFIG.timeout);
    const response = await fetch(url.toString(), {
      method: 'DELETE',
      headers: await getRequestHeaders(),
      signal,
    });

    clearTimeout(timeoutId);
    return await handleResponse(response);
  } catch (error) {
    throw handleAPIError(error);
  }
};

/**
 * Maneja la respuesta de la API
 * @param {Response} response - Respuesta de fetch
 * @returns {Promise} - Datos de la respuesta
 */
const handleResponse = async (response) => {
  const contentType = response.headers.get('content-type');
  const responseText = await response.text();
  const diagnostics = {
    contentType,
    responseBodyLength: responseText.length,
    ...(typeof __DEV__ !== 'undefined' && __DEV__ && !response.ok && responseText
      ? { responsePreview: responseText.slice(0, 500) }
      : {}),
  };

  console.log('API Response status:', response.status);
  console.log('API Response ok:', response.ok);
  console.log('API Response contentType:', contentType);
  console.log('API Response diagnostics:', diagnostics);

  if (!responseText.trim()) {
    if (!response.ok) {
      throw new APIError(
        `El servidor devolvió HTTP ${response.status} sin contenido. Revisa el registro de errores PHP del hosting.`,
        response.status,
        diagnostics
      );
    }

    return contentType?.includes('application/json') ? null : responseText;
  }

  if (contentType?.includes('application/json')) {
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (error) {
      throw new APIError(
        `El servidor devolvió JSON inválido (HTTP ${response.status}).`,
        response.status,
        { ...diagnostics, originalError: error.message }
      );
    }

    if (!response.ok) {
      throw new APIError(
        data.message || data.error || 'Error en la petición',
        response.status,
        data
      );
    }

    return data;
  }

  if (!response.ok) {
    throw new APIError(
      `El servidor devolvió HTTP ${response.status} con una respuesta no JSON.`,
      response.status,
      diagnostics
    );
  }

  return responseText;
};

/**
 * Maneja errores de la API
 * @param {Error} error - Error capturado
 * @returns {APIError} - Error formateado
 */
const handleAPIError = (error) => {
  if (error instanceof APIError) {
    return error;
  }
  
  if (error.name === 'AbortError' || error.name === 'TimeoutError') {
    return new APIError('El registro tardó demasiado y la solicitud fue cancelada. Intenta nuevamente.', 408);
  }
  
  if (error.message?.includes('NetworkError') || error.message?.includes('Failed to fetch')) {
    return new APIError(
      'No se pudo conectar con la API. Comprueba la URL, Apache/XAMPP y la configuración CORS.',
      0
    );
  }
  
  return new APIError(error.message || 'Error desconocido', 500);
};

/**
 * Clase personalizada para errores de API
 */
class APIError extends Error {
  constructor(message, status, data = null) {
    super(message);
    this.name = 'APIError';
    this.status = status;
    this.data = data;
  }
}

/**
 * Esquemas de sanitización predefinidos para endpoints comunes
 */
export const API_SCHEMAS = {
  // Autenticación
  login: {
    email: { type: 'email' },
    username: { type: 'text', maxLength: 50 },
    password: { type: 'sql' },
  },
  register: {
    email: { type: 'email' },
    password: { type: 'sql' },
    name: { type: 'text', maxLength: 100 },
    phone: { type: 'phone' },
  },
  
  // Productos
  searchProducts: {
    query: { type: 'text', maxLength: 100 },
    category: { type: 'text', maxLength: 50 },
  },
  getProducts: {
    categoria: { type: 'number' },
    id: { type: 'number' },
  },
  
  // Pagos
  createPayment: {
    phone: { type: 'phone' },
    reference: { type: 'reference' },
    bankId: { type: 'text', maxLength: 4 },
    amount: { type: 'number' },
  },
  
  // Perfil
  updateProfile: {
    name: { type: 'text', maxLength: 100 },
    email: { type: 'email' },
    phone: { type: 'phone' },
    address: { type: 'text', maxLength: 200 },
  },
};

/**
 * Configura la URL base de la API
 * @param {string} baseURL - Nueva URL base
 */
export const setAPIBaseURL = (baseURL) => {
  API_CONFIG.baseURL = baseURL;
};

/**
 * Configura los headers por defecto
 * @param {Object} headers - Headers adicionales
 */
export const setDefaultHeaders = (headers) => {
  API_CONFIG.headers = {
    ...API_CONFIG.headers,
    ...headers,
  };
};

/**
 * Configura el timeout de las peticiones
 * @param {number} timeout - Timeout en milisegundos
 */
export const setAPITimeout = (timeout) => {
  API_CONFIG.timeout = timeout;
};

export default {
  secureGet,
  securePost,
  securePut,
  secureDelete,
  prepareAPIData,
  API_SCHEMAS,
  setAPIBaseURL,
  setDefaultHeaders,
  setAPITimeout,
  APIError,
};
