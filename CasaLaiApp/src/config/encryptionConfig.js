/**
 * Configuración de claves RSA para encriptación híbrida RSA+AES-256-CBC
 * Solo la clave pública se distribuye con el cliente.
 */

export const ENCRYPTION_CONFIG = {
  // Clave pública RSA (usada para encriptar en el cliente)
  RSA_PUBLIC_KEY: `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmzzy/izR98o+ATCSuSO7
BIJOMJaO+ZIgs8N2iicuZdI8P+WDp2IF/fRsvYZJ4KCZDPBacMaVjzqELqWbUcvg
obE/SUGy+1bTMEEaBhg9W9z/7Ptp4GH8bVSosqKQJpKVq4Uk8tKwgEHdtQWqXchn
p0TQhQ/GMgfhYaVYpk6KCSZqlXBGLPGUBaQg2wcmCx6OGngTwlj2h7gUxwz/ZdX0
lNO5F6UdzvDsnaCFVwoXo2dLDeaam+PAUdHivguTY24iijiT0puPu0yar7u1Qywa
hIN/earblgE+SCH/g474DHhzTVSxvRwHyTRs2L4s4aZs8IRerzS5Rp64gi3owBB3
ywIDAQAB
-----END PUBLIC KEY-----`,

  // Configuración AES
  AES_METHOD: 'AES-256-CBC',
  AES_KEY_LENGTH: 32, // 256 bits
  IV_LENGTH: 16, // 128 bits
};

export default ENCRYPTION_CONFIG;