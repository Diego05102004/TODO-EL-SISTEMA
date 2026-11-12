/**
 * Configuración de claves RSA para encriptación híbrida RSA+AES-256-CBC
 * Solo la clave pública se distribuye con el cliente.
 */

export const ENCRYPTION_CONFIG = {
  // Clave pública RSA (usada para encriptar en el cliente)
  RSA_PUBLIC_KEY: `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzQgbNTHIAXC3Z6l8hJ+o
NtgMUjjxptku1XKNAEV7bldmhgYP88SbKpfKPKHMcrFQJ3iZkilKP2sUvBoMIred
aXxxFybDQS+Q+3rktSbWDD2WRz/52ACwi4rJVKTkG70Bl2t7Wnxw4csqOzy0TV63
40QyNCT9yL/6gAnh1RSewPGSFvTHoANWPM8bh7FA6jfBUdm1dVUeqXQMUoueBWka
bcJtTgPk8fjjgHqtCDIpKbPrCd67Rubc3pHJSS/IpNrFUSrbSWAzLKaPD831RojS
29k2B/B86Pcntni7L1mI18iwHJA4tmzp4BksRRr7YRW/v6NzPMjhgTuGRwfCpJGP
jwIDAQAB
-----END PUBLIC KEY-----`,

  // Configuración AES
  AES_METHOD: 'AES-256-CBC',
  AES_KEY_LENGTH: 32, // 256 bits
  IV_LENGTH: 16, // 128 bits
};

export default ENCRYPTION_CONFIG;