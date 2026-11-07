import { buildProductImageUrl } from '../config/apiConfig';
import { secureGet, securePost } from '../utils/api';

const ensureSuccess = (response) => {
  if (response?.status !== 'success' || !response?.data) {
    throw new Error(response?.message || 'El servidor devolvió una respuesta inválida para el carrito.');
  }

  return response.data;
};

const normalizeImageUrl = (image) => {
  if (!image || typeof image !== 'string') {
    return null;
  }

  return buildProductImageUrl(image);
};

const normalizeCart = (data) => {
  if (!Array.isArray(data.items)) {
    throw new Error('La respuesta del servidor no contiene una lista válida de productos.');
  }

  const items = data.items.map((item) => {
    const id = Number(item.id_producto ?? item.id);
    const cartItemId = Number(item.id_carrito_detalle ?? item.cartItemId);
    const price = Number(item.price ?? item.precio ?? 0);
    const quantity = Number(item.quantity ?? item.cantidad);

    if (!Number.isInteger(id) || id <= 0
      || !Number.isInteger(cartItemId) || cartItemId <= 0
      || !Number.isInteger(quantity) || quantity <= 0
      || !Number.isFinite(price) || price < 0) {
      throw new Error('La respuesta del servidor contiene un producto de carrito inválido.');
    }

    return {
      ...item,
      id,
      cartItemId,
      name: item.name || item.nombre || item.nombre_producto || 'Producto',
      price,
      quantity,
      stock: Number(item.stock ?? item.existencia ?? 0),
      image: normalizeImageUrl(item.image || item.imagen),
      isCombo: false,
    };
  });
  const total = Number(data.total);
  if (!Number.isFinite(total) || total < 0) {
    throw new Error('La respuesta del servidor contiene un total de carrito inválido.');
  }

  return {
    items,
    total,
  };
};

const submit = async (payload) => {
  const response = await securePost('/carrito.php', payload, null, undefined, false);
  return normalizeCart(ensureSuccess(response));
};

const normalizeProducts = (product, quantity) => {
  const isCombo = Boolean(product?.isCombo || product?.type === 'combo');

  if (isCombo) {
    const comboProducts = product.comboProducts ?? product.products ?? product.productos ?? [];
    if (!Array.isArray(comboProducts) || comboProducts.length === 0) {
      throw new Error('Este combo no tiene productos disponibles para agregar al carrito.');
    }

    return comboProducts.map((item) => ({
      id_producto: Number(item.id_producto ?? item.id),
      cantidad: Number(item.quantity ?? item.cantidad ?? 1) * quantity,
    }));
  }

  return [{
    id_producto: Number(product?.id_producto ?? product?.id),
    cantidad: quantity,
  }];
};

export const getCart = async () => {
  const response = await secureGet('/carrito.php', { funcion: 'consultar' });
  return normalizeCart(ensureSuccess(response));
};

export const addToCart = async (product, quantity = 1) => {
  const normalizedQuantity = Number(quantity);
  if (!Number.isInteger(normalizedQuantity) || normalizedQuantity < 1 || normalizedQuantity > 999) {
    throw new Error('La cantidad debe ser un número mayor que cero.');
  }

  const items = normalizeProducts(product, normalizedQuantity);
  if (items.some((item) => !Number.isInteger(item.id_producto) || item.id_producto <= 0)) {
    throw new Error('No se pudo identificar uno de los productos seleccionados.');
  }

  return submit({ funcion: 'agregar', items });
};

export const updateCartItem = async (cartItemId, quantity) => {
  const normalizedQuantity = Number(quantity);
  if (!Number.isInteger(Number(cartItemId)) || Number(cartItemId) <= 0) {
    throw new Error('No se pudo identificar el producto del carrito.');
  }
  if (!Number.isInteger(normalizedQuantity) || normalizedQuantity < 1 || normalizedQuantity > 999) {
    throw new Error('La cantidad debe ser un número mayor que cero.');
  }

  return submit({
    funcion: 'actualizar',
    id_carrito_detalle: Number(cartItemId),
    cantidad: normalizedQuantity,
  });
};

export const removeCartItem = async (cartItemId) => {
  if (!Number.isInteger(Number(cartItemId)) || Number(cartItemId) <= 0) {
    throw new Error('No se pudo identificar el producto del carrito.');
  }

  return submit({
    funcion: 'eliminar',
    id_carrito_detalle: Number(cartItemId),
  });
};

export const clearRemoteCart = async () => submit({ funcion: 'vaciar' });

export default {
  getCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  clearRemoteCart,
};
