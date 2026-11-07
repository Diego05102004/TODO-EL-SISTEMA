import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Image,
  ScrollView,
  TouchableOpacity,
  Dimensions,
  FlatList,
  Alert,
} from 'react-native';
import { useDispatch, useSelector } from 'react-redux';
import { fetchProductById } from '../../redux/slices/productSlice';
import { useCart } from '../../hooks/useCart';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import ScreenWithHeader from '../../components/common/ScreenWithHeader';
import ProductCard from '../../components/ProductCard';
import { Ionicons } from '@expo/vector-icons';
import { buildProductImageUrl } from '../../config/apiConfig';

const { width } = Dimensions.get('window');

// Datos de ejemplo para productos relacionados (en una aplicación real, estos vendrían de una API)
const relatedProducts = [
  {
    id: '10',
    name: 'Papel Fotográfico A4',
    price: 12.99,
    image: 'papel-fotografico',
    category: 'accesorios',
    description: 'Paquete de 50 hojas de papel fotográfico de alta calidad'
  },
  {
    id: '11',
    name: 'Kit de Mantenimiento',
    price: 19.99,
    image: 'kit-mantenimiento',
    category: 'accesorios',
    description: 'Kit completo para limpieza y mantenimiento de impresoras'
  },
  {
    id: '12',
    name: 'Cable USB para Impresora',
    price: 8.99,
    image: 'cable-usb',
    category: 'cables',
    description: 'Cable USB de alta velocidad para impresoras'
  }
];

