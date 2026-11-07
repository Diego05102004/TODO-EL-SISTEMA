import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

// Configuración JWT (debe coincidir con el servidor)
// Para desarrollo local: http://localhost/proyecto-casalai-ca/
// Para producción Always Data: https://casalai.alwaysdata.net/
// NOTA: El servidor está enviando iss: "casalai-api" - Debes actualizar el servidor
// para que use "https://casalai.alwaysdata.net/" o cambiar esto a "casalai-api"
const DEFAULT_ISSUER = 'casalai-api'; // Coincide con lo que envía el servidor actualmente
const DEFAULT_AUDIENCE = 'proyecto-casalai-ca';
// Cambia este valor para definir el tiempo de la sesion
const SESSION_EXPIRATION_SECONDS = 6000;
// Cambia este valor para definir el tiempo de advertencia (en segundos)
const WARNING_THRESHOLD_SECONDS = 30;
// Clave para almacenar la extensión de sesión
const SESSION_EXTENSION_STORAGE_KEY = 'sessionExtensionSeconds';

const getSecureToken = () => (
  Platform.OS === 'web'
    ? AsyncStorage.getItem('userToken')
    : SecureStore.getItemAsync('userToken')
);

const setSecureToken = (token) => (
  Platform.OS === 'web'
    ? AsyncStorage.setItem('userToken', token)
    : SecureStore.setItemAsync('userToken', token)
);

const deleteSecureToken = () => (
  Platform.OS === 'web'
    ? AsyncStorage.removeItem('userToken')
    : SecureStore.deleteItemAsync('userToken')
);

const normalizeTokenValue = (value) => {
  if (typeof value !== 'string') {
    return value;
  }

  const trimmed = value.trim();
  if (!trimmed) {
    return trimmed;
  }

  try {
    return decodeURIComponent(trimmed.replace(/\+/g, '%20'));
  } catch (error) {
    return trimmed;
  }
};

const buildDebugInfo = (token, payload, reason, options = {}) => ({
  tokenPresent: Boolean(token),
  tokenLength: token ? token.length : 0,
  tokenParts: token ? token.split('.').length : 0,
  reason,
  expectedIssuer: options.issuer || DEFAULT_ISSUER,
  expectedAudience: options.audience || DEFAULT_AUDIENCE,
  receivedIssuer: payload?.iss || null,
  receivedAudience: payload?.aud || null,
  exp: payload?.exp || null,
  iat: payload?.iat || null,
});

const base64UrlDecode = (value) => {
  if (!value) {
    return null;
  }

  try {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized.padEnd(normalized.length + ((4 - (normalized.length % 4)) % 4), '=');

    if (typeof globalThis.atob === 'function') {
      const decoded = globalThis.atob(padded);
      return decodeURIComponent(
        decoded
          .split('')
          .map((char) => `%${`00${char.charCodeAt(0).toString(16)}`.slice(-2)}`)
          .join('')
      );
    }

    if (typeof Buffer !== 'undefined') {
      return Buffer.from(padded, 'base64').toString('utf8');
    }

    return null;
  } catch (error) {
    console.warn('No se pudo decodificar el token JWT:', error);
    return null;
  }
};

export const decodeJwtPayload = (token) => {
  if (!token || typeof token !== 'string') {
    return { payload: null, debug: buildDebugInfo(token, null, 'token missing') };
  }

  const trimmedToken = token.trim();
  const normalizedToken = trimmedToken.startsWith('Bearer ')
    ? trimmedToken.replace(/^Bearer\s+/i, '').trim()
    : trimmedToken;
  const parts = normalizedToken.split('.');

  if (parts.length < 2) {
    return {
      payload: null,
      debug: buildDebugInfo(normalizedToken, null, 'token malformed'),
    };
  }

  const payload = base64UrlDecode(parts[1]);
  if (!payload) {
    return {
      payload: null,
      debug: buildDebugInfo(normalizedToken, null, 'payload decode failed'),
    };
  }

  try {
    return {
      payload: JSON.parse(payload),
      debug: buildDebugInfo(normalizedToken, JSON.parse(payload), 'payload decoded'),
    };
  } catch (error) {
    console.warn('[JWT][debug] El payload del JWT no es un JSON válido:', error);
    return {
      payload: null,
      debug: buildDebugInfo(normalizedToken, null, 'payload parse failed'),
    };
  }
};

