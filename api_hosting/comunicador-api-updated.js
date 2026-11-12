/**
 * Comunicador central de la aplicacion movil con los archivos de la API.
 * Los archivos PHP permanecen en el backend; este modulo solo realiza HTTP.
 * 
 * ACTUALIZADO: Envía el parámetro 'funcion' solo para endpoints que usan RecibirPeticion()
 */

import { secureGet, securePost, securePut, secureDelete, API_SCHEMAS } from './api';

const OPERATIONS = {
  login: { method: 'POST', schema: API_SCHEMAS.login, useFunctionParam: false },
  registro: { method: 'POST', useFunctionParam: false },
  productos: { method: 'GET', schema: API_SCHEMAS.getProducts, useFunctionParam: true },
  productos_por_id: { method: 'GET', useFunctionParam: true },
  productos_por_categoria: { method: 'GET', useFunctionParam: true },
  productos_detallado: { method: 'GET', useFunctionParam: true },
  facturas: { method: 'GET', useFunctionParam: true },
  factura_ingresar: { method: 'POST', useFunctionParam: true },
  factura_descargar: { method: 'GET', useFunctionParam: true },
  cuentas: { method: 'GET', useFunctionParam: true },
  pagos: { method: 'GET', useFunctionParam: true },
  pago_ingresar: { method: 'POST', useFunctionParam: true },
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
    const operation = OPERATIONS[functionName] || { method: 'GET', useFunctionParam: true };
    const endpoint = `/${apiFile}`;

    return async (datos = {}) => {
      // Solo agregar 'funcion' si el endpoint usa RecibirPeticion()
      const datosConFuncion = operation.useFunctionParam 
        ? { ...datos, funcion: functionName }
        : datos;

      switch (operation.method) {
        case 'POST':
          return securePost(endpoint, datosConFuncion, operation.schema);
        case 'PUT':
          return securePut(endpoint, datosConFuncion, operation.schema);
        case 'DELETE':
          return secureDelete(endpoint, datosConFuncion, operation.schema);
        case 'GET':
        default:
          return secureGet(endpoint, datosConFuncion, operation.schema);
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
