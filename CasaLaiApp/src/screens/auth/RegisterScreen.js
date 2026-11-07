import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Modal,
} from 'react-native';
import { useDispatch, useSelector } from 'react-redux';
import { Ionicons } from '@expo/vector-icons';
import { registerUser } from '../../redux/slices/authSlice';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';

import { filterKeyPress, validateField } from '../../validation/authValidation';

const RegisterScreen = ({ navigation }) => {
  const [nombreUsuario, setNombreUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [claveConfirmar, setClaveConfirmar] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [nombre, setNombre] = useState('');
  const [apellido, setApellido] = useState('');
  const [correo, setCorreo] = useState('');
  const [telefono, setTelefono] = useState('');
  const [cedula, setCedula] = useState('');
  const [direccion, setDireccion] = useState('');
  
  const [errors, setErrors] = useState({});
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorVisible, setErrorVisible] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const dispatch = useDispatch();
  const { loading } = useSelector((state) => state.auth);

  const handleNombreUsuarioChange = (text) => {
    const filtered = filterKeyPress.nombre_usuario(text);
    setNombreUsuario(filtered);
    setErrors((prev) => ({ ...prev, nombreUsuario: validateField('nombre_usuario', filtered) }));
  };

  const handleClaveChange = (text) => {
    const filtered = filterKeyPress.clave(text);
    setClave(filtered);
    setErrors((prev) => ({
      ...prev,
      clave: validateField('clave', filtered),
      ...(claveConfirmar ? { claveConfirmar: validateField('clave_confirmar', claveConfirmar, filtered) } : {})
    }));
  };

  const handleClaveConfirmarChange = (text) => {
    const filtered = filterKeyPress.clave(text);
    setClaveConfirmar(filtered);
    setErrors((prev) => ({ ...prev, claveConfirmar: validateField('clave_confirmar', filtered, clave) }));
  };

  const handleNombreChange = (text) => {
    const filtered = filterKeyPress.nombre(text);
    setNombre(filtered);
    setErrors((prev) => ({ ...prev, nombre: validateField('nombre', filtered) }));
  };

  const handleApellidoChange = (text) => {
    const filtered = filterKeyPress.apellido(text);
    setApellido(filtered);
    setErrors((prev) => ({ ...prev, apellido: validateField('apellido', filtered) }));
  };

  const handleCorreoChange = (text) => {
    const filtered = filterKeyPress.correo(text);
    setCorreo(filtered);
    setErrors((prev) => ({ ...prev, correo: validateField('correo', filtered) }));
  };

  const handleTelefonoChange = (text) => {
    const filtered = filterKeyPress.telefono(text);
    setTelefono(filtered);
    setErrors((prev) => ({ ...prev, telefono: validateField('telefono', filtered) }));
  };

  const handleCedulaChange = (text) => {
    const filtered = filterKeyPress.cedula(text);
    setCedula(filtered);
    setErrors((prev) => ({ ...prev, cedula: validateField('cedula', filtered) }));
  };

  const handleDireccionChange = (text) => {
    const filtered = filterKeyPress.direccion(text);
    setDireccion(filtered);
    setErrors((prev) => ({ ...prev, direccion: validateField('direccion', filtered) }));
  };

  const validateForm = () => {
    const newErrors = {
      nombre: validateField('nombre', nombre),
      apellido: validateField('apellido', apellido),
      nombreUsuario: validateField('nombre_usuario', nombreUsuario),
      cedula: validateField('cedula', cedula),
      telefono: validateField('telefono', telefono),
      correo: validateField('correo', correo),
      direccion: validateField('direccion', direccion),
      clave: validateField('clave', clave),
      claveConfirmar: validateField('clave_confirmar', claveConfirmar, clave),
    };

    setErrors(newErrors);
    return Object.values(newErrors).every((err) => !err);
  };

  const handleRegister = async () => {
    if (!validateForm()) {
      return;
    }
    try {
      const result = await dispatch(registerUser({
        username: nombreUsuario,
        password: clave,
        nombres: nombre,
        apellidos: apellido,
        correo,
        telefono,
        cedula,
        direccion,
      })).unwrap();

      setSuccessMessage(result?.message || 'Cliente registrado correctamente.');
      setSuccessVisible(true);
    } catch (registerError) {
      const message = typeof registerError === 'string'
        ? registerError
        : registerError?.message || 'No se pudo completar el registro.';
      setErrorMessage(message);
      setErrorVisible(true);
    }
  };

  const formContent = (
    <ScrollView
      style={styles.scrollView}
      contentContainerStyle={styles.scrollContainer}
      keyboardShouldPersistTaps="handled"
      showsVerticalScrollIndicator={true}
    >
      <View style={styles.header}>
        <Text style={styles.title}>Registro de Usuario</Text>
        <Text style={styles.subtitle}>Crea tu cuenta para comenzar</Text>
      </View>

      <View style={styles.form}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Nombre</Text>
          <TextInput
            style={[styles.input, errors.nombre && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="Ingrese su nombre(s)"
            value={nombre}
            onChangeText={handleNombreChange}
            maxLength={50}
          />
          {errors.nombre ? <Text style={styles.errorText}>{errors.nombre}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Apellido</Text>
          <TextInput
            style={[styles.input, errors.apellido && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="Ingrese su apellido(s)"
            value={apellido}
            onChangeText={handleApellidoChange}
            maxLength={50}
          />
          {errors.apellido ? <Text style={styles.errorText}>{errors.apellido}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Nombre de Usuario</Text>
          <TextInput
            style={[styles.input, errors.nombreUsuario && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="usuario123"
            value={nombreUsuario}
            onChangeText={handleNombreUsuarioChange}
            autoCapitalize="none"
            maxLength={20}
          />
          {errors.nombreUsuario ? <Text style={styles.errorText}>{errors.nombreUsuario}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Cédula</Text>
          <TextInput
            style={[styles.input, errors.cedula && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="1.234.567 o 12.345.678"
            value={cedula}
            onChangeText={handleCedulaChange}
            keyboardType="numeric"
            maxLength={10}
          />
          {errors.cedula ? <Text style={styles.errorText}>{errors.cedula}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Teléfono</Text>
          <TextInput
            style={[styles.input, errors.telefono && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="0400-000-0000"
            value={telefono}
            onChangeText={handleTelefonoChange}
            keyboardType="phone-pad"
            maxLength={13}
          />
          {errors.telefono ? <Text style={styles.errorText}>{errors.telefono}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Correo Electrónico</Text>
          <TextInput
            style={[styles.input, errors.correo && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="ejemplo@gmail.com"
            value={correo}
            onChangeText={handleCorreoChange}
            autoCapitalize="none"
            keyboardType="email-address"
            maxLength={50}
          />
          {errors.correo ? <Text style={styles.errorText}>{errors.correo}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Dirección</Text>
          <TextInput
            style={[styles.input, errors.direccion && styles.inputError]}
            placeholderTextColor={COLORS.placeholder}
            placeholder="Ingrese su dirección completa"
            value={direccion}
            onChangeText={handleDireccionChange}
            multiline
            maxLength={100}
          />
          {errors.direccion ? <Text style={styles.errorText}>{errors.direccion}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Contraseña</Text>
          <View style={[styles.passwordInputContainer, errors.clave && styles.inputError]}>
            <TextInput
              style={styles.passwordInput}
              placeholderTextColor={COLORS.placeholder}
              placeholder="Crea una contraseña"
              value={clave}
              onChangeText={handleClaveChange}
              secureTextEntry={!showPassword}
              maxLength={15}
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
          {errors.clave ? <Text style={styles.errorText}>{errors.clave}</Text> : null}
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Confirmar Contraseña</Text>
          <View style={[styles.passwordInputContainer, errors.claveConfirmar && styles.inputError]}>
            <TextInput
              style={styles.passwordInput}
              placeholderTextColor={COLORS.placeholder}
              placeholder="Confirma tu contraseña"
              value={claveConfirmar}
              onChangeText={handleClaveConfirmarChange}
              secureTextEntry={!showConfirmPassword}
              maxLength={15}
            />
            <TouchableOpacity
              style={styles.eyeButton}
              onPress={() => setShowConfirmPassword(!showConfirmPassword)}
            >
              <Ionicons
                name={showConfirmPassword ? 'eye-off-outline' : 'eye-outline'}
                size={22}
                color={COLORS.textSecondary || '#666'}
              />
            </TouchableOpacity>
          </View>
          {errors.claveConfirmar ? <Text style={styles.errorText}>{errors.claveConfirmar}</Text> : null}
        </View>

        <TouchableOpacity
          style={[styles.button, loading && styles.buttonDisabled]}
          onPress={handleRegister}
          disabled={loading}
        >
          <Text style={styles.buttonText}>
            {loading ? 'Registrando...' : 'Registrarse'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.linkButton}
          onPress={() => navigation.navigate('Login')}
        >
          <Text style={styles.linkText}>
            ¿Ya tienes cuenta? Inicia Sesión
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  const modalContent = (
    <>
      <Modal
        animationType="fade"
        transparent
        visible={successVisible}
        onRequestClose={() => setSuccessVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.successModalCard}>
            <View style={styles.successIconContainer}>
              <Ionicons name="checkmark-circle" size={56} color="#22C55E" />
            </View>
            <Text style={styles.modalTitle}>Registro exitoso</Text>
            <Text style={styles.modalMessage}>{successMessage}</Text>
            <TouchableOpacity
              style={styles.successButton}
              onPress={() => {
                setSuccessVisible(false);
                navigation.navigate('Login');
              }}
            >
              <Text style={styles.buttonModalText}>Aceptar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      <Modal
        animationType="fade"
        transparent
        visible={errorVisible}
        onRequestClose={() => setErrorVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.errorModalCard}>
            <View style={styles.errorIconContainer}>
              <Ionicons name="alert-circle" size={56} color="#E53E3E" />
            </View>
            <Text style={styles.modalTitle}>Error en el registro</Text>
            <Text style={styles.modalMessage}>{errorMessage}</Text>
            <TouchableOpacity
              style={styles.errorButton}
              onPress={() => setErrorVisible(false)}
            >
              <Text style={styles.buttonModalText}>Cerrar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </>
  );

  if (Platform.OS === 'web') {
    return (
      <View style={styles.webContainer}>
        {formContent}
        {modalContent}
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      {formContent}
      {modalContent}
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  webContainer: {
    flex: 1,
    height: '100vh',
    maxHeight: '100vh',
    overflow: 'hidden',
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
    width: '100%',
  },
  scrollContainer: {
    flexGrow: 1,
    paddingHorizontal: 20,
    paddingTop: 30,
    paddingBottom: 60,
  },
  header: {
    alignItems: 'center',
    marginBottom: 25,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: COLORS.primary,
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: COLORS.textSecondary,
  },
  form: {
    width: '100%',
  },
  inputGroup: {
    marginBottom: 18,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text,
    marginBottom: 8,
  },
  input: {
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    color: COLORS.text,
  },
  passwordInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 8,
  },
  passwordInput: {
    flex: 1,
    padding: 12,
    fontSize: 16,
    color: COLORS.text,
  },
  eyeButton: {
    paddingHorizontal: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  inputError: {
    borderColor: COLORS.error || COLORS.danger,
  },
  errorText: {
    color: COLORS.error || COLORS.danger,
    fontSize: 12,
    marginTop: 5,
  },
  button: {
    backgroundColor: COLORS.primary,
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 10,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: 'bold',
  },
  linkButton: {
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 10,
  },
  linkText: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: '600',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  successModalCard: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: COLORS.white,
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
  },
  errorModalCard: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: COLORS.white,
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
  },
  successIconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(34, 197, 94, 0.12)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  errorIconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(229, 62, 62, 0.12)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 22,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 8,
    textAlign: 'center',
  },
  modalMessage: {
    color: '#4B5563',
    fontSize: 15,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 20,
  },
  successButton: {
    width: '100%',
    borderRadius: 12,
    backgroundColor: '#22C55E',
    paddingVertical: 12,
    alignItems: 'center',
  },
  errorButton: {
    width: '100%',
    borderRadius: 12,
    backgroundColor: '#E53E3E',
    paddingVertical: 12,
    alignItems: 'center',
  },
  buttonModalText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 15,
  },
});

export default RegisterScreen;