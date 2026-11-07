/**
 * Configuración de la API para la aplicación móvil
 *
 * IMPORTANTE: Cuando subas el sistema al host, actualiza la URL base
 * con la URL real de tu servidor.
 *
 * Para cambiar entre desarrollo y producción:
 * - Usa EXPO_PUBLIC_API_URL en .env
 * - O modifica src/config/envConfig.js y cambia IS_PRODUCTION a true
 */

const DEFAULT_API_URL = 'https://casalai.alwaysdata.net/api';
const configuredApiUrl = process.env.EXPO_PUBLIC_API_URL;
const isLoopbackApiUrl = /^https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?(?:\/|$)/i
  .test(configuredApiUrl || '');
const isDevelopmentBuild = typeof __DEV__ !== 'undefined' && __DEV__;

export const API_BASE_URL = (
  configuredApiUrl && (!isLoopbackApiUrl || isDevelopmentBuild)
    ? configuredApiUrl
    : DEFAULT_API_URL
).replace(/\/+$/, '');

export const buildProductImageUrl = (imageValue) => {
  if (!imageValue) {
    return null;
  }

  const value = String(imageValue).trim().replace(/\\/g, '/');
  if (!value) {
    return null;
  }

  if (/^data:/i.test(value)) {
    return value;
  }

  const serverRoot = API_BASE_URL.replace(/\/api\/?$/i, '');

  // Si la URL ya contiene localhost/proyecto-casalai-ca, reemplazarla completamente
  if (value.includes('localhost/proyecto-casalai-ca')) {
    const imagePath = value.replace(/^.*?\/assets\/img\/productos\//i, '');
    return `${serverRoot}/assets/img/productos/${imagePath}`;
  }

  // Si es una URL HTTP/HTTPS, reemplazar el dominio
  if (/^https?:\/\//i.test(value)) {
    return value
      .replace(/^https?:\/\/localhost\/proyecto-casalai-ca/i, serverRoot)
      .replace(/^https?:\/\/localhost(?::\d+)?/i, serverRoot);
  }

  const productImagesPath = value.match(/(?:^|\/)assets\/img\/productos\/(.+)$/i);

  if (!productImagesPath) {
    return `${serverRoot}/assets/img/productos/${value
      .replace(/^\.?\//, '')
      .split('/')
      .map(encodeURIComponent)
      .join('/')}`;
  }

  const imagePath = productImagesPath[1]
    .split('/')
    .map(encodeURIComponent)
    .join('/');
  const assetPathIndex = value.toLowerCase().lastIndexOf('/assets/img/productos/');
  const urlPrefix = value.slice(0, assetPathIndex);
  const externalOrigin = urlPrefix.match(/^(https?:\/\/[^/]+)/i)?.[1];
  const pathPrefix = externalOrigin ? urlPrefix.slice(externalOrigin.length) : '';
  const uniquePathSegments = pathPrefix
    .split('/')
    .filter(Boolean)
    .filter((segment, index, segments) => (
      index === 0 || segment.toLowerCase() !== segments[index - 1].toLowerCase()
    ));
  const imageRoot = externalOrigin
    ? `${externalOrigin}${uniquePathSegments.length ? `/${uniquePathSegments.join('/')}` : ''}`
    : serverRoot;

  // Si el origen es localhost/proyecto-casalai-ca, reemplazarlo
  if (externalOrigin && externalOrigin.includes('localhost')) {
    return `${serverRoot}/assets/img/productos/${imagePath}`;
  }

  return `${imageRoot}/assets/img/productos/${imagePath}`;
};

// Ruta del servidor - Usar siempre HTTPS en producción
// Para desarrollo local: C:/xampp/htdocs/proyecto-casalai-ca
// Para producción Always Data: Usar rutas del servidor de Always Data
const PROJECT_SERVER_ROOT = 'C:/xampp/htdocs/proyecto-casalai-ca'; // Solo para desarrollo local

// Ruta real del servidor donde PHP debe guardar la imagen.
// IMPORTANTE: esto NO es una URL para el navegador; es una ruta del sistema de archivos.
// En XAMPP debe apuntar a la carpeta física del proyecto, no a una URL pública.
export const PROFILE_IMAGE_STORAGE_PATH = `${PROJECT_SERVER_ROOT}/assets/img/uploads/`;

// URL pública para acceder a la imagen desde la app.
// Esta URL sí se usa para leer la imagen desde el navegador o desde React Native.
export const PROFILE_IMAGE_PUBLIC_URL = 'https://casalai.alwaysdata.net/assets/img/uploads/';

// Ruta real del servidor donde PHP debe guardar la imagen del comprobante de pago.
// IMPORTANTE: esto NO es una URL para el navegador; es una ruta del sistema de archivos.
export const PAGOS_IMAGE_STORAGE_PATH = `${PROJECT_SERVER_ROOT}/assets/img/uploads/comprobantes/`;

// URL pública para acceder a la imagen del comprobante desde la app.
export const PAGOS_IMAGE_PUBLIC_URL = 'https://casalai.alwaysdata.net/assets/img/uploads/comprobantes/';

// Normaliza una ruta de imagen para que funcione tanto con rutas relativas del servidor,
// rutas absolutas del sistema de archivos, como con URLs completas ya resueltas.
export const buildProfileImageUrl = (imageValue) => {
  if (!imageValue) {
    return null;
  }

  const value = String(imageValue).trim();

  if (!value) {
    return null;
  }

  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) {
    return value;
  }

  const normalized = value.replace(/\\/g, '/');
  // PHP puede devolver una ruta absoluta, una ruta relativa o solo el nombre.
  // Para mostrarla no reutilizamos las carpetas del servidor: solo necesitamos el archivo.
  const fileName = normalized.split('/').pop();

  if (!fileName) {
    return null;
  }

  return `${PROFILE_IMAGE_PUBLIC_URL.replace(/\/+$/, '')}/${encodeURIComponent(fileName)}`;
};

export const buildPaymentImageUrl = (imageValue) => {
  if (!imageValue) {
    return null;
  }

  const value = String(imageValue).trim();

  if (!value) {
    return null;
  }

  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) {
    return value;
  }

  const normalized = value.replace(/\\/g, '/');
  const fileName = normalized.split('/').pop();

  if (!fileName) {
    return null;
  }

  return `${PAGOS_IMAGE_PUBLIC_URL.replace(/\/+$/, '')}/${encodeURIComponent(fileName)}`;
};

// Configuración adicional de la API
export const API_CONFIG = {
  baseURL: API_BASE_URL,
  timeout: 10000, // 10 segundos
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Cache-Control': 'no-cache',
  },
};

/**
 * Función para actualizar la URL base de la API dinámicamente
 * Útil si necesitas cambiar la URL en tiempo de ejecución
 */
export const setAPIBaseURL = (newBaseURL) => {
  API_CONFIG.baseURL = newBaseURL;
  // También actualizar la configuración en api.js
  if (typeof require !== 'undefined') {
    try {
      const api = require('../utils/api');
      if (api.setAPIBaseURL) {
        api.setAPIBaseURL(newBaseURL);
      }
    } catch (error) {
      console.warn('No se pudo actualizar la URL base en api.js:', error);
    }
  }
};

export default API_CONFIG;
