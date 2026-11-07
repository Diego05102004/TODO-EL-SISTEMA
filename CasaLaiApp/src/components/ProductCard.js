import React, { useEffect, useState } from 'react';
import { View, Text, Image, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { useCart } from '../hooks/useCart';
import { calculateStockUsed } from '../utils/cart';
import COLORS from '../constants/colors';
import theme from '../constants/theme';
import { Ionicons } from '@expo/vector-icons';
import { buildProductImageUrl } from '../config/apiConfig';

const getProductImage = (imageName) => {
  const imageMap = {
    'producto-default': require('../assets/images/producto-default.jpg'),
  };
  return imageMap[imageName] || imageMap['producto-default'];
};

const ProductCard = ({ product, onPress, onAddToCart }) => {
  const { cartItems, addItem, isMutating } = useCart();
  const [quantity, setQuantity] = useState(1);
  const isCombo = Boolean(product?.isCombo || product?.type === 'combo');
  const productName = product?.name || product?.nombre || product?.nombre_combo || 'Producto';
  const productPrice = Number(product?.price ?? product?.precio ?? 0);
  const productId = product?.id || product?.id_producto;
  const stockValue = Number(product?.stock ?? product?.existencia ?? 0);
  const stock = Number.isFinite(stockValue) ? stockValue : 0;
  const availableStock = Math.max(0, stock - calculateStockUsed(cartItems, productId));
  const isOutOfStock = !isCombo && availableStock <= 0;

  useEffect(() => {
    setQuantity((currentQuantity) => Math.max(1, Math.min(currentQuantity, availableStock || 1)));
  }, [productId, availableStock]);

  const handlePress = () => {
    console.log('Tarjeta presionada:', productName);
    if (onPress) {
      onPress(product);
    }
  };

  const handleAddToCart = async () => {
    if (onAddToCart) {
      await onAddToCart(product, quantity);
      return;
    }

    const imageUrl = buildProductImageUrl(product?.image);
    const imageToUse = product?.imageSource ||
                      (imageUrl ? { uri: imageUrl } : require('../assets/images/producto-default.jpg'));
    
    try {
      await addItem({
        ...product,
        id: productId,
        name: productName,
        price: productPrice,
        image: imageToUse,
        imageUrl: typeof product?.image === 'string' ? product.image : null,
        isCombo,
        comboProducts: product?.products || [],
        stock: product?.stock ?? product?.existencia,
        existencia: product?.existencia ?? product?.stock,
      }, quantity);
    } catch (error) {
      Alert.alert('No se pudo agregar al carrito', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const imageUrl = buildProductImageUrl(product?.image);
  const imageSource = product?.imageSource ||
    (imageUrl ? { uri: imageUrl } : getProductImage(product?.image));

  return (
    <View style={styles.card}>
      <TouchableOpacity 
        onPress={handlePress}
        style={styles.touchableArea}
        activeOpacity={0.7}
      >
        <View style={styles.imageContainer}>
          <Image 
            source={imageSource} 
            style={styles.image} 
            resizeMode="cover"
            onError={(error) => console.log('Error de imagen:', error.nativeEvent.error)}
          />
        </View>
        <View style={styles.details}>
          <Text style={styles.name} numberOfLines={1}>{productName}</Text>
          {isCombo && product?.products?.length > 0 ? (
            <Text style={styles.comboInfo} numberOfLines={2}>
              {product.products.slice(0, 2).map((item) => item.name || item.nombre).join(', ')}
              {product.products.length > 2 ? '...' : ''}
            </Text>
          ) : null}
          <Text style={styles.price}>${productPrice.toFixed(2)}</Text>
          {!isCombo && (
            <>
              <View style={styles.quantityRow}>
                <Text style={styles.quantityLabel}>Cantidad</Text>
                <View style={styles.quantitySelector}>
                  <TouchableOpacity
                    accessibilityRole="button"
                    accessibilityLabel="Disminuir cantidad"
                    style={[styles.quantityButton, quantity <= 1 && styles.quantityButtonDisabled]}
                    onPress={() => setQuantity((currentQuantity) => Math.max(1, currentQuantity - 1))}
                    disabled={quantity <= 1}
                  >
                    <Ionicons
                      name="remove"
                      size={16}
                      color={quantity <= 1 ? '#94a3b8' : COLORS.primary}
                    />
                  </TouchableOpacity>
                  <Text style={styles.quantityText}>{quantity}</Text>
                  <TouchableOpacity
                    accessibilityRole="button"
                    accessibilityLabel="Aumentar cantidad"
                    style={[styles.quantityButton, quantity >= availableStock && styles.quantityButtonDisabled]}
                    onPress={() => setQuantity((currentQuantity) => Math.min(availableStock, currentQuantity + 1))}
                    disabled={quantity >= availableStock}
                  >
                    <Ionicons
                      name="add"
                      size={16}
                      color={quantity >= availableStock ? '#94a3b8' : COLORS.primary}
                    />
                  </TouchableOpacity>
                </View>
              </View>
              <Text style={styles.stockText}>
                {availableStock > 0 ? `${availableStock} disponibles` : 'Agotado'}
              </Text>
            </>
          )}
        </View>
      </TouchableOpacity>
      
      <TouchableOpacity 
        style={[styles.addButton, isOutOfStock && styles.addButtonDisabled]}
        onPress={handleAddToCart}
        activeOpacity={0.8}
        disabled={isOutOfStock || isMutating}
      >
        <Ionicons name="cart-outline" size={20} color={COLORS.white} />
        <Text style={styles.addButtonText}>{isCombo ? 'Combo' : 'Agregar al carrito'}</Text>
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    flex: 1,
    backgroundColor: COLORS.white,
    borderRadius: 8,
    width: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    overflow: 'hidden',
  },
  touchableArea: {
    flex: 1,
  },
  imageContainer: {
    width: '100%',
    height: 120,
    borderTopLeftRadius: 8,
    borderTopRightRadius: 8,
    overflow: 'hidden',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  details: {
    padding: 12,
  },
  name: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    marginBottom: 4,
  },
  comboInfo: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
    marginBottom: 4,
  },
  price: {
    ...theme.typography.bodyBold,
    color: COLORS.primary,
    fontSize: 16,
    marginBottom: 8,
  },
  quantityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  quantityLabel: {
    ...theme.typography.caption,
    color: COLORS.textSecondary,
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 7,
    backgroundColor: '#f8fafc',
  },
  quantityButton: {
    paddingHorizontal: 7,
    paddingVertical: 5,
  },
  quantityButtonDisabled: {
    opacity: 0.45,
  },
  quantityText: {
    minWidth: 20,
    textAlign: 'center',
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  stockText: {
    fontSize: 11,
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  addButton: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    padding: 8,
    borderBottomLeftRadius: 8,
    borderBottomRightRadius: 8,
  },
  addButtonDisabled: {
    backgroundColor: COLORS.gray || '#94a3b8',
  },
  addButtonText: {
    ...theme.typography.bodyBold,
    color: COLORS.white,
    marginLeft: 4,
  },
});

export default ProductCard;