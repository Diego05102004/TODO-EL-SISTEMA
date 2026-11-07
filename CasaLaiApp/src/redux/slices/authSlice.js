import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { validateEmail, validatePassword } from '../../utils/validation';
import { sanitizeForAPI } from '../../sanitization';
import { InvocarMetodo } from '../../utils/comunicadorApi';
import { secureGet } from '../../utils/api';
import {
  clearStoredAuthSession,
  decodeJwtPayload,
  extendStoredSession,
  persistAuthSession,
  refreshStoredSession,
  validateStoredSession,
} from '../../utils/jwt';

let sessionMonitorTimer = null;

const startSessionMonitor = (dispatch) => {
  if (sessionMonitorTimer) {
    clearInterval(sessionMonitorTimer);
  }

  sessionMonitorTimer = setInterval(async () => {
    try {
      const session = await validateStoredSession();
      if (session.tokenStatus === 'warning') {
        console.log('[JWT] Sesión próxima a expirar:', session.expiresIn, 'segundos');
        dispatch({ type: 'auth/setSessionWarning', payload: { expiresIn: session.expiresIn } });
      } else if (session.tokenStatus === 'expired' || session.tokenStatus === 'invalid') {
        console.log('[JWT] Sesión expirada o inválida:', session.tokenStatus);
        dispatch(logoutUser());
      }
    } catch (error) {
      console.warn('[JWT] Error al monitorear sesión:', error);
    }
  }, 10000);
};

// Configuración de rate limiting
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_TIME = 1 * 60 * 1000; // 15 minutos en milisegundos

// Función para obtener intentos de login fallidos
const getLoginAttempts = async () => {
  try {
    const attempts = await AsyncStorage.getItem('loginAttempts');
    const timestamp = await AsyncStorage.getItem('loginAttemptsTimestamp');
    return {
      count: attempts ? parseInt(attempts) : 0,
      timestamp: timestamp ? parseInt(timestamp) : null
    };
  } catch (error) {
    return { count: 0, timestamp: null };
  }
};

// Función para incrementar intentos de login fallidos
const incrementLoginAttempts = async () => {
  try {
    const { count } = await getLoginAttempts();
    await AsyncStorage.setItem('loginAttempts', (count + 1).toString());
    await AsyncStorage.setItem('loginAttemptsTimestamp', Date.now().toString());
  } catch (error) {
    console.error('Error incrementing login attempts:', error);
  }
};

// Función para resetear intentos de login
const resetLoginAttempts = async () => {
  try {
    await AsyncStorage.removeItem('loginAttempts');
    await AsyncStorage.removeItem('loginAttemptsTimestamp');
  } catch (error) {
    console.error('Error resetting login attempts:', error);
  }
};

// Función para verificar si el usuario está bloqueado
const isUserLockedOut = async () => {
  try {
    const { count, timestamp } = await getLoginAttempts();
    
    if (count >= MAX_LOGIN_ATTEMPTS && timestamp) {
      const timeSinceLastAttempt = Date.now() - timestamp;
      if (timeSinceLastAttempt < LOCKOUT_TIME) {
        const remainingTime = Math.ceil((LOCKOUT_TIME - timeSinceLastAttempt) / 60000); // minutos restantes
        return { locked: true, remainingTime };
      } else {
        // Resetear si ya pasó el tiempo de bloqueo
        await resetLoginAttempts();
      }
    }
    
    return { locked: false, remainingTime: 0 };
  } catch (error) {
    console.error('Error checking lockout status:', error);
    return { locked: false, remainingTime: 0 };
  }
};

const pickFirstDefined = (...values) => {
  for (const value of values) {
    if (value !== undefined && value !== null && value !== '') {
      return value;
    }
  }

  return '';
};

