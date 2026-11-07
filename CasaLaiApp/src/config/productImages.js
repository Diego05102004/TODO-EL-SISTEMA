// Mapeo de imágenes de productos
export const PRODUCT_IMAGES = {
  // Imágenes por ID de producto
  'f1': require('../assets/images/producto-1.jpg'),
  'f2': require('../assets/images/producto-2.jpg'),
  'f3': require('../assets/images/producto-3.jpg'),
  'i1': require('../assets/images/producto-4.jpg'),
  'i2': require('../assets/images/producto-5.jpg'),
  'i3': require('../assets/images/producto-6.jpg'),
  'i4': require('../assets/images/producto-7.jpg'),
  't1': require('../assets/images/producto-8.jpg'),
  't2': require('../assets/images/producto-1.jpg'),
  't3': require('../assets/images/producto-2.jpg'),
  'c1': require('../assets/images/producto-3.jpg'),
  'c2': require('../assets/images/producto-4.jpg'),
  'c3': require('../assets/images/producto-5.jpg'),
  // Imagen por defecto
  'default': require('../assets/images/producto-default.jpg')
};

// Función para obtener la imagen de un producto
export const getProductImage = (productId) => {
  return PRODUCT_IMAGES[productId] || PRODUCT_IMAGES['default'];
};

export default PRODUCT_IMAGES;
