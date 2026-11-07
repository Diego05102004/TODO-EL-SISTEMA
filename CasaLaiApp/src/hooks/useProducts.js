import { useState, useEffect } from 'react';
import { InvocarMetodo } from '../utils/comunicadorApi';
import { buildProductImageUrl } from '../config/apiConfig';

// Mapeo de imágenes de productos
const productImages = {
  'producto-1': require('../assets/images/producto-1.jpg'),
  'producto-2': require('../assets/images/producto-2.jpg'),
  'producto-3': require('../assets/images/producto-3.jpg'),
  'producto-4': require('../assets/images/producto-4.jpg'),
  'producto-5': require('../assets/images/producto-5.jpg'),
  'producto-6': require('../assets/images/producto-6.jpg'),
  'producto-7': require('../assets/images/producto-7.jpg'),
  'producto-8': require('../assets/images/producto-8.jpg'),
  'default': require('../assets/images/producto-default.jpg'),
};

// Función para obtener la imagen de un producto
const resolveProductImage = (imageValue) => {
  if (typeof imageValue !== 'string' || !imageValue.trim()) {
    return {
      image: null,
      imageSource: productImages.default,
    };
  }

  const value = imageValue.trim();
  const localImage = productImages[value];
  if (localImage) {
    return {
      image: value,
      imageSource: localImage,
    };
  }

  const imageUrl = buildProductImageUrl(value);

  return {
    image: imageUrl,
    imageSource: { uri: imageUrl },
  };
};

const adaptProduct = (product) => {
  const image = resolveProductImage(product.imagen || product.image);

  return {
    ...product,
    id: Number(product.id_producto ?? product.id ?? 0),
    name: product.nombre || product.name || 'Producto',
    description: product.descripcion || product.description || '',
    brand: product.marca || product.nombre_marca || product.marca_nombre || product.brand || '',
    model: product.modelo || product.nombre_modelo || product.modelo_nombre || product.model || '',
    features: product.caracteristicas || product.caracteristica || product.features || '',
    price: Number(product.precio ?? product.price) || 0,
    stock: Number(product.stock ?? product.existencia) || 0,
    existencia: Number(product.stock ?? product.existencia) || 0,
    category: product.categoria_nombre || product.categoria || product.category || String(product.categoria_id || 'otros'),
    ...image,
  };
};

const adaptCombo = (combo = {}) => {
  const image = resolveProductImage(combo.imagen || combo.image);

  return {
    ...combo,
    id: Number(combo.id_combo ?? combo.id ?? 0),
    name: combo.nombre_combo || combo.nombre || combo.name || 'Combo',
    description: combo.descripcion || combo.description || '',
    price: Number(combo.precio_final ?? combo.precio_total ?? combo.precio ?? 0),
    ...image,
    products: Array.isArray(combo.productos) ? combo.productos.map((product) => ({
      ...product,
      id: Number(product.id_producto ?? product.id ?? 0),
      name: product.nombre_producto || product.nombre || product.name || 'Producto',
      quantity: Number(product.cantidad ?? product.quantity ?? 1),
      price: Number(product.precio ?? product.price ?? 0),
      stock: Number(product.stock ?? product.existencia) || 0,
      existencia: Number(product.stock ?? product.existencia) || 0,
      imagen: resolveProductImage(product.imagen).image,
    })) : [],
    category: 'combos',
    type: 'combo',
    isCombo: true,
  };
};

const groupProductsByCategory = (products) => products.reduce((groups, product) => {
  const category = product.category.toLowerCase();
  if (!groups[category]) {
    groups[category] = [];
  }
  groups[category].push(product);
  return groups;
}, {});

