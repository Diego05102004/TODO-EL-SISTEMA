import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Image,
} from 'react-native';
import { useDispatch, useSelector } from 'react-redux';
import { Ionicons } from '@expo/vector-icons';
import { loginUser, clearError } from '../../redux/slices/authSlice';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { API_BASE_URL } from '../../config/apiConfig';
import { filterKeyPress, validateField } from '../../validation/authValidation';

// Intentar cargar la imagen
let logoImage;
try {
  logoImage = require('../../assets/images/LOGO.png');
} catch (e) {
  console.error('Error al cargar la imagen:', e);
  logoImage = null;
}

const LoginScreen = ({ navigation }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [imageError, setImageError] = useState(false);
  const [usernameError, setUsernameError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [debugData, setDebugData] = useState(null);
  const [apiResponse, setApiResponse] = useState(null);
  const loginSubmittingRef = useRef(false);
  const dispatch = useDispatch();
  const { loading, error, user } = useSelector((state) => state.auth);

  useEffect(() => {
    if (!logoImage) {
      console.error('No se pudo cargar la imagen');
      setImageError(true);
    }
  }, []);

  useEffect(() => {
    return () => {
      dispatch(clearError());
    };
  }, [dispatch]);

  useEffect(() => {
    try {
      if (error && typeof error === 'object' && error.apiResponse) {
        setApiResponse(error.apiResponse);
      } else if (error) {
        setApiResponse({
          status: 'error',
          message: typeof error === 'string' ? error : (error.message || 'Error desconocido'),
          data: error
        });
      } else if (user) {
        setApiResponse({ status: 'success', message: 'Login exitoso' });
      }
    } catch (e) {
      console.error('Error en useEffect de captura de respuesta:', e);
    }
  }, [error, user]);

  const handleUsernameChange = (text) => {
    const filteredText = filterKeyPress.nombre_usuario(text);
    setUsername(filteredText);

    if (!filteredText.trim()) {
      setUsernameError('');
    } else {
      setUsernameError(validateField('usernameLogin', filteredText));
    }
  };

  const handlePasswordChange = (text) => {
    const filteredText = filterKeyPress.clave(text);
    setPassword(filteredText);

    if (!filteredText.trim()) {
      setPasswordError('');
    } else {
      setPasswordError(validateField('passwordLogin', filteredText));
    }
  };

  const handleLogin = async () => {
    if (loginSubmittingRef.current) {
      return;
    }

    setDebugData(null);
    setApiResponse(null);

    let uError = '';
    let pError = '';

    if (!username.trim()) {
      uError = '*Este campo es obligatorio*';
    } else {
      uError = validateField('usernameLogin', username);
    }

    if (!password.trim()) {
      pError = '*Este campo es obligatorio*';
    } else {
      pError = validateField('passwordLogin', password);
    }

    setUsernameError(uError);
    setPasswordError(pError);

    if (uError || pError) {
      return;
    }

    const loginData = { 
      username: username, 
      password, 
      name: 'Usuario' 
    };
    loginSubmittingRef.current = true;

    setDebugData({
      timestamp: new Date().toISOString(),
      apiURL: `${API_BASE_URL}/login.php`,
      requestType: 'USERNAME_LOGIN'
    });

    try {
      await dispatch(loginUser(loginData));
    } catch (error) {
      console.error('Error en handleLogin:', error);
      setApiResponse({
        status: 'error',
        message: error.message || 'Error desconocido al hacer login',
        data: { error: error.toString() }
      });
    } finally {
      loginSubmittingRef.current = false;
    }
  };

  const handleGoToRegister = () => {
    navigation.navigate('Register');
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContainer}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.logoContainer}>
          {logoImage ? (
            <Image
              source={logoImage}
              style={styles.logo}
              resizeMode="contain"
              onError={() => setImageError(true)}
            />
          ) : (
            <View style={[styles.logo, { backgroundColor: COLORS.lightGray, justifyContent: 'center', alignItems: 'center' }]}>
              <Text style={{ color: COLORS.primary }}>Logo no disponible</Text>
            </View>
          )}
          <Text style={styles.title}>Bienvenido</Text>
          <Text style={styles.subtitle}>Inicia sesión para continuar</Text>
        </View>

        <View style={styles.formContainer}>
          {error && (
            <View style={styles.errorContainer}>
              <Text style={styles.errorText}>
                {typeof error === 'object' ? (error.message || error.apiResponse?.message || 'Error en el inicio de sesión') : error}
              </Text>
            </View>
          )}

          <View style={styles.inputContainer}>
            <Text style={styles.label}>Usuario</Text>
            <TextInput
              style={[styles.input, usernameError && styles.inputError]}
              placeholder="Ingresa tu usuario"
              placeholderTextColor={COLORS.placeholder}
              value={username}
              onChangeText={handleUsernameChange}
              keyboardType="default"
              autoCapitalize="none"
              autoCorrect={false}
              maxLength={20}
              editable={!loading}
            />
            {usernameError ? <Text style={styles.fieldErrorText}>{usernameError}</Text> : null}
          </View>

          <View style={styles.inputContainer}>
            <Text style={styles.label}>Contraseña</Text>
            <View style={[styles.passwordInputContainer, passwordError && styles.inputError]}>
              <TextInput
                style={styles.passwordInput}
                placeholder="Ingresa tu contraseña"
                placeholderTextColor={COLORS.placeholder}
                value={password}
                onChangeText={handlePasswordChange}
                secureTextEntry={!showPassword}
                maxLength={15}
                editable={!loading}
              />
              <TouchableOpacity
                style={styles.eyeButton}
                onPress={() => setShowPassword(!showPassword)}
              >
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={22}
                  color={COLORS.textSecondary || '#666'}
                />
              </TouchableOpacity>
            </View>
            {passwordError ? <Text style={styles.fieldErrorText}>{passwordError}</Text> : null}
          </View>

          <TouchableOpacity
            style={[styles.loginButton, (loading || !username || !password) && styles.loginButtonDisabled]}
            onPress={handleLogin}
            disabled={loading || !username || !password}
          >
            <Text style={styles.loginButtonText}>
              {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
            </Text>
          </TouchableOpacity>

          <View style={styles.footer}>
            <Text style={styles.footerText}>¿No tienes una cuenta? </Text>
            <TouchableOpacity onPress={handleGoToRegister} disabled={loading}>
              <Text style={styles.footerLink}>Regístrate</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollContainer: {
    flexGrow: 1,
    padding: theme.spacing.lg,
    paddingVertical: theme.spacing.xl,
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: theme.spacing.xl,
  },
  logo: {
    width: 120,
    height: 120,
    marginBottom: theme.spacing.md,
  },
  title: {
    ...theme.typography.h1,
    color: COLORS.primary,
    marginBottom: theme.spacing.xs,
  },
  subtitle: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
  },
  formContainer: {
    width: '100%',
  },
  errorContainer: {
    backgroundColor: COLORS.danger + '20',
    padding: theme.spacing.md,
    borderRadius: theme.radius.md,
    marginBottom: theme.spacing.lg,
  },
  errorText: {
    color: COLORS.danger,
    textAlign: 'center',
  },
  inputContainer: {
    marginBottom: theme.spacing.lg,
  },
  label: {
    ...theme.typography.body,
    color: COLORS.text,
    marginBottom: theme.spacing.sm,
    fontWeight: '500',
  },
  input: {
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: theme.radius.md,
    padding: theme.spacing.md,
    ...theme.typography.body,
    color: COLORS.text,
  },
  passwordInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: theme.radius.md,
  },
  passwordInput: {
    flex: 1,
    padding: theme.spacing.md,
    ...theme.typography.body,
    color: COLORS.text,
  },
  eyeButton: {
    paddingHorizontal: theme.spacing.md,
    justifyContent: 'center',
    alignItems: 'center',
  },
  inputError: {
    borderColor: COLORS.danger,
  },
  fieldErrorText: {
    ...theme.typography.caption,
    color: COLORS.danger,
    marginTop: theme.spacing.xs,
    textAlign: 'center',
  },
  loginButton: {
    backgroundColor: COLORS.primary,
    padding: theme.spacing.md,
    borderRadius: theme.radius.md,
    alignItems: 'center',
    marginTop: theme.spacing.sm,
    ...theme.shadow.md,
  },
  loginButtonDisabled: {
    opacity: 0.6,
  },
  loginButtonText: {
    ...theme.typography.body,
    color: COLORS.white,
    fontWeight: '600',
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: theme.spacing.xl,
  },
  footerText: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
  },
  footerLink: {
    ...theme.typography.body,
    color: COLORS.primary,
    fontWeight: '600',
  },
  debugContainer: {
    marginTop: theme.spacing.xl,
    padding: theme.spacing.md,
    backgroundColor: '#f5f5f5',
    borderRadius: theme.radius.md,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  debugTitle: {
    ...theme.typography.subtitle,
    color: COLORS.primary,
    fontWeight: 'bold',
    marginBottom: theme.spacing.sm,
    textAlign: 'center',
  },
  debugSection: {
    marginBottom: theme.spacing.md,
    paddingBottom: theme.spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: '#ddd',
  },
  debugSectionTitle: {
    ...theme.typography.bodyBold,
    color: COLORS.text,
    marginBottom: theme.spacing.xs,
  },
  debugText: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginBottom: 2,
  },
  debugError: {
    ...theme.typography.caption,
    color: COLORS.danger,
  },
});

export default LoginScreen;