export const getJwtStatus = (token, options = {}) => {
  if (!token) {
    return {
      status: 'missing',
      payload: null,
      expiresIn: null,
      expiresAt: null,
      debug: buildDebugInfo(token, null, 'token missing', options),
    };
  }

  const { payload, debug: decodeDebug } = decodeJwtPayload(token);
  const issuer = options.issuer || DEFAULT_ISSUER;
  const audience = options.audience || DEFAULT_AUDIENCE;
  const warningThreshold = options.warningThresholdSeconds ?? WARNING_THRESHOLD_SECONDS;
  const extensionSeconds = Number(options.extensionSeconds || 0);

  if (!payload) {
    const debug = {
      ...decodeDebug,
      expectedIssuer: issuer,
      expectedAudience: audience,
      reason: decodeDebug?.reason || 'payload missing',
    };

    console.log('[JWT][debug] validación fallida', debug);

    return {
      status: 'warning',
      payload: null,
      expiresIn: null,
      expiresAt: null,
      debug,
    };
  }

  const normalizedIssuer = normalizeTokenValue(payload.iss);
  const normalizedExpectedIssuer = normalizeTokenValue(issuer);
  const normalizedAudience = normalizeTokenValue(payload.aud);
  const normalizedExpectedAudience = normalizeTokenValue(audience);

  const issuerMismatch = Boolean(payload.iss && normalizedIssuer && normalizedExpectedIssuer && normalizedIssuer !== normalizedExpectedIssuer);
  const audienceMismatch = Boolean(payload.aud && normalizedAudience && normalizedExpectedAudience && normalizedAudience !== normalizedExpectedAudience);

  const debug = {
    ...decodeDebug,
    expectedIssuer: issuer,
    expectedAudience: audience,
    receivedIssuer: payload.iss || null,
    receivedAudience: payload.aud || null,
    issuerMismatch,
    audienceMismatch,
  };

  if (issuerMismatch || audienceMismatch) {
    console.warn('[JWT][debug] coincidencia de issuer/audience diferente, se continuará con la validación como advertencia', debug);
  }

  const now = Math.floor(Date.now() / 1000);
  const expiresAt = payload.exp !== undefined && payload.exp !== null ? Number(payload.exp) : null;
  const issuedAt = payload.iat !== undefined && payload.iat !== null ? Number(payload.iat) : now;
  const fallbackExpiration = issuedAt + SESSION_EXPIRATION_SECONDS;
  const effectiveExpiresAt = expiresAt !== null && !Number.isNaN(expiresAt)
    ? Math.min(expiresAt, fallbackExpiration)
    : fallbackExpiration;
  const adjustedExpiresAt = effectiveExpiresAt + extensionSeconds;
  const expiresIn = adjustedExpiresAt - now;

  if (!expiresAt || Number.isNaN(expiresAt)) {
    console.warn('[JWT][debug] no se encontró exp válido', debug);
    return {
      status: 'warning',
      payload,
      expiresIn: null,
      expiresAt: null,
      debug: { ...debug, reason: 'missing exp' },
    };
  }

  if (expiresIn <= 0) {
    const result = {
      status: 'expired',
      payload: null,
      expiresIn: 0,
      expiresAt: adjustedExpiresAt,
      debug: { ...debug, reason: 'token expired', expiresIn },
    };
    console.log('[JWT][debug] token expirado', result.debug);
    return result;
  }

  if (expiresIn <= warningThreshold) {
    const result = {
      status: 'warning',
      payload,
      expiresIn,
      expiresAt: adjustedExpiresAt,
      debug: { ...debug, reason: 'token near expiry', expiresIn },
    };
    console.log('[JWT][debug] token próximo a expirar', result.debug);
    return result;
  }

  const result = {
    status: 'valid',
    payload,
    expiresIn,
    expiresAt: adjustedExpiresAt,
    debug: { ...debug, reason: 'token valid', expiresIn },
  };
  console.log('[JWT][debug] token válido', result.debug);
  return result;
};

