import React, { useEffect, useState } from 'react';
import { View, Text, Image, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { useCart } from '../hooks/useCart';
import { calculateStockUsed } from '../utils/cart';
import COLORS from '../constants/colors';
import theme from '../constants/theme';
import { Ionicons } from '@expo/vector-icons';
import { buildProductImageUrl } from '../config/apiConfig';

const defaultImage = require('../assets/images/producto-default.jpg');

const ComboCard = ({ combo, onPress, onAddToCart }) => {
  const { cartItems, addItem, isMutating } = useCart();
  const [quantity, setQuantity] = useState(1);
  const name = combo?.name || combo?.nombre_combo || combo?.nombre || 'Combo';
  const price = Number(combo?.price ?? combo?.precio_final ?? combo?.precio_total ?? combo?.precio ?? 0);
  const items = Array.isArray(combo?.products)
    ? combo.products
    : Array.isArray(combo?.productos)
      ? combo.productos
      : [];
  const comboId = combo?.id_combo ?? combo?.id;
  const availableComboQuantity = items.length > 0
    ? Math.min(...items.map((item) => {
      const productId = item?.id_producto ?? item?.id;
      const stock = Number(item?.stock ?? item?.existencia ?? 0);
      const quantityInCombo = Number(item?.quantity ?? item?.cantidad ?? 1);
      const availableProductStock = Math.max(
        0,
        stock - calculateStockUsed(cartItems, productId)
      );

      return quantityInCombo > 0
        ? Math.floor(availableProductStock / quantityInCombo)
        : 0;
    }))
    : Number.isFinite(Number(combo?.stock ?? combo?.existencia))
      && Number(combo?.stock ?? combo?.existencia) > 0
      ? Math.max(
        0,
        Number(combo?.stock ?? combo?.existencia)
          - cartItems
            .filter((item) => item.isCombo && (item.id === comboId || item.id_combo === comboId))
            .reduce((total, item) => total + (Number(item.quantity) || 0), 0)
      )
      : Infinity;
  const isOutOfStock = availableComboQuantity <= 0;

  useEffect(() => {
    setQuantity((currentQuantity) => (
      Number.isFinite(availableComboQuantity)
        ? Math.max(1, Math.min(currentQuantity, availableComboQuantity || 1))
        : currentQuantity
    ));
  }, [comboId, availableComboQuantity]);

  const getItemImage = (item) => {
    if (item?.imageSource) return item.imageSource;
    const imageUrl = buildProductImageUrl(item?.imagen || item?.image);
    return imageUrl ? { uri: imageUrl } : defaultImage;
  };
  const comboImageUrl = buildProductImageUrl(combo?.imagen || combo?.image);
  const imageSource = combo?.imageSource || (comboImageUrl ? { uri: comboImageUrl } : getItemImage(items[0]));

  const handleAddToCart = async () => {
    if (onAddToCart) {
      await onAddToCart(combo, quantity);
    } else {
      try {
        await addItem({
          ...combo,
          id: combo?.id,
          name,
          price,
          image: imageSource,
          imageUrl: typeof combo?.image === 'string' ? combo.image : null,
          isCombo: true,
          comboProducts: items,
          stock: combo?.stock ?? combo?.existencia,
          existencia: combo?.existencia ?? combo?.stock,
        }, quantity);
      } catch (error) {
        Alert.alert('No se pudo agregar al carrito', error?.message || 'Inténtalo de nuevo.');
      }
    }
  };

  const handlePressCard = () => {
    if (onPress) {
      onPress(combo);
    } else {
      console.log('[ComboCard] Combo seleccionado:', combo?.id);
    }
  };

  return (
    <View style={styles.card}>
      <TouchableOpacity style={styles.touchableArea} onPress={handlePressCard}>
        <View style={styles.imageContainer}>
          <Image source={imageSource} style={styles.image} resizeMode="cover" />
          <View style={styles.badge}>
            <Text style={styles.badgeText}>COMBO</Text>
          </View>
        </View>
        <View style={styles.details}>
          <Text style={styles.name} numberOfLines={2}>{name}</Text>
          <Text style={styles.itemsLabel}>Incluye:</Text>
          {items.length > 0 ? (
            items.slice(0, 3).map((item, index) => (
              <View key={`${item?.id || item?.id_producto || index}`} style={styles.itemRow}>
                <Image source={getItemImage(item)} style={styles.itemImage} resizeMode="cover" />
                <View style={styles.itemDetails}>
                  <Text style={styles.item} numberOfLines={1}>
                    {item?.name || item?.nombre_producto || item?.nombre || 'Producto'}
                  </Text>
                  <Text style={styles.itemMeta}>
                    {Number(item?.quantity ?? item?.cantidad ?? 1)} x ${(Number(item?.price ?? item?.precio ?? 0)).toFixed(2)}
                  </Text>
                </View>
              </View>
            ))
          ) : (
            <Text style={styles.item}>Productos incluidos</Text>
          )}
          {items.length > 3 ? <Text style={styles.item}>+{items.length - 3} productos</Text> : null}
          <Text style={styles.price}>${price.toFixed(2)}</Text>
          <View style={styles.quantityRow}>
            <Text style={styles.quantityLabel}>Cantidad</Text>
            <View style={styles.quantitySelector}>
              <TouchableOpacity
                accessibilityRole="button"
                accessibilityLabel="Disminuir cantidad del combo"
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
                accessibilityLabel="Aumentar cantidad del combo"
                style={[
                  styles.quantityButton,
                  quantity >= availableComboQuantity && styles.quantityButtonDisabled,
                ]}
                onPress={() => setQuantity((currentQuantity) => (
                  Number.isFinite(availableComboQuantity)
                    ? Math.min(availableComboQuantity, currentQuantity + 1)
                    : currentQuantity + 1
                ))}
                disabled={quantity >= availableComboQuantity}
              >
                <Ionicons
                  name="add"
                  size={16}
                  color={quantity >= availableComboQuantity ? '#94a3b8' : COLORS.primary}
                />
              </TouchableOpacity>
            </View>
          </View>
          <Text style={styles.stockText}>
            {isOutOfStock
              ? 'Agotado'
              : Number.isFinite(availableComboQuantity)
                ? `${availableComboQuantity} disponibles`
                : 'Disponible'}
          </Text>
        </View>
      </TouchableOpacity>

      <TouchableOpacity
        style={[styles.addButton, isOutOfStock && styles.addButtonDisabled]}
        onPress={handleAddToCart}
        disabled={isOutOfStock || isMutating}
      >
        <Ionicons name="cart-outline" size={20} color={COLORS.white} />
        <Text style={styles.addButtonText}>Agregar</Text>
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
    minWidth: 0,
    maxWidth: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    overflow: 'hidden',
  },
  touchableArea: { flex: 1 },
  imageContainer: {
    width: '100%',
    height: 120,
    backgroundColor: COLORS.lightGray,
    overflow: 'hidden',
  },
  image: { width: '100%', height: '100%' },
  badge: {
    position: 'absolute',
    top: 8,
    left: 8,
    backgroundColor: COLORS.primary,
    paddingHorizontal: 7,
    paddingVertical: 4,
    borderRadius: 4,
  },
  badgeText: { ...theme.typography.caption, color: COLORS.white, fontWeight: 'bold' },
  details: { padding: 12 },
  name: { ...theme.typography.subtitle, color: COLORS.text, marginBottom: 6 },
  itemsLabel: { ...theme.typography.caption, color: COLORS.primary, fontWeight: 'bold', marginBottom: 2 },
  item: { ...theme.typography.caption, color: COLORS.textSecondary, marginBottom: 2 },
  itemRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  itemImage: { width: 34, height: 34, borderRadius: 4, backgroundColor: COLORS.lightGray, marginRight: 7 },
  itemDetails: { flex: 1 },
  itemMeta: { ...theme.typography.caption, color: COLORS.textSecondary, marginTop: 1 },
  price: { ...theme.typography.bodyBold, color: COLORS.primary, fontSize: 16, marginTop: 6 },
  quantityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 8,
  },
  quantityLabel: { ...theme.typography.caption, color: COLORS.textSecondary },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 7,
    backgroundColor: '#f8fafc',
  },
  quantityButton: { paddingHorizontal: 7, paddingVertical: 5 },
  quantityButtonDisabled: { opacity: 0.45 },
  quantityText: {
    minWidth: 20,
    textAlign: 'center',
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  stockText: { fontSize: 11, color: COLORS.textSecondary, marginTop: 3, marginBottom: 8 },
  addButton: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    padding: 8,
  },
  addButtonDisabled: { backgroundColor: COLORS.gray || '#94a3b8' },
  addButtonText: { ...theme.typography.bodyBold, color: COLORS.white, marginLeft: 4 },
});

export default ComboCard;