import React, { useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  ScrollView,
  Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { useProducts } from '../../hooks/useProducts';
import { Ionicons } from '@expo/vector-icons';
import { buildProductImageUrl } from '../../config/apiConfig';

const { width } = Dimensions.get('window');

const AmazonStyleHomeScreen = () => {
  const navigation = useNavigation();
  const { products, loading } = useProducts();
  const [selectedCategory, setSelectedCategory] = useState(null);

  const allProducts = useMemo(
    () => Object.values(products || {}).flat().filter(Boolean),
    [products]
  );

  console.log('[AmazonStyleHomeScreen] Productos cargados:', allProducts.length);

  // Datos de prueba si no hay productos de la API
  const sampleCategories = useMemo(() => [
    { name: 'Impresoras', image: null, count: 5 },
    { name: 'Tintas', image: null, count: 8 },
    { name: 'Cartuchos', image: null, count: 12 },
    { name: 'Papel', image: null, count: 3 },
    { name: 'Accesorios', image: null, count: 7 },
    { name: 'Combos', image: null, count: 4 },
  ], []);

  // Extraer categorías únicas
  const categories = useMemo(() => {
    const categoryMap = new Map();
    allProducts.forEach((product) => {
      const category = product?.category || product?.categoria_nombre || product?.categoria || 'Otros';
      if (!categoryMap.has(category)) {
        categoryMap.set(category, {
          name: category,
          image: product?.image || product?.imagen || null,
          count: 1,
        });
      } else {
        categoryMap.get(category).count += 1;
      }
    });
    const result = Array.from(categoryMap.values());
    console.log('[AmazonStyleHomeScreen] Categorías encontradas:', result.map(c => c.name));

    // Si no hay categorías de la API, usar datos de prueba
    if (result.length === 0) {
      console.log('[AmazonStyleHomeScreen] Usando categorías de prueba');
      return sampleCategories;
    }

    return result;
  }, [allProducts, sampleCategories]);

  // Crear paneles destacados (similar a Amazon)
  const featuredPanels = useMemo(() => {
    const panels = [];
    const usedCategories = new Set();

    // Paneles de categorías destacadas
    categories.slice(0, 6).forEach((category) => {
      if (!usedCategories.has(category.name)) {
        usedCategories.add(category.name);
        panels.push({
          type: 'category',
          title: category.name,
          subtitle: `${category.count} productos disponibles`,
          image: category.image,
          categoryName: category.name,
        });
      }
    });

    console.log('[AmazonStyleHomeScreen] Paneles destacados:', panels.length);
    return panels;
  }, [categories]);

  const renderCategoryCard = ({ item, index }) => (
    <TouchableOpacity
      style={styles.categoryCard}
      onPress={() => navigation.navigate('Catalog', { category: item.categoryName })}
    >
      <View style={styles.categoryImageContainer}>
        {item.image ? (
          <Image source={buildProductImageUrl(item.image) ? { uri: buildProductImageUrl(item.image) } : null} style={styles.categoryImage} resizeMode="cover" />
        ) : (
          <View style={styles.categoryImagePlaceholder}>
            <Ionicons name="pricetag" size={40} color={COLORS.primary} />
          </View>
        )}
      </View>
      <View style={styles.categoryInfo}>
        <Text style={styles.categoryTitle} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.categorySubtitle}>{item.subtitle}</Text>
      </View>
    </TouchableOpacity>
  );

  const renderFeaturedPanel = ({ item }) => (
    <TouchableOpacity
      style={styles.featuredPanel}
      onPress={() => navigation.navigate('Catalog', { category: item.categoryName })}
    >
      <View style={styles.featuredImageContainer}>
        {item.image ? (
          <Image source={buildProductImageUrl(item.image) ? { uri: buildProductImageUrl(item.image) } : null} style={styles.featuredImage} resizeMode="cover" />
        ) : (
          <View style={styles.featuredImagePlaceholder}>
            <Ionicons name="grid" size={50} color={COLORS.primary} />
          </View>
        )}
      </View>
      <View style={styles.featuredInfo}>
        <Text style={styles.featuredTitle}>{item.title}</Text>
        <Text style={styles.featuredSubtitle}>{item.subtitle}</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Cargando categorías...</Text>
      </View>
    );
  }

  if (allProducts.length === 0) {
    return (
      <View style={styles.loadingContainer}>
        <Ionicons name="alert-circle-outline" size={60} color={COLORS.textSecondary} />
        <Text style={styles.loadingText}>No hay productos disponibles</Text>
        <Text style={styles.loadingSubtext}>Verifica la conexión con la API</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Casa Lai</Text>
        <Text style={styles.headerSubtitle}>Explora nuestras categorías</Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Categorías destacadas</Text>
        <FlatList
          data={featuredPanels}
          renderItem={renderFeaturedPanel}
          keyExtractor={(item, index) => `featured-${index}`}
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.featuredList}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Todas las categorías</Text>
        <FlatList
          data={categories}
          renderItem={renderCategoryCard}
          keyExtractor={(item, index) => `category-${index}`}
          numColumns={2}
          columnWrapperStyle={styles.categoryRow}
          contentContainerStyle={styles.categoryList}
        />
      </View>
    </ScrollView>
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
    backgroundColor: COLORS.background,
  },
  loadingText: {
    marginTop: 10,
    color: COLORS.textSecondary,
  },
  header: {
    padding: theme.spacing.lg,
    backgroundColor: COLORS.primary,
  },
  headerTitle: {
    ...theme.typography.h1,
    color: COLORS.white,
    fontWeight: 'bold',
  },
  headerSubtitle: {
    ...theme.typography.body,
    color: COLORS.white,
    opacity: 0.9,
  },
  section: {
    marginTop: theme.spacing.lg,
  },
  sectionTitle: {
    ...theme.typography.h3,
    color: COLORS.text,
    fontWeight: 'bold',
    paddingHorizontal: theme.spacing.lg,
    marginBottom: theme.spacing.md,
  },
  featuredList: {
    paddingHorizontal: theme.spacing.md,
  },
  featuredPanel: {
    width: width * 0.85,
    backgroundColor: COLORS.white,
    borderRadius: 12,
    marginRight: theme.spacing.md,
    overflow: 'hidden',
    elevation: 3,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  featuredImageContainer: {
    height: 150,
    backgroundColor: COLORS.lightGray,
  },
  featuredImage: {
    width: '100%',
    height: '100%',
  },
  featuredImagePlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  featuredInfo: {
    padding: theme.spacing.md,
  },
  featuredTitle: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    fontWeight: 'bold',
    marginBottom: theme.spacing.xs,
  },
  featuredSubtitle: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
  },
  categoryList: {
    paddingHorizontal: theme.spacing.md,
    paddingBottom: theme.spacing.xl,
  },
  categoryRow: {
    justifyContent: 'space-between',
  },
  categoryCard: {
    flex: 1,
    backgroundColor: COLORS.white,
    borderRadius: 12,
    marginHorizontal: theme.spacing.xs,
    marginBottom: theme.spacing.md,
    overflow: 'hidden',
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  categoryImageContainer: {
    height: 120,
    backgroundColor: COLORS.lightGray,
  },
  categoryImage: {
    width: '100%',
    height: '100%',
  },
  categoryImagePlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  categoryInfo: {
    padding: theme.spacing.md,
  },
  categoryTitle: {
    ...theme.typography.bodyBold,
    color: COLORS.text,
    marginBottom: theme.spacing.xs,
  },
  categorySubtitle: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
  },
});

export default AmazonStyleHomeScreen;
