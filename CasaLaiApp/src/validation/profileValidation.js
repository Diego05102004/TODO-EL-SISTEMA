// Expresiones regulares adaptadas
export const REGEX = {
  USERNAME: /^[a-zA-Z0-9]{4,20}$/,
  NAME: /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
  EMAIL: /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
  PASSWORD: /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\])[A-Za-z0-9\u00f1\u00d1\u00E0-\u00FC!@#$%^&*()_+\-=\{}\[\]|:;"'<>.,?\/\\]{6,15}$/,
  PHONE: /^\d{4}-\d{3}-\d{4}$/,
};

// Elimina espacios múltiples consecutivos
export const cleanDoubleSpaces = (text = '') => {
  return text.replace(/\s{2,}/g, ' ');
};

// Filtro en tiempo real: Nombre de Usuario (Solo letras mayúsculas/minúsculas y números)
export const filterUsername = (text = '') => {
  return text.replace(/[^a-zA-Z0-9]/g, '');
};

// Filtro en tiempo real: Nombres y Apellidos (Solo letras, acentos y espacios)
export const filterAlpha = (text = '') => {
  const onlyAlpha = text.replace(/[^a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]/g, '');
  return cleanDoubleSpaces(onlyAlpha);
};

// Mascarilla y formateador para teléfono (04XX-XXX-XXXX)
export const formatPhoneNumber = (text = '') => {
  let digits = text.replace(/\D/g, '');
  if (digits.length > 11) digits = digits.slice(0, 11);

  if (digits.length > 7) {
    return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
  } else if (digits.length > 4) {
    return `${digits.slice(0, 4)}-${digits.slice(4)}`;
  }
  return digits;
};

// Funciones de validación
export const validateUsername = (username) => {
  if (!username || !username.trim()) return 'El nombre de usuario es obligatorio';
  if (!REGEX.USERNAME.test(username)) {
    return '*El usuario debe tener entre 4 y 20 caracteres alfanuméricos*';
  }
  return '';
};

export const validateName = (name) => {
  if (!name || !name.trim()) return 'El nombre es obligatorio';
  if (!REGEX.NAME.test(name)) {
    return '*Solo letras, de 2 a 50 caracteres*';
  }
  return '';
};

export const validateLastName = (lastName) => {
  if (!lastName || !lastName.trim()) return 'El apellido es obligatorio';
  if (!REGEX.NAME.test(lastName)) {
    return '*Solo letras, de 2 a 50 caracteres*';
  }
  return '';
};

export const validatePhone = (phone) => {
  if (!phone || !phone.trim()) return 'El teléfono es obligatorio';
  if (!REGEX.PHONE.test(phone)) {
    return '*Formato válido: 0400-000-0000*';
  }
  return '';
};

export const validateEmail = (email) => {
  if (!email || !email.trim()) return 'El correo electrónico es obligatorio';
  if (!REGEX.EMAIL.test(email)) {
    return '*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*';
  }
  return '';
};

export const validatePassword = (password) => {
  if (!password) return 'La contraseña es obligatoria';
  if (!REGEX.PASSWORD.test(password)) {
    return '*6-15 caracteres, con al menos 1 mayúscula, 1 número y 1 caracter especial*';
  }
  return '';
};

export const validateConfirmPassword = (password, confirmPassword) => {
  if (!confirmPassword) return '*Debe ingresar la contraseña nuevamente*';
  if (password !== confirmPassword) {
    return '*Las contraseñas no coinciden*';
  }
  return '';
};

export const validateProfileImage = (asset) => {
  if (!asset) return 'Debes seleccionar una imagen';
  if (asset.mimeType && !asset.mimeType.toLowerCase().startsWith('image/')) {
    return 'El archivo seleccionado no es una imagen válida';
  }
  if (asset.fileSize && asset.fileSize > 2097152) {
    return 'La imagen debe ser menor a 2MB';
  }
  return '';
};

// Conserva el formato original cuando el selector no informa explícitamente el MIME.
export const getProfileImageMimeType = (asset) => {
  if (asset?.mimeType?.toLowerCase().startsWith('image/')) {
    return asset.mimeType.toLowerCase();
  }

  const extension = asset?.fileName?.split('.').pop()?.toLowerCase();
  const mimeTypes = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    gif: 'image/gif',
    webp: 'image/webp',
    bmp: 'image/bmp',
    heic: 'image/heic',
    heif: 'image/heif',
    avif: 'image/avif',
  };

  return mimeTypes[extension] || 'application/octet-stream';
};