export const getStoredToken = async () => {
  try {
    const secureToken = await getSecureToken();
    if (secureToken) {
      return secureToken;
    }

    const legacyToken = await AsyncStorage.getItem('userToken');
    if (!legacyToken) {
      return null;
    }

    await setSecureToken(legacyToken);
    if (Platform.OS !== 'web') {
      await AsyncStorage.removeItem('userToken');
    }
    return legacyToken;
  } catch (error) {
    console.warn('No se pudo leer el token almacenado:', error);
    throw error;
  }
};

export const getStoredUserData = async () => {
  try {
    const storedUser = await AsyncStorage.getItem('userData');
    return storedUser ? JSON.parse(storedUser) : null;
  } catch (error) {
    console.warn('No se pudo leer la información de usuario almacenada:', error);
    return null;
  }
};

export const getUserIdentity = (user = {}, payload = null) => {
  const source = {
    ...(user || {}),
    ...(payload || {}),
  };

  const cedula =
    source.cedula ??
    source.cedula_usuario ??
    source.documento ??
    source.dni ??
    source.ci ??
    source.id_documento ??
    null;

  return {
    cedula,
    documento: source.documento ?? cedula ?? null,
    email: source.email ?? source.correo ?? source.mail ?? null,
    nombre: source.nombre ?? source.name ?? source.nombre_usuario ?? source.username ?? null,
    username: source.username ?? source.nombre_usuario ?? source.user_name ?? null,
    id: source.id ?? source.id_usuario ?? source.user_id ?? null,
  };
};

export const getStoredSessionExtensionSeconds = async () => {
  try {
    const storedValue = await AsyncStorage.getItem(SESSION_EXTENSION_STORAGE_KEY);
    return storedValue ? Number(storedValue) : 0;
  } catch (error) {
    console.warn('No se pudo leer la extensión de sesión:', error);
    return 0;
  }
};

export const extendStoredSession = async (seconds = 6000) => {
  try {
    const currentValue = await getStoredSessionExtensionSeconds();
    const nextValue = Math.max(0, currentValue + seconds);
    await AsyncStorage.setItem(SESSION_EXTENSION_STORAGE_KEY, String(nextValue));
    return nextValue;
  } catch (error) {
    console.warn('No se pudo extender la sesión:', error);
    return 0;
  }
};

export const persistAuthSession = async ({ token, user, payload }) => {
  try {
    const resolvedPayload = payload || (token ? decodeJwtPayload(token).payload : null);
    const resolvedUser = user
      ? {
          ...user,
          ...getUserIdentity(user, resolvedPayload),
          cedula: user?.cedula ?? user?.cedula_usuario ?? user?.documento ?? resolvedPayload?.cedula ?? resolvedPayload?.cedula_usuario ?? resolvedPayload?.documento ?? null,
          documento: user?.documento ?? user?.cedula ?? user?.cedula_usuario ?? resolvedPayload?.documento ?? resolvedPayload?.cedula ?? resolvedPayload?.cedula_usuario ?? null,
          email: user?.email ?? user?.correo ?? user?.mail ?? resolvedPayload?.email ?? resolvedPayload?.correo ?? null,
        }
      : user;

    if (token) {
      await setSecureToken(token);
      if (Platform.OS !== 'web') {
        await AsyncStorage.removeItem('userToken');
      }
    }

    if (resolvedUser) {
      await AsyncStorage.setItem('userData', JSON.stringify(resolvedUser));
    }

    if (resolvedPayload) {
      await AsyncStorage.setItem('jwtPayload', JSON.stringify(resolvedPayload));
    }

    await AsyncStorage.setItem(SESSION_EXTENSION_STORAGE_KEY, '0');
  } catch (error) {
    console.warn('No se pudo guardar la sesión autenticada:', error);
    throw error;
  }
};

export const clearStoredAuthSession = async () => {
  try {
    await deleteSecureToken();
    if (Platform.OS !== 'web') {
      await AsyncStorage.removeItem('userToken');
    }
    await AsyncStorage.removeItem('userData');
    await AsyncStorage.removeItem('jwtPayload');
    await AsyncStorage.removeItem(SESSION_EXTENSION_STORAGE_KEY);
  } catch (error) {
    console.warn('No se pudo limpiar la sesión almacenada:', error);
    throw error;
  }
};

