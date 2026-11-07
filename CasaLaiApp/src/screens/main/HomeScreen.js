import React, { useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  Image,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  ImageBackground,
  Alert,
  Modal,
  ScrollView,
} from 'react-native';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import ProductCard from '../../components/ProductCard';
import ComboCard from '../../components/ComboCard';
import ComboDetailModal from '../../components/ComboDetailModal';
import ProductDetailModal from '../../components/ProductDetailModal';
import ScreenWithHeader from '../../components/common/ScreenWithHeader';
import { useProducts } from '../../hooks/useProducts';
import { useCart } from '../../hooks/useCart';
import { IMAGES } from '../../config/images';
import { Ionicons } from '@expo/vector-icons';

const normalizeFilterValue = (value) => String(value || '').trim().toLocaleLowerCase();

const getCategory = (product) => (
  product?.category || product?.categoria_nombre || product?.categoria || 'Otros'
);

const getBrand = (product) => (
  product?.brand || product?.marca || product?.nombre_marca || product?.marca_nombre || ''
);

const getProductName = (product) => (
  product?.name || product?.nombre || product?.nombre_combo || 'Producto'
);

const FilterOptions = ({ label, options, selectedValue, onSelect }) => (
  <View style={styles.filterGroup}>
    <Text style={styles.filterLabel}>{label}</Text>
    <ScrollView horizontal showsHorizontalScrollIndicator={false}>
      <View style={styles.filterOptions}>
        {options.map((option) => {
          const isSelected = option.value === selectedValue;
          return (
            <TouchableOpacity
              key={option.value}
              accessibilityRole="button"
              accessibilityState={{ selected: isSelected }}
              style={[styles.filterChip, isSelected && styles.filterChipSelected]}
              onPress={() => onSelect(option.value)}
            >
              <Text style={[styles.filterChipText, isSelected && styles.filterChipTextSelected]}>
                {option.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </ScrollView>
  </View>
);

const HomeScreen = () => {
  const { products, loading, error, refetch } = useProducts();
  const { addItem } = useCart();
  const [searchQuery, setSearchQuery] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [brandFilter, setBrandFilter] = useState('');
  const [minimumPrice, setMinimumPrice] = useState('');
  const [maximumPrice, setMaximumPrice] = useState('');
  const [selectedCombo, setSelectedCombo] = useState(null);
  const [comboModalVisible, setComboModalVisible] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');

  const allProducts = useMemo(
    () => Object.values(products || {}).flat().filter(Boolean),
    [products]
  );

  const categories = useMemo(() => (
    [...new Set(allProducts.map(getCategory).filter(Boolean))]
      .sort((a, b) => a.localeCompare(b, 'es'))
  ), [allProducts]);

  const brands = useMemo(() => (
    [...new Set(allProducts.map(getBrand).filter(Boolean))]
      .sort((a, b) => a.localeCompare(b, 'es'))
  ), [allProducts]);

  const visibleProducts = useMemo(() => {
    const query = normalizeFilterValue(searchQuery);
    const min = minimumPrice.trim() ? Number(minimumPrice.replace(',', '.')) : null;
    const max = maximumPrice.trim() ? Number(maximumPrice.replace(',', '.')) : null;

    return allProducts
      .filter((product) => {
        const name = normalizeFilterValue(getProductName(product));
        const description = normalizeFilterValue(product.description || product.descripcion);
        const category = normalizeFilterValue(getCategory(product));
        const brand = normalizeFilterValue(getBrand(product));
        const price = Number(product.price ?? product.precio ?? 0);

        return (!query || name.includes(query) || description.includes(query) || category.includes(query) || brand.includes(query))
          && (!categoryFilter || category === normalizeFilterValue(categoryFilter))
          && (!brandFilter || brand === normalizeFilterValue(brandFilter))
          && (min === null || !Number.isFinite(min) || price >= min)
          && (max === null || !Number.isFinite(max) || price <= max);
      })
      .sort((a, b) => getProductName(a).localeCompare(getProductName(b), 'es'));
  }, [allProducts, searchQuery, categoryFilter, brandFilter, minimumPrice, maximumPrice]);

  const handleAddToCart = async (product, quantity = 1) => {
    try {
      await addItem(product, quantity);
      const name = getProductName(product);
      setSuccessMessage(
        `${quantity} ${quantity === 1 ? 'unidad agregada' : 'unidades agregadas'} de "${name}" al carrito.`
      );
      return true;
    } catch (error) {
      Alert.alert('No se pudo agregar al carrito', error?.message || 'Inténtalo de nuevo.');
      return false;
    }
  };

  const handleProductPress = (product) => {
    if (product?.isCombo || product?.type === 'combo') {
      setSelectedCombo(product);
      setComboModalVisible(true);
    } else {
      setSelectedProduct(product);
    }
  };

  const relatedProducts = useMemo(() => {
    if (!selectedProduct) return [];

    const selectedId = selectedProduct.id || selectedProduct.id_producto;
    const selectedCategory = normalizeFilterValue(getCategory(selectedProduct));
    return allProducts.filter((product) => (
      !product.isCombo
      && product.type !== 'combo'
      && normalizeFilterValue(getCategory(product)) === selectedCategory
      && (product.id || product.id_producto) !== selectedId
    ));
  }, [allProducts, selectedProduct]);

  const clearFilters = () => {
    setCategoryFilter('');
    setBrandFilter('');
    setMinimumPrice('');
    setMaximumPrice('');
    setSearchQuery('');
  };

  const renderProduct = ({ item }) => (
    <View style={styles.productCell}>
      {item?.isCombo || item?.type === 'combo' ? (
        <ComboCard
          combo={item}
          onPress={() => handleProductPress(item)}
          onAddToCart={(combo, quantity) => handleAddToCart(combo, quantity)}
        />
      ) : (
        <ProductCard
          product={item}
          onPress={handleProductPress}
          onAddToCart={handleAddToCart}
        />
      )}
    </View>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Cargando productos...</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.errorTitle}>No se pudo cargar el catálogo</Text>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryButton} onPress={refetch}>
          <Text style={styles.retryButtonText}>Reintentar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const listHeader = (
    <View style={styles.contentContainer}>
      <Text style={styles.catalogTitle}>Catálogo</Text>

      <View style={styles.filtersContainer}>
        <Text style={styles.filtersTitle}>Filtrar productos</Text>
        <FilterOptions
          label="Categoría"
          options={[
            { label: 'Todas', value: '' },
            ...categories.map((category) => ({ label: category, value: category })),
          ]}
          selectedValue={categoryFilter}
          onSelect={setCategoryFilter}
        />
        <FilterOptions
          label="Marca"
          options={[
            { label: 'Todas', value: '' },
            ...brands.map((brand) => ({ label: brand, value: brand })),
          ]}
          selectedValue={brandFilter}
          onSelect={setBrandFilter}
        />
        <View style={styles.priceFilter}>
          <Text style={styles.filterLabel}>Precio ($)</Text>
          <View style={styles.priceInputs}>
            <TextInput
              style={styles.priceInput}
              value={minimumPrice}
              onChangeText={setMinimumPrice}
              keyboardType="decimal-pad"
              placeholder="Desde"
              accessibilityLabel="Precio mínimo"
            />
            <Text style={styles.priceSeparator}>a</Text>
            <TextInput
              style={styles.priceInput}
              value={maximumPrice}
              onChangeText={setMaximumPrice}
              keyboardType="decimal-pad"
              placeholder="Hasta"
              accessibilityLabel="Precio máximo"
            />
            <TouchableOpacity style={styles.clearFiltersButton} onPress={clearFilters}>
              <Ionicons name="refresh" size={16} color={COLORS.primary} />
              <Text style={styles.clearFiltersText}>Limpiar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      <Text style={styles.resultsText}>
        {visibleProducts.length} {visibleProducts.length === 1 ? 'producto' : 'productos'}
      </Text>
    </View>
  );

  return (
    <>
      <ScreenWithHeader onSearch={setSearchQuery}>
        <ImageBackground source={IMAGES.BACKGROUND} style={styles.backgroundImage} resizeMode="cover">
          <Image
            source={IMAGES.BACKGROUND}
            style={styles.backgroundCover}
            resizeMode="cover"
          />
          <FlatList
            data={visibleProducts}
            renderItem={renderProduct}
            keyExtractor={(item, index) => `${item?.type || (item?.isCombo ? 'combo' : 'product')}-${item?.id || item?.id_producto || index}`}
            numColumns={2}
            columnWrapperStyle={styles.productRow}
            contentContainerStyle={styles.catalogList}
            ListHeaderComponent={listHeader}
            ListEmptyComponent={
              <View style={styles.emptyResults}>
                <Ionicons name="search-outline" size={34} color={COLORS.textSecondary} />
                <Text style={styles.emptyResultsText}>No se encontraron productos con esos filtros.</Text>
              </View>
            }
          />
        </ImageBackground>
      </ScreenWithHeader>

      <ComboDetailModal
        visible={comboModalVisible}
        combo={selectedCombo}
        onClose={() => setComboModalVisible(false)}
        onAddToCart={(combo, quantity) => handleAddToCart(combo, quantity)}
      />

      <ProductDetailModal
        visible={!!selectedProduct}
        product={selectedProduct}
        relatedProducts={relatedProducts}
        onClose={() => setSelectedProduct(null)}
        onAddToCart={handleAddToCart}
        onSelectProduct={setSelectedProduct}
      />

      <Modal
        animationType="fade"
        transparent
        visible={!!successMessage}
        onRequestClose={() => setSuccessMessage('')}
      >
        <View style={styles.successOverlay}>
          <View style={styles.successCard}>
            <Ionicons name="checkmark-circle" size={54} color={COLORS.success || '#22c55e'} />
            <Text style={styles.successTitle}>¡Agregado al carrito!</Text>
            <Text style={styles.successText}>{successMessage}</Text>
            <TouchableOpacity
              style={styles.successButton}
              onPress={() => setSuccessMessage('')}
            >
              <Text style={styles.successButtonText}>Continuar comprando</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </>
  );
};

const styles = StyleSheet.create({
  backgroundImage: {
    ...StyleSheet.absoluteFillObject,
  },
  backgroundCover: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  catalogList: {
    flexGrow: 1,
    paddingBottom: 88,
  },
  contentContainer: {
    backgroundColor: 'rgba(255, 255, 255, 0.92)',
    borderRadius: 12,
    margin: 10,
    marginBottom: 4,
    padding: 14,
  },
  catalogTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: COLORS.darkGray,
    marginBottom: 12,
  },
  filtersContainer: {
    padding: 12,
    backgroundColor: COLORS.white,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  filtersTitle: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    marginBottom: 6,
  },
  filterGroup: {
    marginTop: 8,
  },
  filterLabel: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    fontWeight: '700',
    marginBottom: 6,
  },
  filterOptions: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingRight: 8,
  },
  filterChip: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    backgroundColor: '#f8fafc',
    paddingHorizontal: 12,
    paddingVertical: 7,
    marginRight: 7,
  },
  filterChipSelected: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  filterChipText: {
    fontSize: 12,
    color: COLORS.text,
  },
  filterChipTextSelected: {
    color: COLORS.white,
    fontWeight: '700',
  },
  priceFilter: {
    marginTop: 10,
  },
  priceInputs: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  priceInput: {
    flex: 1,
    height: 38,
    width: 50,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 7,
    paddingHorizontal: 10,
    backgroundColor: '#fff',
    color: COLORS.text,
  },
  priceSeparator: {
    marginHorizontal: 8,
    color: COLORS.textSecondary,
  },
  clearFiltersButton: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: 10,
  },
  clearFiltersText: {
    color: COLORS.primary,
    fontSize: 12,
    fontWeight: '600',
    marginLeft: 3,
  },
  resultsText: {
    marginTop: 12,
    fontSize: 13,
    color: COLORS.textSecondary,
  },
  productRow: {
    paddingHorizontal: 6,
    alignItems: 'stretch',
  },
  productCell: {
    width: '50%',
    paddingHorizontal: 4,
    paddingBottom: 8,
  },
  emptyResults: {
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 40,
  },
  emptyResultsText: {
    marginTop: 10,
    color: COLORS.textSecondary,
    textAlign: 'center',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.background,
  },
  loadingText: {
    marginTop: 10,
  },
  errorTitle: {
    color: COLORS.darkGray,
    fontSize: 18,
    fontWeight: '700',
  },
  errorText: {
    color: COLORS.textSecondary,
    marginTop: 8,
    textAlign: 'center',
    paddingHorizontal: 24,
  },
  retryButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 8,
    marginTop: 16,
    paddingHorizontal: 18,
    paddingVertical: 10,
  },
  retryButtonText: {
    color: COLORS.white,
    fontWeight: '700',
  },
  successOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  successCard: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: COLORS.white,
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    elevation: 10,
  },
  successTitle: {
    marginTop: 12,
    fontSize: 23,
    fontWeight: '700',
    color: COLORS.primary,
    textAlign: 'center',
  },
  successText: {
    marginTop: 10,
    fontSize: 15,
    lineHeight: 22,
    color: COLORS.text,
    textAlign: 'center',
  },
  successButton: {
    width: '100%',
    marginTop: 20,
    backgroundColor: COLORS.primary,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  successButtonText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 15,
  },
});

export default HomeScreen;
