/**
 * Comunicador central de la aplicacion movil con los archivos de la API.
 * Los archivos PHP permanecen en el backend; este modulo solo realiza HTTP.
 */

import { secureGet, securePost, securePut, secureDelete, API_SCHEMAS } from './api';

const OPERATIONS = {
  login: { method: 'POST', schema: API_SCHEMAS.login, useFunctionParam: false, encryptSensitive: true },
  registro: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  registrar_cliente: { method: 'POST', useFunctionParam: false, timeout: 120000, encryptSensitive: true },
  pago_ingresar: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  facturaanular: { method: 'POST', useFunctionParam: true, encryptSensitive: false },
  default: { method: 'GET', schema: API_SCHEMAS.getProducts, useFunctionParam: true, encryptSensitive: false },
  por_id: { method: 'GET', schema: API_SCHEMAS.getProducts, useFunctionParam: true, encryptSensitive: false },
  por_categoria: { method: 'GET', schema: API_SCHEMAS.getProducts, useFunctionParam: true, encryptSensitive: false },
  detallado: { method: 'GET', schema: API_SCHEMAS.getProducts, useFunctionParam: true, encryptSensitive: false },
  cambiar_personal: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  editar_correo: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  cambiar_correo: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  editar_email: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  cambiar_email: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  cambiar_password: { method: 'POST', useFunctionParam: false, encryptSensitive: true },
  cambiar_avatar: { method: 'POST', useFunctionParam: false, encryptSensitive: false },
};

const normalizeApiFile = (apiFile) => {
  if (typeof apiFile !== 'string' || !apiFile.trim()) {
    throw new Error('Debe indicar el nombre del archivo de la API');
  }

  const fileName = apiFile.trim().split('/').pop();
  return fileName.endsWith('.php') ? fileName : `${fileName}.php`;
};

const normalizeFunctionName = (functionName) => {
  if (typeof functionName !== 'string' || !functionName.trim()) {
    throw new Error('Debe indicar el nombre de la funcion de la API');
  }

  return functionName.trim().toLowerCase();
};

const saveResult = (resultHolder, response) => {
  if (typeof resultHolder === 'function') {
    resultHolder(response);
  } else if (resultHolder && typeof resultHolder === 'object') {
    if (Object.prototype.hasOwnProperty.call(resultHolder, 'current')) {
      resultHolder.current = response;
    } else {
      resultHolder.value = response;
    }
  }
};

class ComunicadorAPI {
  async ConsultarMetodo(nombreArchivoApi, nombreFuncion) {
    const apiFile = normalizeApiFile(nombreArchivoApi);
    const functionName = normalizeFunctionName(nombreFuncion);
    const operation = OPERATIONS[functionName] || { method: 'GET' };
    const endpoint = `/${apiFile}`;

    return async (datos = {}) => {
      const requestData = operation.useFunctionParam
        ? { ...datos, funcion: functionName }
        : datos;

      console.log('[ComunicadorAPI] Invocando metodo:', {
        apiFile,
        functionName,
        method: operation.method,
      });

      switch (operation.method) {
        case 'POST':
          return securePost(endpoint, requestData, operation.schema, operation.timeout, operation.encryptSensitive);
        case 'PUT':
          return securePut(endpoint, requestData, operation.schema, operation.encryptSensitive);
        case 'DELETE':
          return secureDelete(endpoint, requestData, operation.schema);
        case 'GET':
        default:
          return secureGet(endpoint, requestData, operation.schema);
      }
    };
  }

  async InvocarMetodo(nombreArchivoApi, nombreFuncion, datos = {}, resultado = null) {
    const metodo = await this.ConsultarMetodo(nombreArchivoApi, nombreFuncion);
    const response = await metodo(datos);
    saveResult(resultado, response);
    return response;
  }
}

export const comunicadorApi = new ComunicadorAPI();
export const ConsultarMetodo = comunicadorApi.ConsultarMetodo.bind(comunicadorApi);
export const InvocarMetodo = comunicadorApi.InvocarMetodo.bind(comunicadorApi);
export default comunicadorApi;