export const loginUser = createAsyncThunk(
  'auth/login',
  async (userData, { rejectWithValue }) => {
    try {
      // Verificar si el usuario está bloqueado por demasiados intentos
      const { locked, remainingTime } = await isUserLockedOut();
      if (locked) {
        return rejectWithValue(
          `Demasiados intentos fallidos. Intenta nuevamente en ${remainingTime} minutos.`
        );
      }

      // Determinar si es email o username
      const isEmail = userData.email && userData.email.includes('@');
      const identifier = isEmail ? userData.email : (userData.username || userData.email);

      // Validar identifier
      if (!identifier) {
        await incrementLoginAttempts();
        return rejectWithValue('El usuario o correo es requerido');
      }

      // Si es email, validar formato
      if (isEmail && !validateEmail(userData.email)) {
        await incrementLoginAttempts();
        return rejectWithValue('Email inválido');
      }

      // Si es username, validar formato
      if (!isEmail && !/^[a-zA-Z0-9_]+$/.test(identifier)) {
        await incrementLoginAttempts();
        return rejectWithValue('Usuario inválido. Solo letras, números y guiones bajos');
      }

      // Validar contraseña (si se proporciona)
      if (userData.password && !validatePassword(userData.password)) {
        await incrementLoginAttempts();
        return rejectWithValue(
          'Contraseña inválida. Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número.'
        );
      }

      // Hacer llamada real a la API con el campo correcto
      const apiData = isEmail
        ? {
            email: userData.email,
            password: userData.password,
            recaptcha_token: userData.recaptcha_token,
          }
        : {
            username: userData.username,
            password: userData.password,
            recaptcha_token: userData.recaptcha_token,
          };

      const response = await InvocarMetodo('login.php', 'login', apiData);

      if (response.status === 'success') {
        const rawUser = response.data?.user || response.data?.usuario || response.data?.cliente || {};
        const token = pickFirstDefined(response.data?.token, response.token, '');
        const payload = response.data?.payload || (token ? decodeJwtPayload(token).payload : null);

        const baseUser = {
          id: pickFirstDefined(rawUser.id_usuario, rawUser.id, payload?.id_usuario, payload?.sub, null),
          email: pickFirstDefined(
            rawUser.correo,
            rawUser.email,
            userData.email,
            userData.username,
            payload?.email,
            payload?.correo,
            ''
          ),
          correo: pickFirstDefined(
            rawUser.correo,
            rawUser.email,
            userData.email,
            userData.username,
            payload?.email,
            payload?.correo,
            ''
          ),
          name: pickFirstDefined(
            rawUser.nombre,
            rawUser.name,
            rawUser.username,
            rawUser.nombre_usuario,
            payload?.nombre,
            ''
          ),
          username: pickFirstDefined(
            rawUser.username,
            rawUser.nombre_usuario,
            userData.username,
            userData.email,
            payload?.username,
            ''
          ),
          role: pickFirstDefined(rawUser.nombre_rol, rawUser.role, payload?.rol, payload?.role, ''),
          cedula: pickFirstDefined(
            rawUser.cedula,
            rawUser.cedula_usuario,
            rawUser.documento,
            payload?.cedula,
            payload?.cedula_usuario,
            payload?.documento,
            ''
          ),
          documento: pickFirstDefined(
            rawUser.documento,
            rawUser.cedula,
            rawUser.cedula_usuario,
            payload?.documento,
            payload?.cedula,
            payload?.cedula_usuario,
            ''
          ),
          token,
        };

        let completeUser = { ...baseUser };
        const userIdForProfile = pickFirstDefined(
          baseUser.id,
          rawUser.id_usuario,
          rawUser.id,
          payload?.id_usuario,
          payload?.sub,
          null
        );

        if (userIdForProfile) {
          try {
            const profileResponse = await secureGet('/perfil.php', { id_usuario: userIdForProfile });
            const profileData = profileResponse?.data?.usuario || profileResponse?.data?.user || profileResponse?.data || {};

            if (profileData && Object.keys(profileData).length > 0) {
              completeUser = {
                ...completeUser,
                id: profileData.id_usuario ?? completeUser.id,
                id_usuario: profileData.id_usuario ?? userIdForProfile,
                username: pickFirstDefined(profileData.username, profileData.nombre_usuario, completeUser.username, ''),
                nombre_usuario: pickFirstDefined(profileData.username, profileData.nombre_usuario, completeUser.username, ''),
                nombres: pickFirstDefined(profileData.nombres, profileData.nombre, completeUser.name, ''),
                nombre: pickFirstDefined(profileData.nombres, profileData.nombre, completeUser.name, ''),
                apellidos: pickFirstDefined(profileData.apellidos, profileData.apellido, '', ''),
                apellido: pickFirstDefined(profileData.apellidos, profileData.apellido, '', ''),
                telefono: pickFirstDefined(profileData.telefono, profileData.phone, completeUser.telefono, ''),
                phone: pickFirstDefined(profileData.telefono, profileData.phone, completeUser.telefono, ''),
                correo: pickFirstDefined(profileData.correo, profileData.email, completeUser.correo, ''),
                email: pickFirstDefined(profileData.correo, profileData.email, completeUser.email, ''),
                cedula: pickFirstDefined(profileData.cedula, profileData.cedula_usuario, completeUser.cedula, ''),
                foto_perfil: pickFirstDefined(profileData.foto_perfil, profileData.avatar, completeUser.foto_perfil, null),
                avatar: pickFirstDefined(profileData.foto_perfil, profileData.avatar, completeUser.foto_perfil, null),
              };
            }
          } catch (profileError) {
            console.warn('[auth/login] No se pudo cargar perfil después del login:', profileError?.message || profileError);
          }
        }

        console.log('[auth/login] datos reconstruidos desde login y perfil:', {
          user: completeUser,
          payload,
          rawUser,
        });

        await persistAuthSession({
          token: completeUser.token,
          user: completeUser,
          payload,
        });

        // Resetear intentos de login exitoso
        await resetLoginAttempts();

        return {
          user: completeUser,
          isAuthenticated: true,
          apiResponse: response
        };
      } else {
        await incrementLoginAttempts();
        return rejectWithValue({
          message: response.message || 'Error en el login',
          apiResponse: response
        });
      }
    } catch (error) {
      await incrementLoginAttempts();
      console.error('Error en login:', error);
      console.error('Error details:', {
        message: error.message,
        status: error.status,
        data: error.data
      });
      return rejectWithValue({
        message: error.message || 'Error en el login',
        apiResponse: {
          status: error.status || 'error',
          message: error.message,
          data: error.data
        }
      });
    }
  }
);