const sampleProducts = {
  featured: [
    {
      id: 'f1',
      name: 'Oferta Especial - Impresora EPSON',
      price: 199.99,
      originalPrice: 249.99,
      image: 'producto-1',
      imageSource: productImages['producto-1'],
      category: 'impresoras',
      description: 'Impresora multifunción EPSON con 30% de descuento',
      isFeatured: true
    },
    {
      id: 'f2',
      name: 'Paquete Tintas EPSON',
      price: 49.99,
      originalPrice: 69.99,
      image: 'producto-2',
      imageSource: productImages['producto-2'],
      category: 'tintas',
      description: 'Paquete de tintas EPSON para impresoras de inyección',
      isFeatured: true
    },
    {
      id: 'f3',
      name: 'Cartucho HP 301XL',
      price: 24.99,
      originalPrice: 34.99,
      image: 'producto-3',
      imageSource: productImages['producto-3'],
      category: 'cartuchos',
      description: 'Cartucho de tóner negro de alta capacidad',
      isFeatured: true
    }
  ],
  impresoras: [
    {
      id: 'i1',
      name: 'Impresora EPSON L380',
      image: 'producto-4',
      imageSource: productImages['producto-4'],
      price: 249.99,
      category: 'impresoras',
      description: 'Impresora multifunción con sistema de tinta continua',
      isFeatured: false
    },
    {
      id: 'i2',
      name: 'HP LaserJet Pro M404dn',
      image: 'producto-5',
      imageSource: productImages['producto-5'],
      price: 329.99,
      category: 'impresoras',
      description: 'Impresora láser monocromática de alto rendimiento',
      isFeatured: false
    },
    {
      id: 'i3',
      name: 'EPSON EcoTank L3150',
      image: 'producto-6',
      imageSource: productImages['producto-6'],
      price: 299.99,
      category: 'impresoras',
      description: 'Impresora de tanque de tinta de alta capacidad',
      isFeatured: false
    },
    {
      id: 'i4',
      name: 'Brother HL-L2350DW',
      image: 'producto-7',
      imageSource: productImages['producto-7'],
      price: 199.99,
      category: 'impresoras',
      description: 'Impresora láser inalámbrica compacta',
      isFeatured: false
    }
  ],
  tintas: [
    {
      id: 't1',
      name: 'Tinta EPSON 664 Negro',
      image: 'producto-8',
      imageSource: productImages['producto-8'],
      price: 12.99,
      category: 'tintas',
      description: 'Cartucho de tinta negra para impresoras EPSON',
      isFeatured: false
    },
    {
      id: 't2',
      name: 'Tinta HP 664 Tricolor',
      image: 'producto-1',
      imageSource: productImages['producto-1'],
      price: 15.99,
      category: 'tintas',
      description: 'Juego de tintas de colores para impresoras HP',
      isFeatured: false
    },
    {
      id: 't3',
      name: 'Tinta Canon PG-245 Negro',
      image: 'producto-2',
      imageSource: productImages['producto-2'],
      price: 14.99,
      category: 'tintas',
      description: 'Cartucho de tinta negra para impresoras Canon',
      isFeatured: false
    }
  ],
  cartuchos: [
    {
      id: 'c1',
      name: 'Cartucho HP 301XL Negro',
      image: 'producto-3',
      imageSource: productImages['producto-3'],
      price: 24.99,
      category: 'cartuchos',
      description: 'Cartucho de tóner negro de alta capacidad',
      isFeatured: false
    },
    {
      id: 'c2',
      name: 'Cartucho Brother TN-660',
      image: 'producto-4',
      imageSource: productImages['producto-4'],
      price: 29.99,
      category: 'cartuchos',
      description: 'Cartucho de tóner para impresoras Brother',
      isFeatured: false
    },
    {
      id: 'c3',
      name: 'Cartucho Samsung MLT-D111S',
      image: 'producto-5',
      imageSource: productImages['producto-5'],
      price: 34.99,
      category: 'cartuchos',
      description: 'Cartucho de tóner para impresoras Samsung',
      isFeatured: false
    }
  ]
};

export const useProducts = () => {
  const [products, setProducts] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    let isActive = true;

    const fetchProducts = async () => {
      setLoading(true);
      setError(null);

      try {
        console.log('[useProducts] Consultando productos en la API');
        const response = await InvocarMetodo('productos.php', 'default');
        const apiProducts = response?.data?.productos;
        const apiCombos = response?.data?.combos;

        if (response?.status !== 'success' || !Array.isArray(apiProducts) || !Array.isArray(apiCombos)) {
          throw new Error(response?.message || 'La API no devolvio productos validos');
        }

        const adaptedProducts = apiProducts.map(adaptProduct);
        const adaptedCombos = apiCombos.map(adaptCombo);
        const groupedProducts = groupProductsByCategory(adaptedProducts);
        groupedProducts.combos = adaptedCombos;
        console.log('[useProducts] Productos recibidos:', {
          total: adaptedProducts.length + adaptedCombos.length,
          productos: adaptedProducts.length,
          combos: adaptedCombos.length,
          categorias: Object.keys(groupedProducts),
        });
        if (isActive) {
          setProducts(groupedProducts);
        }
      } catch (err) {
        const message = err?.message || 'Error al cargar los productos';
        console.error('[useProducts] Error al cargar productos desde la API:', err);
        if (isActive) {
          setError(message);
        }
      } finally {
        if (isActive) {
          setLoading(false);
        }
      }
    };

    fetchProducts();

    return () => {
      isActive = false;
    };
  }, [reloadKey]);

  const refetch = () => setReloadKey((currentKey) => currentKey + 1);

  const filterProducts = (query, productsToFilter) => {
    if (!query) return productsToFilter;
    
    const filtered = {};
    Object.entries(productsToFilter).forEach(([category, items]) => {
      const filteredItems = items.filter(product => 
        product.name.toLowerCase().includes(query.toLowerCase()) ||
        (product.description && product.description.toLowerCase().includes(query.toLowerCase()))
      );
      if (filteredItems.length > 0) filtered[category] = filteredItems;
    });
    return filtered;
  };

  return { 
    products, 
    loading, 
    error, 
    refetch,
    filterProducts 
  };
};

export default useProducts;
