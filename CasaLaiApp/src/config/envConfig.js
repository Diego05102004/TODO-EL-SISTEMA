/**
 * Configuración de entorno para desarrollo y producción
 * Cambia IS_PRODUCTION a true para desplegar en Always Data
 */

export const ENV_CONFIG = {
  // Cambiar a true para producción con Always Data
  IS_PRODUCTION: false,

  // URLs de la API
  API_URLS: {
    development: 'http://TODO-EL-SISTEMA/proyecto-casalai-ca/api',
    production: 'https://casalai.alwaysdata.net/api',
  },

  // Issuer JWT
  // NOTA: El servidor PHP está usando "casalai-api" como issuer
  // Debes actualizar el servidor para usar la URL completa o cambiar esto
  JWT_ISSUERS: {
    development: 'casalai-api',
    production: 'casalai-api',
  },

  // URLs de imágenes
  IMAGE_URLS: {
    development: 'http://TODO-EL-SISTEMA/proyecto-casalai-ca',
    production: 'https://casalai.alwaysdata.net',
  },
};

/**
 * Obtiene la URL de la API según el entorno
 */
export const getApiUrl = () => {
  return ENV_CONFIG.IS_PRODUCTION
    ? ENV_CONFIG.API_URLS.production
    : ENV_CONFIG.API_URLS.development;
};

/**
 * Obtiene el issuer JWT según el entorno
 */
export const getJwtIssuer = () => {
  return ENV_CONFIG.IS_PRODUCTION
    ? ENV_CONFIG.JWT_ISSUERS.production
    : ENV_CONFIG.JWT_ISSUERS.development;
};

/**
 * Obtiene la URL base de imágenes según el entorno
 */
export const getImageBaseUrl = () => {
  return ENV_CONFIG.IS_PRODUCTION
    ? ENV_CONFIG.IMAGE_URLS.production
    : ENV_CONFIG.IMAGE_URLS.development;
};

export default ENV_CONFIG;