export const validateStoredSession = async (options = {}) => {
  const token = await getStoredToken();
  const storedUser = await getStoredUserData();
  const storedPayload = await AsyncStorage.getItem('jwtPayload');
  const payload = storedPayload ? JSON.parse(storedPayload) : (token ? decodeJwtPayload(token).payload : null);
  const extensionSeconds = await getStoredSessionExtensionSeconds();
  const sessionStatus = getJwtStatus(token, { ...options, extensionSeconds });
  const user = storedUser
    ? {
        ...storedUser,
        ...getUserIdentity(storedUser, sessionStatus.payload || payload),
        cedula: storedUser?.cedula ?? storedUser?.cedula_usuario ?? storedUser?.documento ?? sessionStatus.payload?.cedula ?? sessionStatus.payload?.cedula_usuario ?? sessionStatus.payload?.documento ?? payload?.cedula ?? payload?.cedula_usuario ?? payload?.documento ?? null,
        documento: storedUser?.documento ?? storedUser?.cedula ?? storedUser?.cedula_usuario ?? sessionStatus.payload?.documento ?? sessionStatus.payload?.cedula ?? sessionStatus.payload?.cedula_usuario ?? payload?.documento ?? payload?.cedula ?? payload?.cedula_usuario ?? null,
      }
    : null;

  const isAuthenticated = Boolean(token && user && ['valid', 'warning'].includes(sessionStatus.status));

  return {
    isAuthenticated,
    token,
    user,
    tokenStatus: sessionStatus.status,
    expiresIn: sessionStatus.expiresIn,
    expiresAt: sessionStatus.expiresAt,
    jwtPayload: sessionStatus.payload || payload,
    debug: sessionStatus.debug,
  };
};

export const refreshStoredSession = async (options = {}) => {
  const token = await getStoredToken();
  const storedUser = await getStoredUserData();
  const storedPayload = await AsyncStorage.getItem('jwtPayload');
  const payload = storedPayload ? JSON.parse(storedPayload) : (token ? decodeJwtPayload(token).payload : null);
  const extensionSeconds = await getStoredSessionExtensionSeconds();
  const sessionStatus = getJwtStatus(token, { ...options, extensionSeconds });
  const user = storedUser
    ? {
        ...storedUser,
        ...getUserIdentity(storedUser, sessionStatus.payload || payload),
        cedula: storedUser?.cedula ?? storedUser?.cedula_usuario ?? storedUser?.documento ?? sessionStatus.payload?.cedula ?? sessionStatus.payload?.cedula_usuario ?? sessionStatus.payload?.documento ?? payload?.cedula ?? payload?.cedula_usuario ?? payload?.documento ?? null,
        documento: storedUser?.documento ?? storedUser?.cedula ?? storedUser?.cedula_usuario ?? sessionStatus.payload?.documento ?? sessionStatus.payload?.cedula ?? sessionStatus.payload?.cedula_usuario ?? payload?.documento ?? payload?.cedula ?? payload?.cedula_usuario ?? null,
      }
    : null;

  if (!token || !user || !['valid', 'warning'].includes(sessionStatus.status)) {
    await clearStoredAuthSession();
    return {
      isAuthenticated: false,
      token: null,
      user: null,
      tokenStatus: sessionStatus.status,
      expiresIn: sessionStatus.expiresIn,
      expiresAt: sessionStatus.expiresAt,
      jwtPayload: sessionStatus.payload || payload,
      debug: sessionStatus.debug,
    };
  }

  return {
    isAuthenticated: true,
    token,
    user,
    tokenStatus: sessionStatus.status,
    expiresIn: sessionStatus.expiresIn,
    expiresAt: sessionStatus.expiresAt,
    jwtPayload: sessionStatus.payload || payload,
    debug: sessionStatus.debug,
  };
};

export default {
  decodeJwtPayload,
  getJwtStatus,
  getStoredToken,
  getStoredUserData,
  persistAuthSession,
  clearStoredAuthSession,
  validateStoredSession,
  refreshStoredSession,
};
