/**
 * Función para cargar imágenes de manera segura
 * @param {string} imageName - Nombre del archivo de imagen
 * @returns {number|object} - Retorna el recurso de imagen o un objeto con uri
 */
import { buildProductImageUrl } from '../config/apiConfig';

export const getImageSource = (imageName) => {
  try {
    // Primero intentamos cargar la imagen directamente
    const imageMap = {
      'FONDO.jpg': require('../../src/assets/images/FONDO.jpg'),
    };

    if (imageMap[imageName]) {
      return imageMap[imageName];
    }

    // Si no está en el mapa, usamos buildProductImageUrl para obtener la URL correcta
    const imageUrl = buildProductImageUrl(imageName);
    if (imageUrl) {
      return { uri: imageUrl };
    }

    // Retornamos null como respaldo
    return null;
  } catch (error) {
    console.error('Error loading image:', error);
    // Retornamos null como respaldo
    return null;
  }
};