export const checkAuth = createAsyncThunk(
  'auth/checkAuth',
  async (_, { rejectWithValue }) => {
    try {
      const session = await validateStoredSession();
      return {
        user: session.user,
        isAuthenticated: session.isAuthenticated,
        tokenStatus: session.tokenStatus,
        expiresIn: session.expiresIn,
        jwtPayload: session.jwtPayload,
      };
    } catch (error) {
      return rejectWithValue('Error al verificar autenticación');
    }
  }
);

export const logoutUser = createAsyncThunk(
  'auth/logout',
  async (_, { rejectWithValue }) => {
    try {
      await clearStoredAuthSession();
      return { user: null, isAuthenticated: false };
    } catch (error) {
      return rejectWithValue('Error al cerrar sesión');
    }
  }
);

export const refreshAuthSession = createAsyncThunk(
  'auth/refreshSession',
  async (_, { rejectWithValue }) => {
    try {
      const session = await refreshStoredSession();
      return {
        user: session.user,
        isAuthenticated: session.isAuthenticated,
        tokenStatus: session.tokenStatus,
        expiresIn: session.expiresIn,
        expiresAt: session.expiresAt,
        jwtPayload: session.jwtPayload,
      };
    } catch (error) {
      return rejectWithValue('Error al refrescar la sesión');
    }
  }
);

