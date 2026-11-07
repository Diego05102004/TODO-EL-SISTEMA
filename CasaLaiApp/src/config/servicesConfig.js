const SERVICES_CONFIG = {
  ANALITICA_PREDICTIVA: {
    URL: process.env.EXPO_PUBLIC_ANALITICA_PREDICTIVA_URL || '',
  },
  AUDITOR_RECEPCION: {
    URL: process.env.EXPO_PUBLIC_AUDITOR_RECEPCION_URL || '',
  },
};

export { SERVICES_CONFIG };

export const getServiceHeaders = (serviceName) => {
  if (!Object.prototype.hasOwnProperty.call(SERVICES_CONFIG, serviceName)) {
    throw new Error(`[SERVICES] Servicio no encontrado: ${serviceName}`);
  }

  throw new Error(
    `Las credenciales de ${serviceName} deben permanecer en el servidor; implemente un proxy de API antes de usar este servicio desde la app.`
  );
};

export const getServiceURL = (serviceName) => {
  const service = SERVICES_CONFIG[serviceName];
  if (!service) {
    throw new Error(`[SERVICES] Servicio no encontrado: ${serviceName}`);
  }
  if (!service.URL) {
    throw new Error(`[SERVICES] Falta configurar la URL pública de ${serviceName}.`);
  }

  return service.URL;
};

export default SERVICES_CONFIG;
