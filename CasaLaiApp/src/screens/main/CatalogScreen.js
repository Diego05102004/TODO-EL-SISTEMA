import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  TextInput,
  ScrollView,
  Alert,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useProducts } from '../../hooks/useProducts';
import { useCart } from '../../hooks/useCart';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { Ionicons } from '@expo/vector-icons';

const CatalogScreen = ({ route }) => {
  const navigation = useNavigation();
  const { category: categoryParam } = route.params || {};
  const { products, loading } = useProducts();
  const { items: cartItems, addItem, updateItemQuantity, isMutating } = useCart();
  const [searchQuery, setSearchQuery] = useState(categoryParam || '');
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [selectedBrand, setSelectedBrand] = useState('');

  console.log('CatalogScreen render:', { categoryParam, loading, productsCount: Object.keys(products || {}).length });

  const allProducts = Object.values(products || {}).flat().filter(Boolean);
  console.log('allProducts:', allProducts.length);

  // Extraer marcas únicas
  const brands = [...new Set(allProducts.map(p => p.brand || p.marca || p.marca_nombre).filter(Boolean))].sort();

  // Filtrar productos
  const searchFilteredProducts = allProducts.filter((product) => {
    const query = searchQuery.toLowerCase().trim();
    const name = (product?.name || product?.nombre || '').toLowerCase();
    const description = (product?.description || product?.descripcion || '').toLowerCase();
    const category = (product?.category || product?.categoria_nombre || product?.categoria || '').toLowerCase();
    const price = Number(product?.price || product?.precio || 0);
    const brand = product?.brand || product?.marca || product?.marca_nombre || '';

    const matchesSearch = !query ||
      name.includes(query) ||
      description.includes(query) ||
      category.includes(query);

    const min = minPrice ? Number(minPrice) : null;
    const max = maxPrice ? Number(maxPrice) : null;
    const matchesPrice = (min === null || price >= min) && (max === null || price <= max);

    const matchesBrand = !selectedBrand || brand === selectedBrand;

    return matchesSearch && matchesPrice && matchesBrand;
  });

  console.log('searchFilteredProducts:', searchFilteredProducts.length);

  const getCartItems = (productId) => {
    return cartItems.filter((cartItem) => (
      Number(cartItem.id_producto ?? cartItem.id) === Number(productId)
    ));
  };

  const getCartQuantity = (productId) => getCartItems(productId)
    .reduce((quantity, cartItem) => quantity + Number(cartItem.quantity || 0), 0);

  const handleAddToCart = async (product) => {
    try {
      await addItem(product);
    } catch (error) {
      Alert.alert('No se pudo agregar al carrito', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const handleIncreaseQuantity = async (product) => {
    try {
      await addItem(product);
    } catch (error) {
      Alert.alert('No se pudo actualizar la cantidad', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const handleDecreaseQuantity = async (product) => {
    const [cartItem] = getCartItems(product.id);
    if (!cartItem) {
      return;
    }

    try {
      await updateItemQuantity(cartItem.cartItemId, Number(cartItem.quantity) - 1);
    } catch (error) {
      Alert.alert('No se pudo actualizar la cantidad', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const renderProductCard = ({ item }) => {
    const cartQuantity = getCartQuantity(item.id);
    const stock = Number(item?.stock || item?.existencia || 0);
    const isOutOfStock = stock === 0;

    return (
      <View style={styles.productCard}>
        <View style={styles.productImageContainer}>
          {item?.imageSource ? (
            <Image
              source={item.imageSource}
              style={styles.productImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.productImagePlaceholder}>
              <Ionicons name="image-outline" size={40} color={COLORS.textSecondary} />
            </View>
          )}
          {isOutOfStock && (
            <View style={styles.outOfStockBadge}>
              <Text style={styles.outOfStockText}>Agotado</Text>
            </View>
          )}
        </View>

        <View style={styles.productInfo}>
          <Text style={styles.productName} numberOfLines={2}>
            {item?.name || item?.nombre || 'Producto'}
          </Text>
          <Text style={styles.productDescription} numberOfLines={2}>
            {item?.description || item?.descripcion || 'Sin descripción'}
          </Text>
          <View style={styles.productMeta}>
            <Ionicons name="cube-outline" size={14} color={COLORS.textSecondary} />
            <Text style={[styles.stockText, isOutOfStock && styles.stockTextOut]}>
              {stock} disponibles
            </Text>
          </View>
          <Text style={styles.productPrice}>
            ${Number(item?.price || item?.precio || 0).toFixed(2)}
          </Text>
        </View>

        <View style={styles.productActions}>
          {isOutOfStock ? (
            <View style={styles.outOfStockButton}>
              <Ionicons name="close-circle" size={20} color={COLORS.textSecondary} />
              <Text style={styles.outOfStockButtonText}>Agotado</Text>
            </View>
          ) : cartQuantity === 0 ? (
            <TouchableOpacity
              style={styles.addButton}
              onPress={() => handleAddToCart(item)}
              disabled={isMutating}
            >
              <Ionicons name="cart-outline" size={20} color={COLORS.white} />
              <Text style={styles.addButtonText}>Agregar</Text>
            </TouchableOpacity>
          ) : (
            <View style={styles.quantityContainer}>
              <TouchableOpacity
                style={[
                  styles.quantityButton,
                  cartQuantity === 1 && styles.deleteButton
                ]}
                onPress={() => handleDecreaseQuantity(item)}
                disabled={isMutating}
              >
                <Ionicons
                  name={cartQuantity === 1 ? 'trash-outline' : 'remove-outline'}
                  size={18}
                  color={cartQuantity === 1 ? COLORS.danger : COLORS.primary}
                />
              </TouchableOpacity>
              <Text style={styles.quantityText}>
                {cartQuantity} en el carrito
              </Text>
              <TouchableOpacity
                style={styles.quantityButton}
                onPress={() => handleIncreaseQuantity(item)}
                disabled={isMutating || cartQuantity >= stock}
              >
                <Ionicons name="add-outline" size={18} color={COLORS.primary} />
              </TouchableOpacity>
            </View>
          )}
        </View>
      </View>
    );
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Cargando productos...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.searchBar}>
          <Ionicons name="search" size={20} color={COLORS.textSecondary} style={styles.searchIcon} />
          <TextInput
            style={styles.searchInput}
            placeholder="Buscar productos..."
            placeholderTextColor={COLORS.textSecondary}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery ? (
            <TouchableOpacity onPress={() => setSearchQuery('')}>
              <Ionicons name="close-circle" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          ) : null}
        </View>
        <Text style={styles.subtitle}>
          {searchFilteredProducts.length} productos disponibles
        </Text>
      </View>

      {/* Filtros horizontales */}
      <ScrollView
        style={styles.filtersContainer}
        horizontal
        showsHorizontalScrollIndicator={false}
      >
        {brands.length > 0 && (
          <View style={styles.filterGroup}>
            <Text style={styles.filterGroupLabel}>Marca:</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              <TouchableOpacity
                style={[styles.filterChip, !selectedBrand && styles.filterChipActive]}
                onPress={() => setSelectedBrand('')}
              >
                <Text style={[styles.filterChipText, !selectedBrand && styles.filterChipTextActive]}>
                  Todas
                </Text>
              </TouchableOpacity>
              {brands.map((brand) => (
                <TouchableOpacity
                  key={brand}
                  style={[styles.filterChip, selectedBrand === brand && styles.filterChipActive]}
                  onPress={() => setSelectedBrand(brand)}
                >
                  <Text style={[styles.filterChipText, selectedBrand === brand && styles.filterChipTextActive]}>
                    {brand}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        <View style={styles.priceFilterGroup}>
          <Text style={styles.filterGroupLabel}>Precio:</Text>
          <View style={styles.priceInputs}>
            <TextInput
              style={styles.priceInput}
              placeholder="Min"
              placeholderTextColor={COLORS.textSecondary}
              value={minPrice}
              onChangeText={setMinPrice}
              keyboardType="decimal-pad"
            />
            <Text style={styles.priceSeparator}>-</Text>
            <TextInput
              style={styles.priceInput}
              placeholder="Max"
              placeholderTextColor={COLORS.textSecondary}
              value={maxPrice}
              onChangeText={setMaxPrice}
              keyboardType="decimal-pad"
            />
          </View>
        </View>
      </ScrollView>

      {searchFilteredProducts.length === 0 ? (
        <View style={styles.emptyState}>
          <Ionicons name="search-outline" size={60} color={COLORS.textSecondary} />
          <Text style={styles.emptyTitle}>No se encontraron productos</Text>
          <Text style={styles.emptyText}>Intenta con otros filtros</Text>
        </View>
      ) : (
        <FlatList
          data={searchFilteredProducts}
          renderItem={renderProductCard}
          keyExtractor={(item) => `product-${item.id}`}
          contentContainerStyle={styles.productList}
          numColumns={2}
          columnWrapperStyle={styles.row}
        />
      )}
    </View>
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
  },
  loadingText: {
    marginTop: 10,
    color: COLORS.textSecondary,
  },
  header: {
    padding: theme.spacing.lg,
    backgroundColor: COLORS.white,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.lightGray,
  },
  title: {
    ...theme.typography.h1,
    color: COLORS.primary,
    marginBottom: theme.spacing.xs,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.lightGray,
    borderRadius: 8,
    paddingHorizontal: theme.spacing.md,
    paddingVertical: theme.spacing.sm,
    marginBottom: theme.spacing.xs,
  },
  searchIcon: {
    marginRight: theme.spacing.sm,
  },
  searchInput: {
    flex: 1,
    ...theme.typography.body,
    color: COLORS.text,
  },
  subtitle: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
  },
  filtersContainer: {
    backgroundColor: COLORS.white,
    paddingVertical: theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.lightGray,
  },
  filterChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: theme.spacing.md,
    paddingVertical: theme.spacing.sm,
    backgroundColor: COLORS.lightGray,
    borderRadius: 20,
    marginRight: theme.spacing.sm,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
  },
  filterChipActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  filterChipText: {
    ...theme.typography.caption,
    color: COLORS.text,
    marginLeft: theme.spacing.xs,
  },
  filterChipTextActive: {
    color: COLORS.white,
  },
  filterGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: theme.spacing.md,
  },
  filterGroupLabel: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    fontWeight: 'bold',
    marginRight: theme.spacing.sm,
  },
  priceFilterGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: theme.spacing.md,
  },
  priceInputs: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  priceInput: {
    width: 60,
    height: 32,
    borderWidth: 1,
    borderColor: COLORS.lightGray,
    borderRadius: 6,
    paddingHorizontal: theme.spacing.sm,
    ...theme.typography.caption,
    color: COLORS.text,
    textAlign: 'center',
  },
  priceSeparator: {
    marginHorizontal: theme.spacing.sm,
    color: COLORS.textSecondary,
  },
  productList: {
    padding: theme.spacing.md,
  },
  row: {
    justifyContent: 'space-between',
  },
  productCard: {
    flex: 1,
    backgroundColor: COLORS.white,
    borderRadius: 12,
    marginBottom: theme.spacing.md,
    marginHorizontal: theme.spacing.xs,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  productImageContainer: {
    height: 150,
    backgroundColor: COLORS.lightGray,
  },
  productImage: {
    width: '100%',
    height: '100%',
  },
  productImagePlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  outOfStockBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  outOfStockText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: 'bold',
  },
  productInfo: {
    padding: theme.spacing.md,
  },
  productName: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    fontWeight: 'bold',
    marginBottom: theme.spacing.xs,
  },
  productDescription: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginBottom: theme.spacing.sm,
  },
  productMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: theme.spacing.sm,
  },
  stockText: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginLeft: 4,
  },
  stockTextOut: {
    color: COLORS.danger,
  },
  productPrice: {
    ...theme.typography.subtitle,
    color: COLORS.primary,
    fontWeight: 'bold',
    fontSize: 18,
  },
  productActions: {
    padding: theme.spacing.md,
    borderTopWidth: 1,
    borderTopColor: COLORS.lightGray,
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingVertical: theme.spacing.sm,
    borderRadius: 8,
  },
  addButtonText: {
    ...theme.typography.bodyBold,
    color: COLORS.white,
    marginLeft: theme.spacing.sm,
  },
  outOfStockButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.lightGray,
    paddingVertical: theme.spacing.sm,
    borderRadius: 8,
  },
  outOfStockButtonText: {
    ...theme.typography.bodyBold,
    color: COLORS.textSecondary,
    marginLeft: theme.spacing.sm,
  },
  quantityContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  quantityButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.lightGray,
    justifyContent: 'center',
    alignItems: 'center',
  },
  deleteButton: {
    backgroundColor: 'rgba(220, 53, 69, 0.1)',
  },
  quantityText: {
    ...theme.typography.caption,
    color: COLORS.text,
    fontWeight: 'bold',
  },
  emptyState: {
    alignItems: 'center',
    padding: theme.spacing.xl,
  },
  emptyTitle: {
    ...theme.typography.h3,
    color: COLORS.text,
    marginTop: theme.spacing.md,
  },
  emptyText: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
    marginTop: theme.spacing.xs,
  },
});

export default CatalogScreen;
