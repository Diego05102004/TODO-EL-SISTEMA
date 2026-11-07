/**
 * Configuración de URLs para la API móvil
 * Centraliza las URLs base para desarrollo y producción
 * 
 * Para emuladores Android: usar 10.0.2.2
 * Para dispositivos físicos: usar la IP local del PC (ej: 192.168.1.20)
 * Para producción: usar HTTPS
 */

const ENV = process.env.NODE_ENV || 'development';

const config = {
  development: {
    // Para navegador en el mismo PC
    BASE_URL: 'https://localhost/proyecto-casalai-ca',
    API_BASE_URL: 'https://localhost/proyecto-casalai-ca/api',
    ASSETS_URL: 'https://localhost/proyecto-casalai-ca/assets',
    IMAGES_URL: 'https://localhost/proyecto-casalai-ca/assets/img',
  },
  emulator: {
    // Para emulador Android (10.0.2.2 apunta al host)
    BASE_URL: 'https://10.0.2.2/proyecto-casalai-ca',
    API_BASE_URL: 'https://10.0.2.2/proyecto-casalai-ca/api',
    ASSETS_URL: 'https://10.0.2.2/proyecto-casalai-ca/assets',
    IMAGES_URL: 'https://10.0.2.2/proyecto-casalai-ca/assets/img',
  },
  device: {
    // Para dispositivo físico - REEMPLAZAR con tu IP local
    BASE_URL: 'https://192.168.1.20/proyecto-casalai-ca',
    API_BASE_URL: 'https://192.168.1.20/proyecto-casalai-ca/api',
    ASSETS_URL: 'https://192.168.1.20/proyecto-casalai-ca/assets',
    IMAGES_URL: 'https://192.168.1.20/proyecto-casalai-ca/assets/img',
  },
  production: {
    BASE_URL: 'https://casalai.alwaysdata.net',
    API_BASE_URL: 'https://casalai.alwaysdata.net/api',
    ASSETS_URL: 'https://casalai.alwaysdata.net/assets',
    IMAGES_URL: 'https://casalai.alwaysdata.net/assets/img',
  }
};

// Configuración actual basada en el entorno
const currentConfig = config[ENV] || config.development;

/**
 * Obtiene la URL base configurada
 * @returns {string} URL base
 */
export const getBaseUrl = () => currentConfig.BASE_URL;

/**
 * Obtiene la URL base de la API
 * @returns {string} URL base de la API
 */
export const getApiBaseUrl = () => currentConfig.API_BASE_URL;

/**
 * Obtiene la URL de assets
 * @returns {string} URL de assets
 */
export const getAssetsUrl = () => currentConfig.ASSETS_URL;

/**
 * Obtiene la URL de imágenes
 * @returns {string} URL de imágenes
 */
export const getImagesUrl = () => currentConfig.IMAGES_URL;

/**
 * Construye URL completa para una imagen
 * @param {string} imagePath - Ruta relativa de la imagen
 * @returns {string|null} URL completa de la imagen
 */
export const getImageUrl = (imagePath) => {
  if (!imagePath) return null;
  
  let normalizedPath = imagePath.replace(/\\/g, '/').replace(/^\/+/, '');
  if (/^https?:\/\//i.test(normalizedPath)) return normalizedPath;
  if (normalizedPath.startsWith('assets/img/')) {
    return `${getImagesUrl()}/${normalizedPath.slice('assets/img/'.length)}`;
  }
  if (normalizedPath.startsWith('assets/')) {
    return `${getBaseUrl()}/${normalizedPath}`;
  }
  if (normalizedPath.startsWith('img/')) {
    return `${getAssetsUrl()}/${normalizedPath}`;
  }
  if (!normalizedPath.startsWith('productos/')) {
    normalizedPath = `productos/${normalizedPath}`;
  }

  return `${getImagesUrl()}/${normalizedPath}`;
};

/**
 * Obtiene el entorno actual
 * @returns {string} Entorno (development|emulator|device|production)
 */
export const getEnvironment = () => ENV;

/**
 * Actualiza la configuración en tiempo de ejecución
 * Útil para cambiar entre localhost y IP del dispositivo
 * @param {string} env - Entorno a usar
 */
export const setEnvironment = (env) => {
  if (config[env]) {
    Object.assign(currentConfig, config[env]);
  }
};

export default currentConfig;
