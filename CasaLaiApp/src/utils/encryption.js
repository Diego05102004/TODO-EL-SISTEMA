/**
 * Cifrado híbrido RSA+AES-256-CBC para proteger datos sensibles enviados a la API.
 * La clave privada y el descifrado permanecen exclusivamente en el servidor.
 */

import forge from 'node-forge';
import { ENCRYPTION_CONFIG } from '../config/encryptionConfig.js';

class Encryption {
  constructor() {
    this.rsaPublicKey = ENCRYPTION_CONFIG.RSA_PUBLIC_KEY;
    this.loadRSAKey();
  }

  loadRSAKey() {
    if (typeof this.rsaPublicKey !== 'string' || !this.rsaPublicKey.trim()) {
      throw new Error('La clave pública RSA de la API no está configurada.');
    }

    this.parsePemKey(this.rsaPublicKey);
  }

  parsePemKey(pemKey) {
    try {
      return forge.pki.publicKeyFromPem(pemKey);
    } catch (error) {
      throw new Error(`Error al parsear la clave pública RSA: ${error.message}`);
    }
  }

  generateRandomBytes(length) {
    return forge.random.getBytesSync(length);
  }

  encrypt(data) {
    if (data === null || data === undefined || data === '') {
      return data;
    }

    const plainText = String(data);
    const aesKey = this.generateRandomBytes(32);
    const iv = this.generateRandomBytes(16);
    const cipher = forge.cipher.createCipher('AES-CBC', aesKey);
    cipher.start({ iv });
    cipher.update(forge.util.createBuffer(forge.util.encodeUtf8(plainText)));

    if (!cipher.finish()) {
      throw new Error('No se pudieron cifrar los datos con AES-256-CBC.');
    }

    const encryptedAesKey = this.parsePemKey(this.rsaPublicKey)
      .encrypt(aesKey, 'RSAES-PKCS1-V1_5');
    const keyLength = encryptedAesKey.length;
    const keyLengthBytes = String.fromCharCode(
      (keyLength >>> 24) & 0xFF,
      (keyLength >>> 16) & 0xFF,
      (keyLength >>> 8) & 0xFF,
      keyLength & 0xFF
    );

    return forge.util.encode64(
      keyLengthBytes + encryptedAesKey + iv + cipher.output.getBytes()
    );
  }

  encryptArray(data, fields) {
    if (!data || typeof data !== 'object') {
      return data;
    }

    const result = { ...data };
    for (const field of fields) {
      if (
        Object.prototype.hasOwnProperty.call(result, field)
        && result[field] !== undefined
        && result[field] !== null
      ) {
        result[field] = this.encrypt(result[field]);
      }
    }

    return result;
  }
}

export const encryption = new Encryption();
export default encryption;
