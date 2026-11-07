import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image, Platform, Alert, ScrollView, TextInput, Modal } from 'react-native';
import { useDispatch, useSelector } from 'react-redux';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import {
  API_BASE_URL,
  PROFILE_IMAGE_STORAGE_PATH,
  buildProfileImageUrl,
} from '../../config/apiConfig';
import { InvocarMetodo } from '../../utils/comunicadorApi';
import { getStoredToken, persistAuthSession } from '../../utils/jwt';
import { updateUserProfile } from '../../redux/slices/authSlice';
import { encryption } from '../../utils/encryption';

import {
  filterUsername,
  filterAlpha,
  formatPhoneNumber,
  validateUsername,
  validateName,
  validateLastName,
  validatePhone,
  validateEmail,
  validatePassword,
  validateConfirmPassword,
  validateProfileImage,
  getProfileImageMimeType,
} from '../../validation/profileValidation';

const ProfileScreen = () => {
  const dispatch = useDispatch();
  const { user } = useSelector((state) => state.auth);
  const userId = user?.id_usuario ?? user?.id ?? user?.user_id ?? null;

  // Estados para foto de perfil
  const [profileImage, setProfileImage] = useState(
    buildProfileImageUrl(user?.foto_perfil || user?.avatar) || null
  );
  const [selectedAsset, setSelectedAsset] = useState(null);
  const [tempImage, setTempImage] = useState(null);
  const [isPhotoModalVisible, setIsPhotoModalVisible] = useState(false);
  const [isLoadingProfile, setIsLoadingProfile] = useState(false);
  const [hasProfileLoaded, setHasProfileLoaded] = useState(false);
  const [profileSuccessVisible, setProfileSuccessVisible] = useState(false);
  const [profileSuccessMessage, setProfileSuccessMessage] = useState('');
  const [profileErrorVisible, setProfileErrorVisible] = useState(false);
  const [profileErrorMessage, setProfileErrorMessage] = useState('');

  // Estados de edición
  const [isEditingPersonal, setIsEditingPersonal] = useState(false);
  const [isEditingEmail, setIsEditingEmail] = useState(false);
  const [isEditingPassword, setIsEditingPassword] = useState(false);

  // Mapeo dinámico soportando nombres de columnas de tbl_usuarios y camelCase
  const getUserUsername = () => user?.username || user?.nombre_usuario || '';
  const getUserNombres = () => user?.nombres || user?.nombre || user?.name || '';
  const getUserApellidos = () => user?.apellidos || user?.apellido || user?.lastName || '';
  const getUserTelefono = () => user?.telefono || user?.phone || '';
  const getUserCorreo = () => user?.correo || user?.email || '';
  const getUserCedula = () => user?.cedula || '';

  // Campos de Información Personal
  const [username, setUsername] = useState(getUserUsername());
  const [name, setName] = useState(getUserNombres());
  const [lastName, setLastName] = useState(getUserApellidos());
  const [phone, setPhone] = useState(getUserTelefono());

  const [errorsPersonal, setErrorsPersonal] = useState({});

  // Campos de Cambio de Correo
  const [newEmail, setNewEmail] = useState('');
  const [currentPasswordForEmail, setCurrentPasswordForEmail] = useState('');
  const [errorsEmail, setErrorsEmail] = useState({});

  // Campos de Cambio de Contraseña
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [errorsPassword, setErrorsPassword] = useState({});
  const [currentPasswordForProfile, setCurrentPasswordForProfile] = useState('');
  const [currentPasswordForAvatar, setCurrentPasswordForAvatar] = useState('');

  useEffect(() => {
    if (user?.foto_perfil) {
      // La imagen puede venir guardada como ruta relativa/relativa al servidor o como URL completa.
      // Aquí convertimos a una URL pública para que pueda cargarse correctamente en la app.
      const resolvedImage = buildProfileImageUrl(user.foto_perfil) || user.foto_perfil;
      setProfileImage(resolvedImage);
    }
  }, [user?.foto_perfil]);

  useEffect(() => {
    const fetchProfile = async () => {
      if (!userId) {
        return;
      }

      try {
        setIsLoadingProfile(true);
        const response = await InvocarMetodo('perfil.php', 'default', { id_usuario: userId });
        const result = response?.data ?? response;
        const rawProfileData = result?.usuario || result?.user || result || {};
        const profileData = rawProfileData;

        if (profileData && Object.keys(profileData).length > 0) {
          const nextUser = {
            ...(user || {}),
            id: profileData.id_usuario ?? user?.id ?? userId,
            id_usuario: profileData.id_usuario ?? userId,
            username: profileData.username ?? profileData.nombre_usuario ?? user?.username ?? '',
            nombre_usuario: profileData.username ?? profileData.nombre_usuario ?? user?.username ?? '',
            nombres: profileData.nombres ?? profileData.nombre ?? user?.nombres ?? user?.nombre ?? '',
            nombre: profileData.nombres ?? profileData.nombre ?? user?.nombres ?? user?.nombre ?? '',
            apellidos: profileData.apellidos ?? profileData.apellido ?? user?.apellidos ?? user?.apellido ?? '',
            apellido: profileData.apellidos ?? profileData.apellido ?? user?.apellidos ?? user?.apellido ?? '',
            telefono: profileData.telefono ?? user?.telefono ?? '',
            phone: profileData.telefono ?? user?.telefono ?? '',
            correo: profileData.correo ?? profileData.email ?? user?.correo ?? user?.email ?? '',
            email: profileData.correo ?? profileData.email ?? user?.correo ?? user?.email ?? '',
            cedula: profileData.cedula ?? user?.cedula ?? '',
            foto_perfil: profileData.foto_perfil ?? user?.foto_perfil ?? null,
            token: user?.token ?? null,
          };

          dispatch(updateUserProfile(nextUser));
          setHasProfileLoaded(true);

          if (nextUser.foto_perfil) {
            // Cuando el backend devuelve la ruta guardada, la convertimos a URL pública para mostrarla.
            setProfileImage(buildProfileImageUrl(nextUser.foto_perfil) || nextUser.foto_perfil);
          }
          if (!isEditingPersonal) {
            setUsername(nextUser.username || '');
            setName(nextUser.nombres || '');
            setLastName(nextUser.apellidos || '');
            setPhone(nextUser.telefono || '');
          }
        }
      } catch (error) {
        console.warn('No se pudo cargar el perfil desde la API:', error?.message || error);
      } finally {
        setIsLoadingProfile(false);
      }
    };

    fetchProfile();
  }, [userId]);

  // Sincronización limpia con el store sin reseteos imprevistos mientras se edita
  useEffect(() => {
    if (!user || isEditingPersonal || !hasProfileLoaded) {
      return;
    }

    setUsername(getUserUsername());
    setName(getUserNombres());
    setLastName(getUserApellidos());
    setPhone(getUserTelefono());
  }, [user, isEditingPersonal, hasProfileLoaded]);

  const handleOpenPhotoModal = () => {
    setTempImage(profileImage);
    setIsPhotoModalVisible(true);
  };

  const pickImage = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    
    if (status !== 'granted') {
      Alert.alert('Permiso requerido', 'Necesitamos acceso a tu galería para cambiar la foto de perfil');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      aspect: [1, 1],
      // La calidad máxima evita recomprimir el archivo antes de enviarlo al servidor.
      quality: 1,
    });

    if (!result.canceled && result.assets && result.assets.length > 0) {
      const asset = result.assets[0];
      const sizeError = validateProfileImage(asset);
      if (sizeError) {
        Alert.alert('Error', sizeError);
        return;
      }
      setSelectedAsset(asset);
      setTempImage(asset.uri);
    }
  };

  const handleSavePhoto = async () => {
    if (!selectedAsset && !tempImage) {
      Alert.alert('Error', 'Debes seleccionar una imagen');
      return;
    }

    if (!userId) {
      Alert.alert('Sesión expirada', 'No se pudo identificar al usuario para actualizar la foto.');
      return;
    }

    if (!currentPasswordForAvatar) {
      Alert.alert('Contraseña requerida', 'Ingresa tu contraseña actual para cambiar la foto de perfil.');
      return;
    }

    try {
      const fileUri = selectedAsset?.uri || tempImage;
      const originalFileName = selectedAsset?.fileName || fileUri?.split('/').pop() || 'perfil.jpg';
      const cleanFileName = originalFileName.replace(/\\/g, '/').split('/').pop() || 'perfil.jpg';
      const mimeType = getProfileImageMimeType(selectedAsset || { fileName: cleanFileName });
      const extensionFromName = cleanFileName.includes('.')
        ? cleanFileName.split('.').pop().toLowerCase()
        : '';
      const extensionFromMime = mimeType.split('/')[1]?.toLowerCase().replace('jpeg', 'jpg');
      const imageExtension = extensionFromName || extensionFromMime || 'jpg';
      const safeBaseName = cleanFileName
        .replace(/\.[^/.]+$/, '')
        .replace(/[^a-zA-Z0-9_-]/g, '_') || 'perfil';
      const uploadFileName = `${Date.now()}_${safeBaseName}.${imageExtension}`;

      // El objeto { uri, name, type } solo describe el archivo. Convertimos el URI a Blob
      // para que FormData envíe los bytes reales y PHP los reciba en $_FILES.
      const fileResponse = await fetch(fileUri);
      if (!fileResponse.ok) {
        throw new Error('No se pudo leer la imagen seleccionada');
      }
      const imageBlob = await fileResponse.blob();
      if (!imageBlob.size) {
        throw new Error('La imagen seleccionada está vacía');
      }

      // Este valor representa la carpeta física del servidor donde PHP debe guardar la imagen.
      // NO es la URL pública que la app usa para mostrar la imagen.
      const normalizedStoragePath = (PROFILE_IMAGE_STORAGE_PATH || 'assets/img/uploads/')
        .replace(/\\/g, '/')
        .replace(/^\/+|\/+$/g, '');

      // La ruta de almacenamiento solo se envía para que el backend conozca el destino físico.
      // FormData.name debe contener solo el nombre del archivo. La ruta física se envía
      // por separado; mezclar ambas cosas puede corromper el multipart al procesarlo en PHP.

      const formData = new FormData();
      formData.append('funcion', 'cambiar_avatar');
      formData.append('id_usuario', String(userId));
      formData.append('clave_actual', encryption.encrypt(currentPasswordForAvatar));
      //formData.append('ruta_foto', normalizedStoragePath);
      formData.append('foto_perfil', imageBlob, uploadFileName);

      const token = await getStoredToken();
      if (!token) {
        throw new Error('Tu sesión expiró. Inicia sesión nuevamente.');
      }

      const response = await fetch(`${API_BASE_URL}/perfil.php`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      const contentType = response.headers.get('content-type') || '';
      const result = contentType.includes('application/json') ? await response.json() : await response.text();
      // La API responde { status, message, data: { foto_perfil } }.
      // Extraemos el nivel data para usar el nombre final generado por PHP.
      const responseData = result?.data?.data || result?.data || result;

      if (!response.ok || responseData?.status === 'error' || result?.status === 'error') {
        throw new Error(
          responseData?.message || result?.message
            ? responseData?.message || result?.message
            : 'No se pudo actualizar la foto de perfil.'
        );
      }

      // El backend devuelve la ruta que se guardó en la base de datos.
      // En la app la convertimos a una URL pública antes de mostrarla.
      const savedImagePath =
        responseData?.ruta_foto ||
        responseData?.foto_perfil ||
        responseData?.path ||
        responseData?.avatar;

      if (!savedImagePath) {
        throw new Error('El servidor no devolvió el nombre de la imagen guardada.');
      }

      const nextUser = {
        ...(user || {}),
        id_usuario: userId,
        foto_perfil: savedImagePath,
        email: user?.correo || user?.email || '',
        correo: user?.correo || user?.email || '',
      };
      dispatch(updateUserProfile(nextUser));
      await persistAuthSession({ token: user?.token, user: nextUser, payload: null });
      setProfileImage(buildProfileImageUrl(savedImagePath) || savedImagePath);
      setSelectedAsset(null);
      setTempImage(null);
      setCurrentPasswordForAvatar('');
      setIsPhotoModalVisible(false);
      const successText = responseData?.message || result?.message || 'Foto de perfil actualizada correctamente.';
      showProfileSuccess(successText);
    } catch (error) {
      showProfileError(error?.message || 'No se pudo actualizar la foto de perfil');
    }
  };

  // Manejadores de cambios
  const handleChangeUsername = (text) => {
    const filtered = filterUsername(text);
    setUsername(filtered);
    setErrorsPersonal((prev) => ({ ...prev, username: validateUsername(filtered) }));
  };

  const handleChangeName = (text) => {
    const filtered = filterAlpha(text);
    setName(filtered);
    setErrorsPersonal((prev) => ({ ...prev, name: validateName(filtered) }));
  };

  const handleChangeLastName = (text) => {
    const filtered = filterAlpha(text);
    setLastName(filtered);
    setErrorsPersonal((prev) => ({ ...prev, lastName: validateLastName(filtered) }));
  };

  const handleChangePhone = (text) => {
    const formatted = formatPhoneNumber(text);
    setPhone(formatted);
    setErrorsPersonal((prev) => ({ ...prev, phone: validatePhone(formatted) }));
  };

  const handleChangeNewEmail = (text) => {
    setNewEmail(text);
    setErrorsEmail((prev) => ({ ...prev, newEmail: validateEmail(text) }));
  };

  const handleChangeNewPassword = (text) => {
    setNewPassword(text);
    setErrorsPassword((prev) => ({
      ...prev,
      newPassword: validatePassword(text),
      confirmPassword: validateConfirmPassword(text, confirmPassword),
    }));
  };

  const handleChangeConfirmPassword = (text) => {
    setConfirmPassword(text);
    setErrorsPassword((prev) => ({
      ...prev,
      confirmPassword: validateConfirmPassword(newPassword, text),
    }));
  };

  // Guardar Información Personal
  const handleSavePersonal = async () => {
    const errUsername = validateUsername(username);
    const errName = validateName(name);
    const errLastName = validateLastName(lastName);
    const errPhone = validatePhone(phone);

    if (errUsername || errName || errLastName || errPhone || !currentPasswordForProfile) {
      setErrorsPersonal({
        username: errUsername,
        name: errName,
        lastName: errLastName,
        phone: errPhone,
        clave_actual: !currentPasswordForProfile ? 'Ingresa tu contraseña actual' : '',
      });
      const message = 'Por favor corrige los campos señalados y confirma tu contraseña actual';
      setProfileErrorMessage(message);
      setProfileErrorVisible(true);
      return;
    }

    try {
      const payload = {
        funcion: 'cambiar_personal',
        id_usuario: Number(userId),
        username: username.trim(),
        nombres: name.trim(),
        apellidos: lastName.trim(),
        telefono: phone.trim(),
        clave_actual: currentPasswordForProfile,
      };

      const response = await InvocarMetodo('perfil.php', 'cambiar_personal', payload);
      const result = response?.data ?? response;
      const isSuccess = response?.status === 'success' || result?.status === 'success' || response?.success === true;

      if (!isSuccess) {
        throw new Error(response?.message || result?.message || 'No se pudo actualizar la información personal.');
      }

      const nextUser = {
        ...(user || {}),
        id_usuario: userId,
        username: payload.username,
        nombre_usuario: payload.username,
        nombres: payload.nombres,
        nombre: payload.nombres,
        apellidos: payload.apellidos,
        apellido: payload.apellidos,
        telefono: payload.telefono,
        phone: payload.telefono,
        name: payload.nombres,
        lastName: payload.apellidos,
        correo: user?.correo || user?.email || '',
        email: user?.correo || user?.email || '',
      };

      dispatch(updateUserProfile(nextUser));
      await persistAuthSession({ token: user?.token, user: nextUser, payload: null });

      setCurrentPasswordForProfile('');
      setIsEditingPersonal(false);
      const successText = response?.message || result?.message || 'Tus datos personales han sido actualizados correctamente.';
      showProfileSuccess(successText);
    } catch (error) {
      showProfileError(error?.message || 'No se pudo guardar tu información personal');
    }
  };

  // Guardar Correo
  const handleSaveEmail = async () => {
    const trimmedEmail = newEmail.trim();
    const errEmail = validateEmail(trimmedEmail);
    if (errEmail || !currentPasswordForEmail) {
      setErrorsEmail({
        newEmail: errEmail,
        currentPasswordForEmail: !currentPasswordForEmail ? 'Ingresa tu contraseña actual' : '',
      });
      const message = 'Por favor completa y corrige los campos del correo';
      setProfileErrorMessage(message);
      setProfileErrorVisible(true);
      return;
    }

    try {
      const payload = {
        funcion: 'cambiar_correo',
        id_usuario: Number(userId),
        clave_actual: currentPasswordForEmail,
        correo_actual: user?.correo || user?.email || '',
        correo: trimmedEmail,
        email: trimmedEmail,
        nuevo_correo: trimmedEmail,
        correo_nuevo: trimmedEmail,
        email_nuevo: trimmedEmail,
      };

      const response = await InvocarMetodo('perfil.php', 'cambiar_correo', payload);
      const result = response?.data ?? response;
      const isSuccess = response?.status === 'success' || result?.status === 'success' || response?.success === true;

      if (!isSuccess) {
        throw new Error(response?.message || result?.message || 'No se pudo actualizar el correo.');
      }

      const nextUser = {
        ...(user || {}),
        id_usuario: userId,
        correo: trimmedEmail,
        email: trimmedEmail,
        nombre_usuario: user?.nombre_usuario || user?.username || '',
        username: user?.nombre_usuario || user?.username || '',
      };

      dispatch(updateUserProfile(nextUser));
      await persistAuthSession({ token: user?.token, user: nextUser, payload: null });

      setIsEditingEmail(false);
      setNewEmail('');
      setCurrentPasswordForEmail('');
      setErrorsEmail({});
      const successText = response?.message || result?.message || 'Tu correo electrónico ha sido actualizado correctamente.';
      showProfileSuccess(successText);
    } catch (error) {
      showProfileError(error?.message || 'No se pudo actualizar tu correo electrónico');
    }
  };

  // Guardar Contraseña
  const handleSavePassword = async () => {
    const errNewPassword = validatePassword(newPassword);
    const errConfirmPassword = validateConfirmPassword(newPassword, confirmPassword);

    if (!currentPassword || errNewPassword || errConfirmPassword) {
      setErrorsPassword({
        currentPassword: !currentPassword ? 'Ingresa tu contraseña actual' : '',
        newPassword: errNewPassword,
        confirmPassword: errConfirmPassword,
      });
      const message = 'Por favor revisa los campos de contraseña';
      setProfileErrorMessage(message);
      setProfileErrorVisible(true);
      return;
    }

    try {
      const payload = {
        funcion: 'cambiar_password',
        id_usuario: Number(userId),
        clave_actual: currentPassword,
        clave_nueva: newPassword,
        clave_confirmar: confirmPassword,
      };

      const response = await InvocarMetodo('perfil.php', 'cambiar_password', payload);
      const result = response?.data ?? response;
      const isSuccess = response?.status === 'success' || result?.status === 'success' || response?.success === true;

      if (!isSuccess) {
        throw new Error(response?.message || result?.message || 'No se pudo actualizar la contraseña.');
      }

      setIsEditingPassword(false);
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setErrorsPassword({});
      const successText = response?.message || result?.message || 'Tu contraseña ha sido actualizada correctamente.';
      showProfileSuccess(successText);
    } catch (error) {
      showProfileError(error?.message || 'No se pudo cambiar la contraseña');
    }
  };

  const showProfileSuccess = (message) => {
    const fallbackMessage = 'La operación se realizó correctamente.';
    const finalMessage = message || fallbackMessage;
    setProfileSuccessMessage(finalMessage === 'Operación exitosa' ? fallbackMessage : finalMessage);
    setProfileSuccessVisible(true);
  };

  const showProfileError = (message) => {
    const fallbackMessage = 'No se pudo completar la operación.';
    setProfileErrorMessage(message || fallbackMessage);
    setProfileErrorVisible(true);
  };

  const renderUserField = (label, value) => (
    <View style={styles.infoItem}>
      <Text style={styles.infoLabel}>{label}</Text>
      <View style={styles.fieldContainer}>
        <Text style={styles.infoValue}>{value || 'No disponible'}</Text>
      </View>
    </View>
  );

  return (
    <View style={styles.container}>
      {/* CABECERA DE PERFIL */}
      <View style={styles.profileHeader}>
        <TouchableOpacity onPress={handleOpenPhotoModal} style={styles.avatarContainer}>
          {profileImage ? (
            <Image source={{ uri: profileImage }} style={styles.avatar} />
          ) : (
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>
                {getUserNombres().charAt(0)?.toUpperCase() || getUserUsername().charAt(0)?.toUpperCase() || 'E'}
              </Text>
            </View>
          )}
          <View style={styles.cameraIcon}>
            <Ionicons name="camera" size={18} color={COLORS.white} />
          </View>
        </TouchableOpacity>

        <Text style={styles.userName}>
          {getUserNombres()} {getUserApellidos()}
        </Text>
        <Text style={styles.userEmail}>@{getUserUsername()}</Text>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* RECUADRO 1: INFORMACIÓN PERSONAL */}
        <View style={styles.infoContainer}>
          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionTitle}>Información Personal</Text>
            {!isEditingPersonal && (
              <TouchableOpacity style={styles.editButton} onPress={() => setIsEditingPersonal(true)}>
                <Ionicons name="pencil" size={18} color={COLORS.primary} />
              </TouchableOpacity>
            )}
          </View>

          {isEditingPersonal ? (
            <View style={styles.formGroup}>
              <View style={styles.inputBlock}>
                <Text style={styles.infoLabel}>Nombres</Text>
                <TextInput
                  style={[styles.input, errorsPersonal.name && styles.inputError]}
                  value={name}
                  onChangeText={handleChangeName}
                  placeholder="2 primeros nombres"
                />
                {errorsPersonal.name ? <Text style={styles.errorText}>{errorsPersonal.name}</Text> : null}
              </View>

              <View style={styles.inputBlock}>
                <Text style={styles.infoLabel}>Apellidos</Text>
                <TextInput
                  style={[styles.input, errorsPersonal.lastName && styles.inputError]}
                  value={lastName}
                  onChangeText={handleChangeLastName}
                  placeholder="2 primeros apellidos"
                />
                {errorsPersonal.lastName ? <Text style={styles.errorText}>{errorsPersonal.lastName}</Text> : null}
              </View>

              <View style={styles.inputBlock}>
                <Text style={styles.infoLabel}>Teléfono</Text>
                <TextInput
                  style={[styles.input, errorsPersonal.phone && styles.inputError]}
                  value={phone}
                  onChangeText={handleChangePhone}
                  keyboardType="numeric"
                  placeholder="Formato: 0400-000-0000"
                />
                {errorsPersonal.phone ? <Text style={styles.errorText}>{errorsPersonal.phone}</Text> : null}
              </View>
                            <View style={styles.inputBlock}>
                <Text style={styles.infoLabel}>Contraseña Actual</Text>
                <TextInput
                  style={[styles.input, errorsPersonal.clave_actual && styles.inputError]}
                  value={currentPasswordForProfile}
                  onChangeText={(text) => {
                    setCurrentPasswordForProfile(text);
                    setErrorsPersonal((prev) => ({ ...prev, clave_actual: '' }));
                  }}
                  secureTextEntry
                  placeholder="Confirma tu contraseña actual"
                />
                {errorsPersonal.clave_actual ? <Text style={styles.errorText}>{errorsPersonal.clave_actual}</Text> : null}
              </View>

              <View style={styles.cardActions}>
                <TouchableOpacity style={styles.saveGreenButton} onPress={handleSavePersonal}>
                  <Ionicons name="save-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                  <Text style={styles.saveButtonText}>Guardar</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.cancelGrayButton}
                  onPress={() => {
                    setUsername(getUserUsername());
                    setName(getUserNombres());
                    setLastName(getUserApellidos());
                    setPhone(getUserTelefono());
                    setCurrentPasswordForProfile('');
                    setErrorsPersonal({});
                    setIsEditingPersonal(false);
                  }}
                >
                  <Ionicons name="close-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                  <Text style={styles.cancelButtonText}>Cancelar</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <>
              {renderUserField('Nombres', name)}
              {renderUserField('Apellidos', lastName)}
              {renderUserField('Teléfono', phone)}
     
            </>
          )}
        </View>

        {/* RECUADRO 2: CONFIGURACIÓN DE CUENTA */}
        <View style={styles.infoContainer}>
          <Text style={styles.sectionTitle}>Configuración de Cuenta</Text>

          {/* CAMBIO DE CORREO */}
          <View style={styles.accountSubSection}>
            <View style={styles.fieldHeaderRow}>
              <Text style={styles.subSectionTitle}>Correo Electrónico</Text>
              {!isEditingEmail && (
                <TouchableOpacity style={styles.editButton} onPress={() => setIsEditingEmail(true)}>
                  <Ionicons name="pencil" size={18} color={COLORS.primary} />
                </TouchableOpacity>
              )}
            </View>

            {isEditingEmail ? (
              <View style={styles.formGroup}>
                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Correo Actual</Text>
                  <TextInput
                    style={[styles.input, styles.disabledInput]}
                    value={getUserCorreo()}
                    editable={false}
                  />
                </View>

                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Nuevo Correo</Text>
                  <TextInput
                    style={[styles.input, errorsEmail.newEmail && styles.inputError]}
                    value={newEmail}
                    onChangeText={handleChangeNewEmail}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    placeholder="Formato: correo@gmail.com"
                  />
                  {errorsEmail.newEmail ? <Text style={styles.errorText}>{errorsEmail.newEmail}</Text> : null}
                </View>

                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Contraseña Actual</Text>
                  <TextInput
                    style={[styles.input, errorsEmail.currentPasswordForEmail && styles.inputError]}
                    value={currentPasswordForEmail}
                    onChangeText={(t) => {
                      setCurrentPasswordForEmail(t);
                      setErrorsEmail((prev) => ({ ...prev, currentPasswordForEmail: '' }));
                    }}
                    secureTextEntry
                    placeholder="Ingresa tu contraseña para confirmar"
                  />
                  {errorsEmail.currentPasswordForEmail ? (
                    <Text style={styles.errorText}>{errorsEmail.currentPasswordForEmail}</Text>
                  ) : null}
                </View>

                <View style={styles.cardActions}>
                  <TouchableOpacity style={styles.saveGreenButton} onPress={handleSaveEmail}>
                    <Ionicons name="mail-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                    <Text style={styles.saveButtonText}>Actualizar Correo</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.cancelGrayButton}
                    onPress={() => {
                      setNewEmail('');
                      setCurrentPasswordForEmail('');
                      setErrorsEmail({});
                      setIsEditingEmail(false);
                    }}
                  >
                    <Ionicons name="close-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                    <Text style={styles.cancelButtonText}>Cancelar</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ) : (
              <View style={styles.fieldContainer}>
                <Text style={styles.infoValue}>{getUserCorreo() || 'No disponible'}</Text>
              </View>
            )}
          </View>

          {/* CAMBIO DE CONTRASEÑA */}
          <View style={styles.accountSubSectionLast}>
            <View style={styles.fieldHeaderRow}>
              <Text style={styles.subSectionTitle}>Contraseña</Text>
              {!isEditingPassword && (
                <TouchableOpacity style={styles.editButton} onPress={() => setIsEditingPassword(true)}>
                  <Ionicons name="pencil" size={18} color={COLORS.primary} />
                </TouchableOpacity>
              )}
            </View>

            {isEditingPassword ? (
              <View style={styles.formGroup}>
                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Contraseña Actual</Text>
                  <TextInput
                    style={[styles.input, errorsPassword.currentPassword && styles.inputError]}
                    value={currentPassword}
                    onChangeText={(t) => {
                      setCurrentPassword(t);
                      setErrorsPassword((prev) => ({ ...prev, currentPassword: '' }));
                    }}
                    secureTextEntry
                    placeholder="Ingresa tu contraseña actual"
                  />
                  {errorsPassword.currentPassword ? (
                    <Text style={styles.errorText}>{errorsPassword.currentPassword}</Text>
                  ) : null}
                </View>

                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Nueva Contraseña</Text>
                  <TextInput
                    style={[styles.input, errorsPassword.newPassword && styles.inputError]}
                    value={newPassword}
                    onChangeText={handleChangeNewPassword}
                    secureTextEntry
                    placeholder="6-15 caracteres (May, Num, C.Especial) x1"
                  />
                  {errorsPassword.newPassword ? <Text style={styles.errorText}>{errorsPassword.newPassword}</Text> : null}
                </View>

                <View style={styles.inputBlock}>
                  <Text style={styles.infoLabel}>Confirmar Nueva Contraseña</Text>
                  <TextInput
                    style={[styles.input, errorsPassword.confirmPassword && styles.inputError]}
                    value={confirmPassword}
                    onChangeText={handleChangeConfirmPassword}
                    secureTextEntry
                    placeholder="Confirma tu nueva contraseña"
                  />
                  {errorsPassword.confirmPassword ? (
                    <Text style={styles.errorText}>{errorsPassword.confirmPassword}</Text>
                  ) : null}
                </View>

                <View style={styles.cardActions}>
                  <TouchableOpacity style={styles.saveGreenButton} onPress={handleSavePassword}>
                    <Ionicons name="lock-closed-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                    <Text style={styles.saveButtonText}>Actualizar Contraseña</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.cancelGrayButton}
                    onPress={() => {
                      setCurrentPassword('');
                      setNewPassword('');
                      setConfirmPassword('');
                      setErrorsPassword({});
                      setIsEditingPassword(false);
                    }}
                  >
                    <Ionicons name="close-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                    <Text style={styles.cancelButtonText}>Cancelar</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ) : (
              <View style={styles.fieldContainer}>
                <Text style={styles.infoValue}>••••••••</Text>
              </View>
            )}
          </View>
        </View>
      </ScrollView>

      <Modal
        animationType="fade"
        transparent={true}
        visible={profileSuccessVisible}
        onRequestClose={() => setProfileSuccessVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.successModalCard}>
            <View style={styles.successIconContainer}>
              <Ionicons name="checkmark-circle" size={56} color={COLORS.success || '#22c55e'} />
            </View>
            <Text style={styles.successTitle}>Operación exitosa</Text>
            <Text style={styles.successText}>{profileSuccessMessage}</Text>

            <TouchableOpacity
              style={styles.successPrimaryButton}
              onPress={() => setProfileSuccessVisible(false)}
            >
              <Text style={styles.successPrimaryText}>Aceptar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      <Modal
        animationType="fade"
        transparent={true}
        visible={profileErrorVisible}
        onRequestClose={() => setProfileErrorVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.errorModalCard}>
            <View style={styles.errorIconContainer}>
              <Ionicons name="alert-circle" size={56} color="#E53E3E" />
            </View>
            <Text style={styles.errorTitle}>Error</Text>
            <Text style={styles.errorTextModal}>{profileErrorMessage}</Text>

            <TouchableOpacity
              style={styles.errorPrimaryButton}
              onPress={() => setProfileErrorVisible(false)}
            >
              <Text style={styles.errorPrimaryText}>Cerrar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* MODAL CAMBIAR FOTO DE PERFIL */}
      <Modal
        visible={isPhotoModalVisible}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setIsPhotoModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Cambiar Foto de Perfil</Text>
              <TouchableOpacity onPress={() => setIsPhotoModalVisible(false)}>
                <Ionicons name="close" size={20} color={COLORS.textSecondary || '#666'} />
              </TouchableOpacity>
            </View>

            <View style={styles.modalBody}>
              <View style={styles.modalAvatarContainer}>
                {tempImage ? (
                  <Image source={{ uri: tempImage }} style={styles.modalAvatar} />
                ) : (
                  <View style={styles.modalAvatarPlaceholder}>
                    <Text style={styles.modalAvatarText}>
                      {getUserNombres().charAt(0)?.toUpperCase() || 'E'}
                    </Text>
                  </View>
                )}
              </View>

              <View style={styles.inputBlock}>
                <Text style={styles.infoLabel}>Contraseña Actual</Text>
                <TextInput
                  style={[styles.input, !currentPasswordForAvatar && styles.inputError]}
                  value={currentPasswordForAvatar}
                  onChangeText={(text) => setCurrentPasswordForAvatar(text)}
                  secureTextEntry
                  placeholder="Ingresa tu contraseña para confirmar"
                />
              </View>

              <TouchableOpacity style={styles.selectImageButton} onPress={pickImage}>
                <Ionicons name="image-outline" size={18} color={COLORS.white} style={styles.buttonIcon} />
                <Text style={styles.selectImageButtonText}>Seleccionar Imagen</Text>
              </TouchableOpacity>

              <Text style={styles.modalSubtext}>
                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
              </Text>
            </View>

            <View style={styles.modalFooter}>
              <TouchableOpacity style={styles.saveGreenButton} onPress={handleSavePhoto}>
                <Ionicons name="image-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                <Text style={styles.saveButtonText}>Guardar Foto</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.cancelGrayButton} onPress={() => setIsPhotoModalVisible(false)}>
                <Ionicons name="close-outline" size={16} color={COLORS.white} style={styles.buttonIcon} />
                <Text style={styles.cancelButtonText}>Cancelar</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
  },
  profileHeader: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: theme.spacing.lg,
    paddingTop: Platform.OS === 'ios' ? theme.spacing.xl : theme.spacing.lg,
    paddingBottom: theme.spacing.xl,
    alignItems: 'center',
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    ...theme.shadow.md,
  },
  avatarContainer: {
    position: 'relative',
    marginBottom: theme.spacing.sm,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: COLORS.white,
    justifyContent: 'center',
    alignItems: 'center',
    ...theme.shadow.md,
  },
  avatarText: {
    ...theme.typography.h1,
    color: COLORS.primary,
  },
  cameraIcon: {
    position: 'absolute',
    right: 2,
    bottom: 2,
    backgroundColor: COLORS.primary,
    borderRadius: 14,
    width: 28,
    height: 28,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: COLORS.white,
  },
  userName: {
    ...theme.typography.h2,
    color: COLORS.white,
    marginBottom: 2,
    textAlign: 'center',
  },
  userEmail: {
    ...theme.typography.body,
    color: COLORS.white,
    opacity: 0.9,
    textAlign: 'center',
  },
  infoContainer: {
    backgroundColor: COLORS.white,
    borderRadius: theme.radius.lg,
    marginHorizontal: theme.spacing.lg,
    marginTop: theme.spacing.md,
    marginBottom: theme.spacing.md,
    padding: theme.spacing.lg,
    ...theme.shadow.sm,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: theme.spacing.md,
    paddingBottom: theme.spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
  },
  sectionTitle: {
    ...theme.typography.h3,
    color: COLORS.primary,
  },
  subSectionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textSecondary,
  },
  infoItem: {
    marginBottom: theme.spacing.md,
  },
  fieldContainer: {
    backgroundColor: COLORS.background || '#F5F7FA',
    padding: theme.spacing.sm + 2,
    borderRadius: theme.radius.sm || 6,
    marginTop: 4,
  },
  infoLabel: {
    fontSize: 13,
    color: COLORS.textSecondary,
    fontWeight: '600',
    marginBottom: 4,
  },
  infoValue: {
    ...theme.typography.body,
    color: COLORS.text,
  },
  formGroup: {
    marginTop: 4,
  },
  inputBlock: {
    marginBottom: 12,
  },
  input: {
    backgroundColor: COLORS.white,
    borderWidth: 1,
    borderColor: COLORS.border || '#DDD',
    borderRadius: theme.radius.sm || 6,
    padding: theme.spacing.sm,
    fontSize: 14,
    color: COLORS.text,
  },
  inputError: {
    borderColor: '#DC3545',
  },
  errorText: {
    color: '#DC3545',
    fontSize: 11,
    marginTop: 3,
    fontStyle: 'italic',
  },
  disabledInput: {
    backgroundColor: '#E9ECEF',
    color: '#6C757D',
  },
  editButton: {
    padding: 6,
  },
  accountSubSection: {
    marginBottom: theme.spacing.lg,
    paddingBottom: theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
  },
  accountSubSectionLast: {
    marginBottom: theme.spacing.sm,
  },
  fieldHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  cardActions: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: theme.spacing.sm,
  },
  saveGreenButton: {
    flexDirection: 'row',
    backgroundColor: '#28A745',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 6,
    alignItems: 'center',
    marginRight: 10,
  },
  cancelGrayButton: {
    flexDirection: 'row',
    backgroundColor: '#6C757D',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 6,
    alignItems: 'center',
  },
  buttonIcon: {
    marginRight: 6,
  },
  saveButtonText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: 'bold',
  },
  cancelButtonText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: 'bold',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: theme.spacing.lg,
  },
  modalCard: {
    width: '100%',
    maxWidth: 380,
    backgroundColor: COLORS.white,
    borderRadius: 12,
    padding: theme.spacing.lg,
    ...theme.shadow.lg,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingBottom: theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  modalBody: {
    alignItems: 'center',
    paddingVertical: theme.spacing.lg,
  },
  modalAvatarContainer: {
    marginBottom: theme.spacing.md,
  },
  modalAvatar: {
    width: 110,
    height: 110,
    borderRadius: 55,
  },
  modalAvatarPlaceholder: {
    width: 110,
    height: 110,
    borderRadius: 55,
    backgroundColor: '#F0F4F8',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  modalAvatarText: {
    fontSize: 40,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  selectImageButton: {
    flexDirection: 'row',
    backgroundColor: '#3182CE',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 6,
    alignItems: 'center',
    marginBottom: 10,
  },
  selectImageButtonText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '600',
  },
  modalSubtext: {
    fontSize: 11,
    color: COLORS.textSecondary || '#718096',
    textAlign: 'center',
  },
  modalFooter: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: theme.spacing.md,
    borderTopWidth: 1,
    borderTopColor: COLORS.borderLight,
  },
  successModalCard: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
    ...theme.shadow.lg,
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
  successTitle: {
    fontSize: 22,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 8,
  },
  successText: {
    color: '#4B5563',
    fontSize: 15,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 20,
  },
  successPrimaryButton: {
    width: '100%',
    borderRadius: 12,
    backgroundColor: '#22C55E',
    paddingVertical: 12,
    alignItems: 'center',
  },
  successPrimaryText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
  errorModalCard: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
    ...theme.shadow.lg,
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
  errorTitle: {
    fontSize: 22,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 8,
  },
  errorTextModal: {
    color: '#4B5563',
    fontSize: 15,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 20,
  },
  errorPrimaryButton: {
    width: '100%',
    borderRadius: 12,
    backgroundColor: '#E53E3E',
    paddingVertical: 12,
    alignItems: 'center',
  },
  errorPrimaryText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
});

export default ProfileScreen;