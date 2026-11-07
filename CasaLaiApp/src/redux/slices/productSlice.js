import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { InvocarMetodo } from '../../utils/comunicadorApi';

const normalizeProduct = (product = {}) => ({
  ...product,
  id: Number(product.id_producto ?? product.id ?? 0),
  name: product.nombre || product.name || 'Producto',
  description: product.descripcion || product.description || '',
  price: Number(product.precio ?? 0),
  stock: Number(product.stock ?? 0),
  image: product.imagen || product.image || null,
  type: 'product',
  isCombo: false,
});

const normalizeCombo = (combo = {}) => {
  const items = Array.isArray(combo.productos) ? combo.productos : [];
  const price = Number(combo.precio_final ?? combo.precio_total ?? combo.precio ?? 0);

  return {
    ...combo,
    id: Number(combo.id_combo ?? combo.id ?? 0),
    name: combo.nombre_combo || combo.name || combo.nombre || 'Combo',
    description: combo.descripcion || combo.description || '',
    price,
    stock: Number(combo.stock ?? 0),
    image: combo.imagen || combo.image || null,
    type: 'combo',
    isCombo: true,
    products: items.map((item) => ({
      ...item,
      id: Number(item.id_producto ?? item.id ?? 0),
      name: item.nombre_producto || item.name || item.nombre || 'Producto',
      quantity: Number(item.cantidad ?? 1),
      price: Number(item.precio ?? 0),
    })),
  };
};

export const fetchProducts = createAsyncThunk(
  'products/fetchProducts',
  async (params = {}, { rejectWithValue }) => {
    try {
      const response = await InvocarMetodo('productos.php', 'default', params);
      console.log('[Productos] Respuesta de todos los productos:', response);
      
      if (response.status === 'success') {
        const rawProducts = Array.isArray(response.data?.productos) ? response.data.productos : [];
        const rawCombos = Array.isArray(response.data?.combos) ? response.data.combos : [];

        return {
          products: rawProducts.map(normalizeProduct),
          combos: rawCombos.map(normalizeCombo),
          total: Number(response.data?.total ?? (rawProducts.length + rawCombos.length)),
        };
      } else {
        return rejectWithValue(response.message || 'Error al obtener productos');
      }
    } catch (error) {
      return rejectWithValue(error.message || 'Error al obtener productos');
    }
  }
);

export const fetchProductsByCategory = createAsyncThunk(
  'products/fetchProductsByCategory',
  async (categoriaId, { rejectWithValue }) => {
    try {
      const response = await InvocarMetodo(
        'productos.php',
        'por_categoria',
        { categoria: categoriaId }
      );
      console.log('[Productos] Respuesta por categoria:', {
        categoriaId,
        response,
      });
      if (response.status === 'success') {
        return {
          products: response.data.productos,
          total: response.data.total,
          categoria: categoriaId
        };
      } else {
        return rejectWithValue(response.message || 'Error al obtener productos de la categoría');
      }
    } catch (error) {
      return rejectWithValue(error.message || 'Error al obtener productos de la categoría');
    }
  }
);

export const fetchProductById = createAsyncThunk(
  'products/fetchProductById',
  async (productId, { rejectWithValue }) => {
    try {
      const response = await InvocarMetodo(
        'productos.php',
        'por_id',
        { id: productId }
      );
      console.log('[Productos] Respuesta por ID:', { productId, response });
      
      if (response.status === 'success') {
        const product = response.data.producto || response.data.productos?.[0];
        return {
          product: product ? {
            ...product,
            name: product.nombre,
            description: product.descripcion || '',
            price: Number(product.precio) || 0,
            image: product.imagen || null,
          } : null
        };
      } else {
        return rejectWithValue(response.message || 'Error al obtener el producto');
      }
    } catch (error) {
      return rejectWithValue(error.message || 'Error al obtener el producto');
    }
  }
);

const initialState = {
  products: [],
  combos: [],
  currentProduct: null,
  loading: false,
  error: null,
  total: 0,
  categoria: null
};

const productSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    clearCurrentProduct: (state) => {
      state.currentProduct = null;
    },
  },
  extraReducers: (builder) => {
    // Fetch Products
    builder.addCase(fetchProducts.pending, (state) => {
      state.loading = true;
      state.error = null;
    });
    builder.addCase(fetchProducts.fulfilled, (state, action) => {
      state.loading = false;
      state.products = action.payload.products;
      state.combos = action.payload.combos;
      state.total = action.payload.total;
      state.error = null;
    });
    builder.addCase(fetchProducts.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.products = [];
    });
    
    // Fetch Products by Category
    builder.addCase(fetchProductsByCategory.pending, (state) => {
      state.loading = true;
      state.error = null;
    });
    builder.addCase(fetchProductsByCategory.fulfilled, (state, action) => {
      state.loading = false;
      state.products = (action.payload.products || []).map(normalizeProduct);
      state.combos = (action.payload.combos || []).map(normalizeCombo);
      state.total = action.payload.total;
      state.categoria = action.payload.categoria;
      state.error = null;
    });
    builder.addCase(fetchProductsByCategory.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.products = [];
    });
    
    // Fetch Product by ID
    builder.addCase(fetchProductById.pending, (state) => {
      state.loading = true;
      state.error = null;
    });
    builder.addCase(fetchProductById.fulfilled, (state, action) => {
      state.loading = false;
      state.currentProduct = action.payload.product;
      state.error = null;
    });
    builder.addCase(fetchProductById.rejected, (state, action) => {
      state.loading = false;
      state.error = action.payload;
      state.currentProduct = null;
    });
  },
});

export const { clearError, clearCurrentProduct } = productSlice.actions;
export default productSlice.reducer;
