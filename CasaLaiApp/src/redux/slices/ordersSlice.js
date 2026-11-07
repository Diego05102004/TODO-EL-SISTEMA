import { createSlice } from '@reduxjs/toolkit';

const initialState = {
  pedidos: [],
  lastFetch: null,
  isLoading: false,
  error: null,
};

/**
 * Compara dos arrays de pedidos para detectar cambios
 * @param {Array} oldPedidos - Pedidos anteriores
 * @param {Array} newPedidos - Pedidos nuevos
 * @returns {boolean} - true si hay cambios, false si son iguales
 */
const hasPedidosChanged = (oldPedidos, newPedidos) => {
  // Si uno está vacío y el otro no, hay cambio
  if (oldPedidos.length === 0 && newPedidos.length > 0) return true;
  if (oldPedidos.length > 0 && newPedidos.length === 0) return true;

  // Si las longitudes son diferentes, hay cambio
  if (oldPedidos.length !== newPedidos.length) return true;

  // Comparar cada pedido
  for (let i = 0; i < oldPedidos.length; i++) {
    const oldPedido = oldPedidos[i];
    const newPedido = newPedidos[i];

    // Comparar campos clave que indican cambios
    if (
      oldPedido.id !== newPedido.id ||
      oldPedido.numero !== newPedido.numero ||
      oldPedido.estado !== newPedido.estado ||
      oldPedido.total !== newPedido.total ||
      oldPedido.fecha !== newPedido.fecha ||
      oldPedido.metodo !== newPedido.metodo
    ) {
      return true;
    }

    // Comparar cliente_detalle si existe
    if (oldPedido.clienteDetalle || newPedido.clienteDetalle) {
      const oldDetalle = oldPedido.clienteDetalle || {};
      const newDetalle = newPedido.clienteDetalle || {};

      if (
        oldDetalle.nombre !== newDetalle.nombre ||
        oldDetalle.cedula !== newDetalle.cedula ||
        oldDetalle.telefono !== newDetalle.telefono ||
        oldDetalle.direccion !== newDetalle.direccion
      ) {
        return true;
      }
    }

    // Comparar número de items
    const oldItemsLength = Array.isArray(oldPedido.items) ? oldPedido.items.length : 0;
    const newItemsLength = Array.isArray(newPedido.items) ? newPedido.items.length : 0;

    if (oldItemsLength !== newItemsLength) {
      return true;
    }
  }

  return false;
};

const ordersSlice = createSlice({
  name: 'orders',
  initialState,
  reducers: {
    setPedidos: (state, action) => {
      const newPedidos = action.payload;

      // Si no hay pedidos nuevos, no hacer nada
      if (!newPedidos || newPedidos.length === 0) {
        state.pedidos = [];
        state.lastFetch = null;
        return;
      }

      // Comparar con los pedidos actuales
      const changed = hasPedidosChanged(state.pedidos, newPedidos);

      if (changed) {
        console.log('[Orders] Cambios detectados, actualizando caché');
        state.pedidos = newPedidos;
        state.lastFetch = new Date().toISOString();
      } else {
        console.log('[Orders] Sin cambios, manteniendo caché');
      }

      state.isLoading = false;
      state.error = null;
    },
    setLoading: (state, action) => {
      state.isLoading = action.payload;
    },
    setError: (state, action) => {
      state.error = action.payload;
      state.isLoading = false;
    },
    clearPedidos: (state) => {
      state.pedidos = [];
      state.lastFetch = null;
      state.error = null;
    },
    // Forzar actualización ignorando caché
    forceUpdatePedidos: (state, action) => {
      console.log('[Orders] Forzando actualización de caché');
      state.pedidos = action.payload;
      state.lastFetch = new Date().toISOString();
      state.isLoading = false;
      state.error = null;
    },
  },
});

export const {
  setPedidos,
  setLoading,
  setError,
  clearPedidos,
  forceUpdatePedidos,
} = ordersSlice.actions;

export const selectPedidos = (state) => state.orders.pedidos;
export const selectPedidosLoading = (state) => state.orders.isLoading;
export const selectPedidosError = (state) => state.orders.error;
export const selectLastFetch = (state) => state.orders.lastFetch;

export default ordersSlice.reducer;
