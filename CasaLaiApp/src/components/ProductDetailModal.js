import React, { useState, useEffect } from 'react';
import {
  Modal,
  View,
  Text,
  Image,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Dimensions,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import COLORS from '../constants/colors';
import theme from '../constants/theme';
import { InvocarMetodo } from '../utils/comunicadorApi';

const { width } = Dimensions.get('window');

const formatFeatureLabel = (label) => label
  .replace(/_/g, ' ')
  .replace(/\b\w/g, (character) => character.toUpperCase());

const formatFeatureValue = (value) => {
  if (value === null || value === undefined || value === '') return 'N/A';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
};

const ProductDetailModal = ({
  visible,
  onClose,
  product,
  relatedProducts = [],
  tasaBcv = 804.57,
  onAddToCart,
  onSelectProduct,
}) => {
  const [quantity, setQuantity] = useState(1);
  const [detailProduct, setDetailProduct] = useState(null);
  const [error, setError] = useState(null);
  const [isAddingToCart, setIsAddingToCart] = useState(false);

  useEffect(() => {
    if (!visible || !product) return undefined;

    setQuantity(1);
    setDetailProduct(null);
    setError(null);

    const productId = product.id || product.id_producto;
    if (!productId) return undefined;

    let isActive = true;
    InvocarMetodo('productos.php', 'detallado', { id: productId })
      .then((response) => {
        if (!isActive) return;

        if (response?.status !== 'success') {
          if (isActive) {
            setError('Error al detallar el producto/combo');
            setDetailProduct(null);
          }
          return;
        }

        const detailedData = response.data?.producto || response.data?.productos?.[0];
        const characteristics = response.data?.caracteristicas ?? detailedData?.caracteristicas;
        if (detailedData) {
          setDetailProduct({
            ...product,
            ...detailedData,
            caracteristicas: characteristics,
          });
        } else {
          if (isActive) {
            setError('Producto no encontrado');
            setDetailProduct(null);
          }
        }
      })
      .catch((err) => {
        if (isActive) {
          console.error('[ProductDetailModal] Error al cargar detalles:', err);
          const errorMessage = err?.message || 'Error al detallar el producto/combo';
          setError(errorMessage);
          setDetailProduct(null);
        }
      });

    return () => {
      isActive = false;
    };
  }, [visible, product]);

  if (!product) return null;

  if (error) {
    return (
      <Modal
        visible={visible}
        animationType="slide"
        transparent={true}
        onRequestClose={onClose}
      >
        <View style={styles.overlay}>
          <View style={styles.errorModalCard}>
            <Ionicons name="alert-circle-outline" size={48} color={COLORS.danger || '#dc2626'} />
            <Text style={styles.errorTitle}>Error al detallar el producto/combo</Text>
            <Text style={styles.errorMessage}>{error}</Text>
            <TouchableOpacity style={styles.errorButton} onPress={onClose}>
              <Text style={styles.errorButtonText}>Cerrar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    );
  }

  const displayedProduct = detailProduct || product;

  const priceUsd = Number(displayedProduct.precio ?? displayedProduct.price ?? 0);
  const priceBs = (priceUsd * tasaBcv).toLocaleString('es-VE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  const stock = Number(displayedProduct.stock ?? displayedProduct.existencia ?? 0);

  const handleIncrement = () => {
    if (stock > 0 && quantity < stock) {
      setQuantity((prev) => prev + 1);
    }
  };

  const handleDecrement = () => {
    if (quantity > 1) {
      setQuantity((prev) => prev - 1);
    }
  };

  const handleAddToCart = async () => {
    if (onAddToCart) {
      setIsAddingToCart(true);
      try {
        const wasAdded = await onAddToCart(product, quantity);
        if (wasAdded !== false) {
          onClose();
        }
      } catch (error) {
        Alert.alert('No se pudo agregar al carrito', error?.message || 'Inténtalo de nuevo.');
      } finally {
        setIsAddingToCart(false);
      }
    }
  };

  // Resuelve correctamente tanto imágenes de URL como require() locales
  const resolveImageSource = (img) => {
    if (!img) return null;
    if (typeof img === 'string') return { uri: img };
    return img;
  };

  const mainImage = resolveImageSource(
    displayedProduct.imageSource || displayedProduct.imagen || displayedProduct.image || displayedProduct.imageUrl
  );

  const rawFeatures = displayedProduct.features
    || displayedProduct.caracteristicas
    || displayedProduct.caracteristica;
  const featureEntries = rawFeatures && typeof rawFeatures === 'object'
    ? Object.entries(rawFeatures)
    : [];
  const hasTextFeatures = rawFeatures && typeof rawFeatures !== 'object';

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <View style={styles.modalCard}>
          {/* Botón de Cierre */}
          <TouchableOpacity style={styles.closeButton} onPress={onClose} activeOpacity={0.7}>
            <Ionicons name="close" size={24} color={COLORS.textSecondary || '#64748b'} />
          </TouchableOpacity>

          <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
            {/* Sección Principal del Producto */}
            <View style={styles.mainSection}>
              <View style={styles.imageContainer}>
                {mainImage ? (
                  <Image source={mainImage} style={styles.productImage} resizeMode="contain" />
                ) : (
                  <View style={styles.placeholderImage}>
                    <Ionicons name="image-outline" size={48} color={COLORS.gray || '#cbd5e1'} />
                  </View>
                )}
              </View>

              <View style={styles.infoContainer}>
                <Text style={styles.title}>{displayedProduct.nombre || displayedProduct.name || 'Producto'}</Text>

                <Text style={styles.metaText}>
                  Marca: <Text style={styles.metaValue}>{displayedProduct.brand || displayedProduct.marca || displayedProduct.nombre_marca || 'N/A'}</Text>
                  {'  •  '}
                  Modelo: <Text style={styles.metaValue}>{displayedProduct.model || displayedProduct.modelo || displayedProduct.nombre_modelo || 'N/A'}</Text>
                </Text>

                <Text style={styles.metaText}>
                  Serial: <Text style={styles.metaValue}>{displayedProduct.serial || 'N/A'}</Text>
                </Text>

                <View style={styles.categoryRow}>
                  <Text style={styles.metaText}>Categoría: </Text>
                  <View style={styles.categoryBadge}>
                    <Text style={styles.categoryText}>{displayedProduct.category || displayedProduct.categoria || displayedProduct.categoria_nombre || 'Otros'}</Text>
                  </View>
                </View>

                {!!(displayedProduct.description || displayedProduct.descripcion) && (
                  <Text style={styles.description}>{displayedProduct.description || displayedProduct.descripcion}</Text>
                )}

                <View style={styles.priceRow}>
                  <Text style={styles.priceUsd}>${priceUsd.toFixed(2)}</Text>
                  <Text style={styles.priceBs}>{priceBs} BS</Text>
                </View>

                <View style={[styles.stockBadge, stock <= 0 && styles.outOfStockBadge]}>
                  <Text style={[styles.stockText, stock <= 0 && styles.outOfStockText]}>
                    {stock > 0 ? `En stock (${stock})` : 'Agotado'}
                  </Text>
                </View>

                <View style={styles.actionsRow}>
                  <View style={styles.quantitySelector}>
                    <TouchableOpacity
                      style={[styles.qtyBtn, quantity <= 1 && styles.qtyBtnDisabled]}
                      onPress={handleDecrement}
                      disabled={quantity <= 1}
                    >
                      <Ionicons name="remove" size={16} color={quantity <= 1 ? '#94a3b8' : COLORS.primary} />
                    </TouchableOpacity>

                    <Text style={styles.qtyText}>{quantity}</Text>

                    <TouchableOpacity
                      style={[styles.qtyBtn, (quantity >= stock || stock <= 0) && styles.qtyBtnDisabled]}
                      onPress={handleIncrement}
                      disabled={quantity >= stock || stock <= 0}
                    >
                      <Ionicons name="add" size={16} color={quantity >= stock || stock <= 0 ? '#94a3b8' : COLORS.primary} />
                    </TouchableOpacity>
                  </View>

                  <TouchableOpacity
                    style={[styles.addToCartBtn, stock <= 0 && styles.addToCartDisabled]}
                    onPress={handleAddToCart}
                    disabled={stock <= 0 || isAddingToCart}
                  >
                    <Ionicons name="cart-outline" size={20} color={COLORS.white} style={{ marginRight: 6 }} />
                    <Text style={styles.addToCartText}>Agregar al carrito</Text>
                  </TouchableOpacity>
                </View>

                <Text style={styles.warrantyText}>
                  Garantía: {displayedProduct.garantia || 'Garantía de 1 mes de duración'}
                </Text>
              </View>
            </View>

            <View style={styles.sectionDivider} />
            <Text style={styles.sectionTitle}>CARACTERÍSTICAS</Text>
            {featureEntries.length > 0 ? (
              <View style={styles.featuresList}>
                {featureEntries.map(([label, value]) => (
                  <View key={label} style={styles.featureRow}>
                    <Text style={styles.featureLabel}>{formatFeatureLabel(label)}</Text>
                    <Text style={styles.featuresText}>{formatFeatureValue(value)}</Text>
                  </View>
                ))}
              </View>
            ) : hasTextFeatures ? (
              <Text style={styles.featuresText}>{formatFeatureValue(rawFeatures)}</Text>
            ) : (
              <Text style={styles.emptySectionText}>
                Este producto no tiene características registradas para su categoría.
              </Text>
            )}

            {relatedProducts.length > 0 && (
              <>
                <View style={styles.sectionDivider} />
                <Text style={styles.sectionTitle}>PRODUCTOS RELACIONADOS</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.relatedList}>
                  {relatedProducts.map((relItem) => {
                    const relId = relItem.id || relItem.id_producto;
                    const relPriceUsd = Number(relItem.precio ?? relItem.price ?? 0);
                    const relPriceBs = (relPriceUsd * tasaBcv).toLocaleString('es-VE', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    });
                    const relImg = resolveImageSource(relItem.imageSource || relItem.imagen || relItem.image || relItem.imageUrl);

                    return (
                      <View key={relId} style={styles.relatedCard}>
                        {relImg ? (
                          <Image source={relImg} style={styles.relatedImage} resizeMode="contain" />
                        ) : (
                          <Ionicons name="image-outline" size={32} color={COLORS.gray || '#cbd5e1'} />
                        )}
                        <Text style={styles.relatedTitle} numberOfLines={1}>
                          {relItem.nombre || relItem.name}
                        </Text>
                        <Text style={styles.relatedBrand}>{relItem.marca || 'Azon'}</Text>
                        <Text style={styles.relatedPrice}>{relPriceBs} BS</Text>

                        <TouchableOpacity
                          style={styles.relatedViewBtn}
                          onPress={() => onSelectProduct && onSelectProduct(relItem)}
                        >
                          <Ionicons name="eye-outline" size={14} color={COLORS.primary} style={{ marginRight: 4 }} />
                          <Text style={styles.relatedViewText}>Ver</Text>
                        </TouchableOpacity>
                      </View>
                    );
                  })}
                </ScrollView>
              </>
            )}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 12,
  },
  modalCard: {
    width: '100%',
    maxHeight: '90%',
    backgroundColor: COLORS.white || '#ffffff',
    borderRadius: 16,
    overflow: 'hidden',
    position: 'relative',
    elevation: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
  },
  closeButton: {
    position: 'absolute',
    top: 12,
    right: 12,
    zIndex: 10,
    backgroundColor: '#f1f5f9',
    borderRadius: 20,
    padding: 6,
  },
  scrollContent: {
    padding: 16,
    paddingTop: 40,
  },
  mainSection: {
    flexDirection: 'column',
    gap: 16,
  },
  imageContainer: {
    width: '100%',
    height: 200,
    backgroundColor: '#f8fafc',
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  productImage: {
    width: '90%',
    height: '90%',
  },
  placeholderImage: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  infoContainer: {
    flex: 1,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.text || '#0f172a',
    marginBottom: 6,
  },
  metaText: {
    fontSize: 13,
    color: COLORS.textSecondary || '#64748b',
    marginBottom: 4,
  },
  metaValue: {
    color: COLORS.text || '#1e293b',
    fontWeight: '600',
  },
  categoryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  categoryBadge: {
    backgroundColor: '#e2e8f0',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
  },
  categoryText: {
    fontSize: 11,
    color: '#475569',
    fontWeight: '600',
  },
  description: {
    fontSize: 13,
    color: COLORS.textSecondary || '#475569',
    marginVertical: 6,
    lineHeight: 18,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: 8,
    marginVertical: 8,
  },
  priceUsd: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.text || '#0f172a',
  },
  priceBs: {
    fontSize: 14,
    color: COLORS.textSecondary || '#64748b',
    fontWeight: '500',
  },
  stockBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#dcfce7',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    marginBottom: 12,
  },
  stockText: {
    color: '#15803d',
    fontSize: 12,
    fontWeight: '700',
  },
  outOfStockBadge: {
    backgroundColor: '#fee2e2',
  },
  outOfStockText: {
    color: '#b91c1c',
  },
  actionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 12,
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    backgroundColor: '#f8fafc',
  },
  qtyBtn: {
    padding: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyBtnDisabled: {
    opacity: 0.4,
  },
  qtyText: {
    paddingHorizontal: 12,
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text || '#0f172a',
  },
  addToCartBtn: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: COLORS.primary || '#2563eb',
    borderRadius: 8,
    paddingVertical: 10,
    paddingHorizontal: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addToCartDisabled: {
    backgroundColor: '#94a3b8',
  },
  addToCartText: {
    color: COLORS.white || '#ffffff',
    fontWeight: '700',
    fontSize: 14,
  },
  warrantyText: {
    fontSize: 12,
    color: COLORS.textSecondary || '#64748b',
  },
  sectionDivider: {
    height: 1,
    backgroundColor: '#e2e8f0',
    marginVertical: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.primary || '#2563eb',
    textAlign: 'center',
    marginBottom: 10,
    letterSpacing: 0.5,
  },
  featuresText: {
    fontSize: 13,
    color: COLORS.text || '#334155',
    lineHeight: 18,
  },
  featuresList: {
    gap: 8,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    paddingBottom: 6,
  },
  featureLabel: {
    width: '42%',
    fontSize: 13,
    color: COLORS.textSecondary || '#64748b',
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  emptySectionText: {
    fontSize: 12,
    color: COLORS.textSecondary || '#94a3b8',
    textAlign: 'center',
    fontStyle: 'italic',
  },
  relatedList: {
    gap: 12,
    paddingVertical: 4,
  },
  relatedCard: {
    width: 140,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 10,
    padding: 8,
    alignItems: 'center',
  },
  relatedImage: {
    width: '100%',
    height: 80,
    marginBottom: 6,
  },
  relatedTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.text || '#0f172a',
    textAlign: 'center',
  },
  relatedBrand: {
    fontSize: 11,
    color: COLORS.textSecondary || '#64748b',
    marginBottom: 4,
  },
  relatedPrice: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.primary || '#2563eb',
    marginBottom: 6,
  },
  relatedViewBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.primary || '#2563eb',
    borderRadius: 6,
    width: '100%',
    paddingVertical: 4,
  },
  relatedViewText: {
    fontSize: 12,
    color: COLORS.primary || '#2563eb',
    fontWeight: '600',
  },
  errorModalCard: {
    width: '90%',
    maxWidth: 400,
    backgroundColor: COLORS.white || '#ffffff',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    elevation: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
  },
  errorTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text || '#0f172a',
    marginTop: 12,
    marginBottom: 8,
    textAlign: 'center',
  },
  errorMessage: {
    fontSize: 14,
    color: COLORS.textSecondary || '#64748b',
    textAlign: 'center',
    marginBottom: 20,
    lineHeight: 20,
  },
  errorButton: {
    backgroundColor: COLORS.primary || '#2563eb',
    borderRadius: 8,
    paddingVertical: 12,
    paddingHorizontal: 24,
    alignItems: 'center',
  },
  errorButtonText: {
    color: COLORS.white || '#ffffff',
    fontWeight: '700',
    fontSize: 15,
  },
});

export default ProductDetailModal;