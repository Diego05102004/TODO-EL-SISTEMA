import React, { useEffect, useState } from 'react';
import {
  Modal,
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  ScrollView,
  Dimensions,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import COLORS from '../constants/colors';
import { useCart } from '../hooks/useCart';
import { calculateStockUsed } from '../utils/cart';
import { buildProductImageUrl } from '../config/apiConfig';

const { height } = Dimensions.get('window');
const defaultImage = require('../assets/images/producto-default.jpg');

const ComboDetailModal = ({ visible, combo, onClose, onAddToCart }) => {
  const { cartItems, isMutating } = useCart();
  const [quantity, setQuantity] = useState(1);

  // Extraer el nombre del combo
  const comboName = combo?.name || combo?.nombre_combo || combo?.nombre || 'Combo';

  // Extraer la lista de productos con todos los fallbacks posibles
  const includedItems = Array.isArray(combo?.products)
    ? combo.products
    : Array.isArray(combo?.productos)
      ? combo.productos
      : Array.isArray(combo?.items)
        ? combo.items
        : Array.isArray(combo?.incluye)
          ? combo.incluye
          : [];
  const comboId = combo?.id_combo ?? combo?.id;
  const availableComboQuantity = includedItems.length > 0
    ? Math.min(...includedItems.map((item) => {
      const productId = item?.id_producto ?? item?.id;
      const stock = Number(item?.stock ?? item?.existencia ?? 0);
      const quantityInCombo = Number(item?.quantity ?? item?.cantidad ?? 1);
      const availableStock = Math.max(0, stock - calculateStockUsed(cartItems, productId));

      return quantityInCombo > 0
        ? Math.floor(availableStock / quantityInCombo)
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
    if (visible) {
      setQuantity(1);
    }
  }, [visible, comboId]);

  useEffect(() => {
    if (Number.isFinite(availableComboQuantity)) {
      setQuantity((currentQuantity) => (
        Math.max(1, Math.min(currentQuantity, availableComboQuantity || 1))
      ));
    }
  }, [availableComboQuantity]);

  if (!combo) return null;

  // Función helper para resolver la imagen de un producto/item
  const getItemImage = (item) => {
    if (item?.imageSource) return item.imageSource;
    const img = item?.imagen || item?.image || item?.imagen_url;
    if (img) {
      const imageUrl = buildProductImageUrl(img);
      return imageUrl ? { uri: imageUrl } : defaultImage;
    }
    return defaultImage;
  };

  // Resolver la imagen principal del combo
  const comboImageUrl = buildProductImageUrl(combo?.image || combo?.imagen);
  const comboImage = combo?.imageSource ||
    (comboImageUrl
      ? { uri: comboImageUrl }
      : getItemImage(includedItems[0]));

  // Precio final
  const totalPrice = Number(combo?.price ?? combo?.precio_final ?? combo?.precio_total ?? combo?.precio ?? 0);

  return (
    <Modal
      animationType="slide"
      transparent={true}
      visible={visible}
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <View style={styles.modalContent}>
          {/* Botón de cierre */}
          <TouchableOpacity style={styles.closeButton} onPress={onClose}>
            <Ionicons name="close" size={24} color={COLORS.darkGray || '#333'} />
          </TouchableOpacity>

          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={styles.scrollContent}
          >
            {/* Imagen del combo */}
            <View style={styles.imageContainer}>
              <Image
                source={comboImage}
                style={styles.comboImage}
                resizeMode="contain"
                defaultSource={defaultImage}
              />
              <View style={styles.comboBadge}>
                <Text style={styles.comboBadgeText}>COMBO</Text>
              </View>
            </View>

            {/* Título y Descripción */}
            <Text style={styles.comboTitle}>{comboName}</Text>
            
            {combo?.description || combo?.descripcion ? (
              <Text style={styles.comboDescription}>
                {combo.description || combo.descripcion}
              </Text>
            ) : null}

            <View style={styles.divider} />

            {/* Productos incluidos */}
            <Text style={styles.sectionTitle}>Productos Incluidos:</Text>
            
            <View style={styles.itemsList}>
              {includedItems.map((item, index) => {
                const itemName = item?.name || item?.nombre_producto || item?.nombre || 'Producto';
                const itemQuantity = Number(item?.quantity ?? item?.cantidad ?? 1);
                const itemPrice = Number(item?.price ?? item?.precio ?? 0);
                const itemImgSource = getItemImage(item);

                return (
                  <View key={item?.id || item?.id_producto || index} style={styles.itemRow}>
                    <Image
                      source={itemImgSource}
                      style={styles.itemImage}
                      resizeMode="cover"
                      defaultSource={defaultImage}
                    />
                    <View style={styles.itemInfo}>
                      <Text style={styles.itemName} numberOfLines={2}>
                        {itemName}
                      </Text>
                      <Text style={styles.itemQuantity}>
                        Cantidad: {itemQuantity}
                      </Text>
                    </View>
                    <Text style={styles.itemPrice}>
                      ${(itemPrice * itemQuantity).toFixed(2)}
                    </Text>
                  </View>
                );
              })}
            </View>
          </ScrollView>

          {/* Footer con precio total y acción */}
          <View style={styles.footer}>
            <View style={styles.priceContainer}>
              <Text style={styles.totalLabel}>
                {quantity > 1 ? `Total (${quantity} combos):` : 'Precio Total:'}
              </Text>
              <Text style={styles.totalPrice}>
                ${(totalPrice * quantity).toFixed(2)}
              </Text>
            </View>

            <View style={styles.quantityContainer}>
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
                    size={18}
                    color={quantity <= 1 ? '#94a3b8' : COLORS.primary}
                  />
                </TouchableOpacity>
                <Text style={styles.quantityValue}>{quantity}</Text>
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
                    size={18}
                    color={quantity >= availableComboQuantity ? '#94a3b8' : COLORS.primary}
                  />
                </TouchableOpacity>
              </View>
              <Text style={styles.availableStock}>
                {isOutOfStock
                  ? 'Agotado'
                  : Number.isFinite(availableComboQuantity)
                    ? `${availableComboQuantity} disponibles`
                    : 'Disponible'}
              </Text>
            </View>

            <TouchableOpacity
              style={[styles.addButton, isOutOfStock && styles.addButtonDisabled]}
              disabled={isOutOfStock || isMutating}
              onPress={async () => {
                if (onAddToCart) {
                  try {
                    const wasAdded = await onAddToCart(combo, quantity);
                    if (wasAdded !== false) {
                      onClose();
                    }
                  } catch (error) {
                    Alert.alert('Stock insuficiente', error.message);
                  }
                }
              }}
            >
              <Ionicons name="cart-outline" size={20} color={COLORS.white || '#FFF'} style={{ marginRight: 8 }} />
              <Text style={styles.addButtonText}>Agregar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: COLORS.white || '#FFF',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: height * 0.85,
    paddingTop: 16,
    paddingHorizontal: 16,
    paddingBottom: 20,
  },
  closeButton: {
    alignSelf: 'flex-end',
    padding: 6,
    borderRadius: 20,
    backgroundColor: '#F0F0F0',
    zIndex: 10,
  },
  scrollContent: {
    paddingBottom: 15,
  },
  imageContainer: {
    width: '100%',
    height: 180,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    marginBottom: 12,
  },
  comboImage: {
    width: '100%',
    height: '100%',
  },
  comboBadge: {
    position: 'absolute',
    top: 0,
    left: 0,
    backgroundColor: COLORS.primary || '#007AFF',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  comboBadgeText: {
    color: '#FFF',
    fontWeight: 'bold',
    fontSize: 12,
  },
  comboTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.darkGray || '#222',
    marginBottom: 6,
  },
  comboDescription: {
    fontSize: 14,
    color: COLORS.textSecondary || '#666',
    marginBottom: 12,
    lineHeight: 20,
  },
  divider: {
    height: 1,
    backgroundColor: '#E0E0E0',
    marginVertical: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.darkGray || '#333',
    marginBottom: 10,
  },
  itemsList: {
    gap: 10,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F9F9F9',
    borderRadius: 8,
    padding: 10,
    borderWidth: 1,
    borderColor: '#EEEEEE',
  },
  itemImage: {
    width: 45,
    height: 45,
    borderRadius: 6,
    marginRight: 12,
    backgroundColor: '#EAEAEA',
  },
  itemInfo: {
    flex: 1,
  },
  itemName: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text || '#333',
  },
  itemQuantity: {
    fontSize: 12,
    color: COLORS.textSecondary || '#777',
    marginTop: 2,
  },
  itemPrice: {
    fontSize: 14,
    fontWeight: 'bold',
    color: COLORS.primary || '#007AFF',
  },
  footer: {
    borderTopWidth: 1,
    borderColor: '#EEEEEE',
    paddingTop: 12,
    marginTop: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
    gap: 12,
  },
  priceContainer: {
    flexDirection: 'column',
  },
  totalLabel: {
    fontSize: 12,
    color: COLORS.textSecondary || '#666',
  },
  totalPrice: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.primary || '#007AFF',
  },
  quantityContainer: {
    alignItems: 'center',
  },
  quantityLabel: {
    fontSize: 12,
    color: COLORS.textSecondary || '#666',
    marginBottom: 4,
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    backgroundColor: '#f8fafc',
  },
  quantityButton: {
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  quantityButtonDisabled: {
    opacity: 0.45,
  },
  quantityValue: {
    minWidth: 24,
    textAlign: 'center',
    color: COLORS.text || '#333',
    fontSize: 14,
    fontWeight: '700',
  },
  availableStock: {
    fontSize: 10,
    color: COLORS.textSecondary || '#777',
    marginTop: 3,
  },
  addButton: {
    backgroundColor: COLORS.primary || '#007AFF',
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
  },
  addButtonDisabled: {
    backgroundColor: COLORS.gray || '#94a3b8',
  },
  addButtonText: {
    color: COLORS.white || '#FFF',
    fontSize: 15,
    fontWeight: 'bold',
  },
});

export default ComboDetailModal;