const ProductDetailScreen = ({ route, navigation }) => {
  const { productId } = route.params;
  const [quantity, setQuantity] = useState(1);
  const dispatch = useDispatch();
  const { currentProduct: product, loading, error } = useSelector((state) => state.products);
  const { addItem, isMutating } = useCart();

  useEffect(() => {
    console.log('[ProductDetail] Consultando producto:', productId);
    dispatch(fetchProductById(productId));
  }, [dispatch, productId]);

  const handleAddToCart = async () => {
    if (product) {
      try {
        await addItem(product, quantity);
        Alert.alert('Producto agregado', `¡${product.name} se ha añadido al carrito!`);
      } catch (requestError) {
        Alert.alert('No se pudo agregar al carrito', requestError?.message || 'Inténtalo de nuevo.');
      }
    }
  };

  const handleRelatedProductPress = (relatedProduct) => {
    navigation.replace('ProductDetail', { productId: relatedProduct.id });
  };

  const renderRelatedProduct = ({ item }) => (
    <View style={styles.relatedProductCard}>
      <ProductCard 
        product={item} 
        onPress={() => handleRelatedProductPress(item)}
        style={styles.productCard}
      />
    </View>
  );

  if (loading || !product) {
    return (
      <ScreenWithHeader>
        <View style={styles.loadingContainer}>
          <Text>{error || 'Cargando detalles del producto...'}</Text>
        </View>
      </ScreenWithHeader>
    );
  }

  return (
    <ScreenWithHeader>
      <ScrollView style={styles.container}>
        {/* Imagen del producto */}
        <View style={styles.imageContainer}>
          <Image
            source={buildProductImageUrl(product.image) ? { uri: buildProductImageUrl(product.image) } : require('../../assets/images/producto-default.jpg')}
            style={styles.productImage}
            resizeMode="contain"
          />
        </View>

        {/* Información del producto */}
        <View style={styles.detailsContainer}>
          <Text style={styles.productName}>{product.name}</Text>
          
          <View style={styles.priceContainer}>
            {product.originalPrice && (
              <Text style={styles.originalPrice}>${product.originalPrice.toFixed(2)}</Text>
            )}
            <Text style={styles.price}>${product.price.toFixed(2)}</Text>
            {product.originalPrice && (
              <Text style={styles.discount}>
                {Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)}% OFF
              </Text>
            )}
          </View>

          <Text style={styles.sectionTitle}>Descripción</Text>
          <Text style={styles.description}>{product.description}</Text>
          
          {/* Selector de cantidad */}
          <View style={styles.quantityContainer}>
            <Text style={styles.sectionTitle}>Cantidad:</Text>
            <View style={styles.quantitySelector}>
              <TouchableOpacity 
                style={styles.quantityButton}
                onPress={() => setQuantity(Math.max(1, quantity - 1))}
              >
                <Ionicons name="remove" size={20} color={COLORS.primary} />
              </TouchableOpacity>
              <Text style={styles.quantityText}>{quantity}</Text>
              <TouchableOpacity 
                style={styles.quantityButton}
                onPress={() => setQuantity(quantity + 1)}
              >
                <Ionicons name="add" size={20} color={COLORS.primary} />
              </TouchableOpacity>
            </View>
          </View>

          {/* Botón de añadir al carrito */}
          <TouchableOpacity 
            style={styles.addToCartButton}
            onPress={handleAddToCart}
            disabled={isMutating}
          >
            <Ionicons name="cart" size={20} color={COLORS.white} style={styles.cartIcon} />
            <Text style={styles.addToCartText}>Añadir al carrito</Text>
            <Text style={styles.addToCartPrice}>${(product.price * quantity).toFixed(2)}</Text>
          </TouchableOpacity>
        </View>

        {/* Productos relacionados */}
        <View style={styles.relatedProductsContainer}>
          <Text style={styles.sectionTitle}>Productos Relacionados</Text>
          <FlatList
            data={relatedProducts}
            renderItem={renderRelatedProduct}
            keyExtractor={item => item.id}
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.relatedProductsList}
          />
        </View>
      </ScrollView>
    </ScreenWithHeader>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  imageContainer: {
    width: '100%',
    height: 300,
    backgroundColor: COLORS.white,
    justifyContent: 'center',
    alignItems: 'center',
    ...theme.shadow.sm,
  },
  productImage: {
    width: '80%',
    height: '80%',
  },
  detailsContainer: {
    padding: 20,
    backgroundColor: COLORS.white,
    marginBottom: 10,
    ...theme.shadow.sm,
  },
  productName: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 10,
    color: COLORS.darkGray,
  },
  priceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 15,
  },
  price: {
    fontSize: 24,
    fontWeight: 'bold',
    color: COLORS.primary,
    marginRight: 10,
  },
  originalPrice: {
    fontSize: 18,
    color: COLORS.gray,
    textDecorationLine: 'line-through',
    marginRight: 10,
  },
  discount: {
    backgroundColor: COLORS.dangerLight,
    color: COLORS.danger,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
    fontSize: 14,
    fontWeight: 'bold',
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginTop: 20,
    marginBottom: 10,
    color: COLORS.darkGray,
  },
  description: {
    fontSize: 16,
    color: COLORS.gray,
    lineHeight: 24,
  },
  quantityContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: 20,
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.lightGray,
    borderRadius: 8,
  },
  quantityButton: {
    padding: 10,
    backgroundColor: COLORS.lightGray,
  },
  quantityText: {
    width: 40,
    textAlign: 'center',
    fontSize: 16,
    fontWeight: 'bold',
  },
  addToCartButton: {
    flexDirection: 'row',
    backgroundColor: COLORS.primary,
    borderRadius: 8,
    padding: 15,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 10,
    ...theme.shadow.sm,
  },
  addToCartText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: 'bold',
    marginLeft: 10,
    flex: 1,
  },
  cartIcon: {
    marginRight: 5,
  },
  addToCartPrice: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: 'bold',
  },
  relatedProductsContainer: {
    padding: 20,
    backgroundColor: COLORS.white,
    marginTop: 10,
    ...theme.shadow.sm,
  },
  relatedProductsList: {
    paddingVertical: 10,
  },
  relatedProductCard: {
    width: 160,
    marginRight: 15,
  },
  productCard: {
    width: '100%',
  },
});


export default ProductDetailScreen;