export const registerUser = createAsyncThunk(
  'auth/register',
  async (userData, { rejectWithValue }) => {
    try {
      // Sanitizar datos antes de procesar
      const sanitizedData = sanitizeForAPI(userData, {
        username: { type: 'text', maxLength: 50 },
        password: { type: 'sql' },
        nombres: { type: 'text', maxLength: 50 },
        apellidos: { type: 'text', maxLength: 50 },
        correo: { type: 'email' },
        telefono: { type: 'text', maxLength: 20 },
        cedula: { type: 'text', maxLength: 20 },
        direccion: { type: 'text', maxLength: 200 }
      });

      // Usar el mismo comunicador central que LoginScreen para garantizar el POST
      // al endpoint y mantener un único flujo de manejo de respuestas y errores.
      // Este endpoint registra en dos bases de datos y puede tardar más que un POST normal.
      const response = await InvocarMetodo('registrarCliente.php', 'registrar_cliente', sanitizedData);

      const responseData = response?.data || response;

      if (response?.status === 'success' && responseData?.status === 'success') {
        return {
          success: true,
          message: responseData.message || response.message || 'Cliente registrado correctamente',
        };
      } else {
        return rejectWithValue(responseData?.message || response?.message || 'Error en el registro');
      }
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

const initialState = {
  user: null,
  isAuthenticated: false,
  loading: false,
  error: null,
  appReady: false,
  tokenStatus: 'missing',
  expiresIn: null,
  expiresAt: null,
  jwtPayload: null,
  sessionExtensions: 0,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    setSessionWarning: (state, action) => {
      state.tokenStatus = 'warning';
      state.expiresIn = action.payload.expiresIn ?? state.expiresIn;
    },
    extendSessionCounter: (state) => {
      state.sessionExtensions = Math.min((state.sessionExtensions || 0) + 1, 3);
      state.expiresIn = (state.expiresIn || 0) + 60;
      state.expiresAt = (state.expiresAt || Math.floor(Date.now() / 1000)) + 60;
    },
    updateUserProfile: (state, action) => {
      if (!action.payload) {
        return;
      }

      const payload = action.payload || {};
      const baseUser = state.user || {};
      const nextUser = {
        ...baseUser,
        ...payload,
      };

      if (payload.correo !== undefined) {
        nextUser.correo = payload.correo;
      }
      if (payload.email !== undefined) {
        nextUser.email = payload.email;
      }
      if (payload.correo && !payload.email) {
        nextUser.email = payload.correo;
      }
      if (payload.email && !payload.correo) {
        nextUser.correo = payload.email;
      }
      if (payload.nombres !== undefined) {
        nextUser.nombres = payload.nombres;
      }
      if (payload.nombre !== undefined) {
        nextUser.nombre = payload.nombre;
      }
      if (payload.apellidos !== undefined) {
        nextUser.apellidos = payload.apellidos;
      }
      if (payload.apellido !== undefined) {
        nextUser.apellido = payload.apellido;
      }
      if (payload.username !== undefined) {
        nextUser.username = payload.username;
      }
      if (payload.nombre_usuario !== undefined) {
        nextUser.nombre_usuario = payload.nombre_usuario;
      }

      state.user = nextUser;
    },
  },
  extraReducers: (builder) => {
    // Login
    builder.addCase(loginUser.pending, (state) => {
      state.loading = true;
      state.error = null;
    });
    builder.addCase(loginUser.fulfilled, (state, action) => {
      state.loading = false;
      state.isAuthenticated = true;
      state.user = action.payload.user;
      state.error = null;
      state.tokenStatus = 'valid';
      state.expiresIn = null;
      state.expiresAt = null;
      state.jwtPayload = null;
    });
    builder.addCase(loginUser.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.isAuthenticated = false;
      state.user = null;
    });
    
    // Check Auth
    builder.addCase(checkAuth.pending, (state) => {
      state.loading = true;
    });
    builder.addCase(checkAuth.fulfilled, (state, action) => {
      state.loading = false;
      state.isAuthenticated = action.payload.isAuthenticated;

      const payloadUser = action.payload.user || {};
      const currentUser = state.user || {};
      state.user = {
        ...payloadUser,
        ...currentUser,
      };

      state.tokenStatus = action.payload.tokenStatus || 'missing';
      state.expiresIn = action.payload.expiresIn ?? null;
      state.expiresAt = action.payload.expiresAt ?? null;
      state.jwtPayload = action.payload.jwtPayload ?? null;
      state.appReady = true;
    });
    builder.addCase(checkAuth.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.appReady = true;
    });
    
    // Logout
    builder.addCase(logoutUser.fulfilled, (state) => {
      state.isAuthenticated = false;
      state.user = null;
      state.loading = false;
      state.error = null;
      state.tokenStatus = 'missing';
      state.expiresIn = null;
      state.expiresAt = null;
      state.jwtPayload = null;
      state.sessionExtensions = 0;
    });

    builder.addCase(refreshAuthSession.fulfilled, (state, action) => {
      state.loading = false;
      state.isAuthenticated = action.payload.isAuthenticated;

      const payloadUser = action.payload.user || {};
      const currentUser = state.user || {};
      state.user = {
        ...payloadUser,
        ...currentUser,
      };

      state.tokenStatus = action.payload.tokenStatus || 'missing';
      state.expiresIn = action.payload.expiresIn ?? null;
      state.expiresAt = action.payload.expiresAt ?? null;
      state.jwtPayload = action.payload.jwtPayload ?? null;
    });
    
    // Register
    builder.addCase(registerUser.pending, (state) => {
      state.loading = true;
      state.error = null;
    });
    builder.addCase(registerUser.fulfilled, (state) => {
      state.loading = false;
      state.error = null;
    });
    builder.addCase(registerUser.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
    });

    builder.addCase(refreshAuthSession.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.isAuthenticated = false;
      state.user = null;
      state.tokenStatus = 'invalid';
      state.expiresIn = null;
      state.expiresAt = null;
      state.jwtPayload = null;
    });
  },
});

export const { clearError, setSessionWarning, extendSessionCounter, updateUserProfile } = authSlice.actions;
export default authSlice.reducer;
