import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, Image, TouchableOpacity, TextInput, Alert, ActivityIndicator, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { useSelector } from 'react-redux';
import { useCart } from '../../hooks/useCart';
import COLORS from '../../constants/colors';
import theme from '../../constants/theme';
import { Ionicons } from '@expo/vector-icons';
import { encryption } from '../../utils/encryption';
import { API_BASE_URL, buildProductImageUrl } from '../../config/apiConfig';
import { getStoredToken } from '../../utils/jwt';

const getOrderProducts = (cartItems) => {
  const productsById = new Map();

  const addProduct = (product, quantity) => {
    const productId = Number(product?.id_producto ?? product?.id);
    const productQuantity = Number(quantity) || 0;

    if (productId <= 0 || productQuantity <= 0) {
      return;
    }

    const existingProduct = productsById.get(productId);
    if (existingProduct) {
      existingProduct.quantity += productQuantity;
      return;
    }

    productsById.set(productId, {
      id: productId,
      name: product?.name || product?.nombre_producto || product?.nombre || 'Producto',
      price: Number(product?.price ?? product?.precio ?? 0),
      quantity: productQuantity,
    });
  };

  cartItems.forEach((item) => {
    const itemQuantity = Number(item?.quantity) || 1;
    const comboProducts = item?.isCombo && Array.isArray(item?.comboProducts)
      ? item.comboProducts
      : [];

    if (comboProducts.length > 0) {
      comboProducts.forEach((product) => {
        const quantityInCombo = Number(product?.quantity ?? product?.cantidad) || 1;
        addProduct(product, quantityInCombo * itemQuantity);
      });
      return;
    }

    if (!item?.isCombo) {
      addProduct(item, itemQuantity);
    }
  });

  return Array.from(productsById.values());
};

const CartQuantityInput = ({ quantity, disabled, onCommit }) => {
  const [draft, setDraft] = useState(String(quantity));

  useEffect(() => {
    setDraft(String(quantity));
  }, [quantity]);

  const commitQuantity = () => {
    const nextQuantity = Number.parseInt(draft, 10);
    if (Number.isInteger(nextQuantity) && nextQuantity > 0) {
      onCommit(nextQuantity);
    } else {
      setDraft(String(quantity));
    }
  };

  return (
    <TextInput
      style={styles.quantityInput}
      value={draft}
      keyboardType="numeric"
      editable={!disabled}
      onChangeText={setDraft}
      onEndEditing={commitQuantity}
    />
  );
};

const CartScreen = () => {
  const navigation = useNavigation();
  const {
    items,
    total,
    isLoading,
    isMutating,
    error,
    refreshCart,
    removeItem,
    updateItemQuantity,
    clearCart,
  } = useCart();
  const { user, jwtPayload } = useSelector(state => state.auth);
  const isLoadingOrderProducts = isLoading || isMutating;

  const [isCreatingOrder, setIsCreatingOrder] = useState(false);
  const [successModalVisible, setSuccessModalVisible] = useState(false);

  useFocusEffect(useCallback(() => {
    refreshCart().catch(() => {});
  }, [refreshCart]));

  const handleRemoveFromCart = async (cartItemId) => {
    try {
      await removeItem(cartItemId);
    } catch (error) {
      Alert.alert('No se pudo eliminar el producto', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const handleQuantityChange = async (cartItemId, quantity) => {
    try {
      await updateItemQuantity(cartItemId, quantity);
    } catch (error) {
      Alert.alert('No se pudo actualizar la cantidad', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const handleClearCart = async () => {
    try {
      await clearCart();
    } catch (error) {
      Alert.alert('No se pudo vaciar el carrito', error?.message || 'Inténtalo de nuevo.');
    }
  };

  const handleCreateOrder = async () => {
    if (isLoadingOrderProducts) {
      Alert.alert('Carrito en actualización', 'Espera a que termine la sincronización antes de generar el pedido.');
      return;
    }

    if (error) {
      Alert.alert('Carrito sin sincronizar', 'Actualiza el carrito antes de generar el pedido.');
      return;
    }

    if (!items || items.length === 0) {
      Alert.alert('Carrito vacío', 'Debes agregar productos antes de generar un pedido.');
      return;
    }

    const sourceUser = {
      ...(user || {}),
      ...(jwtPayload || {}),
      ...(user?.cliente || {}),
      ...(user?.usuario || {}),
    };

    const cedulaUsuario = String(
      sourceUser.cedula ||
      sourceUser.cedula_usuario ||
      sourceUser.documento ||
      sourceUser.dni ||
      sourceUser.ci ||
      sourceUser.id_documento ||
      user?.cedula ||
      user?.documento ||
      jwtPayload?.cedula ||
      jwtPayload?.documento ||
      jwtPayload?.cedula_usuario ||
      ''
    ).trim();

    if (!cedulaUsuario) {
      console.warn('[Cart] cédula no encontrada en user/jwtPayload:', { user, jwtPayload });
      Alert.alert('No se pudo identificar al cliente', 'Inicia sesión nuevamente para continuar.');
      return;
    }

    const formData = new URLSearchParams();
    formData.append('funcion', 'facturaIngresarMovil');
    formData.append('cliente', encryption.encrypt(cedulaUsuario));
    formData.append('cedula', encryption.encrypt(cedulaUsuario));
    formData.append('fecha', new Date().toISOString().slice(0, 10));
    formData.append('descuento', '0');
    formData.append('estatus', 'Borrador');
    formData.append('total', String(Number(total || 0)));

    const orderProducts = getOrderProducts(items);
    console.log('[Cart] Productos consolidados para el pedido:', orderProducts);

    orderProducts.forEach((item, index) => {
      const productId = item.id;
      const quantity = item.quantity;

      formData.append('id_producto[]', String(productId));
      formData.append(`id_producto[${index}]`, String(productId));
      formData.append('cantidad[]', String(quantity));
      formData.append(`cantidad[${index}]`, String(quantity));

      if (item.name) {
        formData.append(`producto[${index}]`, String(item.name));
      }
      if (item.price) {
        formData.append(`precio[${index}]`, String(Number(item.price || 0)));
      }
    });

    try {
      setIsCreatingOrder(true);

      const token = await getStoredToken();
      if (!token) {
        throw new Error('Tu sesión expiró. Inicia sesión nuevamente.');
      }

      const apiUrl = `${API_BASE_URL}/gestionarFactura.php`;
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          Authorization: `Bearer ${token}`,
        },
        body: formData.toString(),
      });

      const responseText = await response.text();
      let responseData = {};

      try {
        responseData = responseText ? JSON.parse(responseText) : {};
      } catch {
        responseData = {};
      }

      const rawMessage = [
        responseData?.message,
        responseData?.mensaje,
        responseData?.resultado,
        responseData?.status,
        responseText,
      ].filter(Boolean).join(' ');

      const normalizedMessage = String(rawMessage).toLowerCase();
      const success =
        response.ok && (
          responseData?.status === 'success' ||
          responseData?.result === 'success' ||
          responseData?.resultado === 'success' ||
          responseData?.success === true ||
          responseData?.estado === 'success' ||
          /operacion|operación|exitosa|exito|éxito|correctamente|registrado|guardado|generado|pedido/i.test(normalizedMessage)
        );

      if (!success) {
        const message =
          responseData?.message ||
          responseData?.mensaje ||
          responseData?.resultado ||
          responseText ||
          'No se pudo registrar el pedido.';

        Alert.alert('Pedido no registrado', message);
        return;
      }

      try {
        await clearCart();
      } catch (clearError) {
        console.error('[Cart] Pedido registrado, pero no se pudo vaciar el carrito:', clearError);
        Alert.alert(
          'Pedido generado',
          'El pedido se registró, pero el carrito no pudo sincronizarse. Actualízalo antes de continuar.'
        );
      }
      console.log('[Cart] Pedido creado correctamente. Respuesta del backend:', {
        responseStatus: response.status,
        responseData,
        normalizedMessage,
      });
      setSuccessModalVisible(true);
    } catch (error) {
      console.error('[Cart] Error al crear pedido:', error);
      Alert.alert(
        'Error al registrar pedido',
        error?.message || 'No se pudo crear el pedido en este momento.'
      );
    } finally {
      setIsCreatingOrder(false);
    }
  };

  const renderItem = ({ item }) => {
    const imageValue = item.image || item.imageUrl;
    const imageUrl = buildProductImageUrl(imageValue);
    const imageSource = imageUrl ? { uri: imageUrl } : (typeof imageValue === 'object' ? imageValue : null);
    
    return (
      <View style={styles.cartItem}>
        <View style={styles.itemImageContainer}>
          {imageSource ? (
            <Image 
              source={imageSource} 
              style={styles.itemImage} 
              resizeMode="cover"
              onError={(error) => console.log('Error loading cart item image:', error.nativeEvent.error)}
            />
          ) : (
            <View style={[styles.itemImage, { backgroundColor: COLORS.lightGray, justifyContent: 'center', alignItems: 'center' }]}>
              <Ionicons name="image-outline" size={24} color={COLORS.primary} />
            </View>
          )}
        </View>
        <View style={styles.itemDetails}>
          <Text style={styles.itemName} numberOfLines={1}>{item.name}</Text>
          <Text style={styles.itemPrice}>${item.price.toFixed(2)} c/u</Text>
          <View style={styles.quantityContainer}>
            <View style={styles.quantityControls}>
              <TouchableOpacity 
                style={[styles.quantityButton, item.quantity <= 1 && styles.disabledButton]}
                onPress={() => handleQuantityChange(item.cartItemId, item.quantity - 1)}
                disabled={item.quantity <= 1 || isMutating}
                activeOpacity={0.7}
              >
                <Ionicons 
                  name="remove" 
                  size={16} 
                  color={item.quantity <= 1 ? COLORS.gray : COLORS.primary} 
                />
              </TouchableOpacity>
              
              <CartQuantityInput
                quantity={item.quantity}
                disabled={isMutating}
                onCommit={(quantity) => handleQuantityChange(item.cartItemId, quantity)}
              />
              
              <TouchableOpacity 
                style={styles.quantityButton}
                onPress={() => handleQuantityChange(item.cartItemId, item.quantity + 1)}
                disabled={isMutating}
                activeOpacity={0.7}
              >
                <Ionicons name="add" size={16} color={COLORS.primary} />
              </TouchableOpacity>
            </View>
            <Text style={styles.itemTotal}>${(item.price * item.quantity).toFixed(2)}</Text>
          </View>
        </View>
        <TouchableOpacity 
          style={styles.removeButton}
          onPress={() => handleRemoveFromCart(item.cartItemId)}
          disabled={isMutating}
        >
          <Ionicons name="trash-outline" size={20} color={COLORS.danger} />
        </TouchableOpacity>
      </View>
    );
  };

  if (isLoading && items.length === 0) {
    return (
      <View style={styles.emptyContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.emptySubtext}>Cargando tu carrito...</Text>
      </View>
    );
  }

  if (error && items.length === 0 && !successModalVisible) {
    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="cloud-offline-outline" size={54} color={COLORS.danger} />
        <Text style={styles.emptyText}>No se pudo cargar el carrito</Text>
        <Text style={styles.emptySubtext}>{error}</Text>
        <TouchableOpacity style={styles.retryButton} onPress={() => refreshCart().catch(() => {})}>
          <Text style={styles.retryButtonText}>Reintentar</Text>
        </TouchableOpacity>
      </View>
    );
  }

  if (items.length === 0 && !successModalVisible) {
    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="cart-outline" size={64} color={COLORS.primary} />
        <Text style={styles.emptyText}>Tu carrito está vacío</Text>
        <Text style={styles.emptySubtext}>Añade productos para comenzar a comprar</Text>
      </View>
    );
  }

  return (
    <>
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.header}>
          <Text style={styles.title}>Mi Carrito</Text>
          <TouchableOpacity onPress={handleClearCart} style={styles.clearButton} disabled={isMutating}>
            <Ionicons name="trash-outline" size={21} color={COLORS.white} style={{marginRight: 5}} />
            <Text style={{ color: COLORS.white, fontSize: 16 }}>Vaciar</Text>
          </TouchableOpacity>
        </View>

        {error ? (
          <View style={styles.errorBanner}>
            <Text style={styles.errorBannerText}>{error}</Text>
            <TouchableOpacity onPress={() => refreshCart().catch(() => {})}>
              <Text style={styles.retryLink}>Reintentar</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        <FlatList
          data={items}
          renderItem={renderItem}
          keyExtractor={item => item.cartItemId.toString()}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
        />

        <View style={styles.footer}>
          <View style={styles.totalContainer}>
            <Text style={styles.totalText}>Total:</Text>
            <Text style={styles.totalAmount}>${total.toFixed(2)}</Text>
          </View>
          <TouchableOpacity
            style={[styles.checkoutButton, isCreatingOrder && styles.checkoutButtonDisabled]}
            onPress={handleCreateOrder}
            disabled={isCreatingOrder || isMutating || isLoading}
          >
            {isCreatingOrder ? (
              <ActivityIndicator color={COLORS.white} size="small" />
            ) : (
              <Text style={styles.checkoutButtonText}>Generar Pedido</Text>
            )}
          </TouchableOpacity>
        </View>
      </SafeAreaView>

      <Modal
        animationType="fade"
        transparent={true}
        visible={successModalVisible}
        onRequestClose={() => setSuccessModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.successModalCard}>
            <Ionicons name="checkmark-circle" size={52} color={COLORS.success || '#22c55e'} />
            <Text style={styles.successTitle}>Pedido generado</Text>
            <Text style={styles.successText}>
              Enhorabuena Estimado Usuario su pedido acaba de ser generado correctamente, puede pagar el pedido en la seccion de Pedidos.
            </Text>
            <Text style={styles.successNote}>
              Nota: si el pedido no es pagado en un plazo de 24 horas despues de su creacion el pedido quedara Anulado
            </Text>

            <View style={styles.successActions}>
              <TouchableOpacity
                style={styles.successPrimaryButton}
                onPress={() => {
                  setSuccessModalVisible(false);
                  navigation.navigate('Pedidos');
                }}
              >
                <Text style={styles.successPrimaryText}>Ver pedidos</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.successSecondaryButton}
                onPress={() => setSuccessModalVisible(false)}
              >
                <Text style={styles.successSecondaryText}>Quedarme aquí</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: theme.spacing.lg,
    backgroundColor: COLORS.background,
  },
  emptyText: {
    ...theme.typography.h2,
    color: COLORS.primary,
    marginTop: theme.spacing.lg,
    marginBottom: theme.spacing.sm,
  },
  emptySubtext: {
    ...theme.typography.body,
    color: COLORS.textSecondary,
    textAlign: 'center',
  },
  header: {
    backgroundColor: COLORS.primary,
    padding: theme.spacing.lg,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    position: 'relative',
  },
  title: {
    ...theme.typography.h2,
    color: COLORS.white,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  clearButton: {
    position: 'absolute',
    right: 20,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 16,
  },
  listContent: {
    padding: theme.spacing.md,
  },
  cartItem: {
    flexDirection: 'row',
    backgroundColor: COLORS.white,
    borderRadius: 8,
    padding: theme.spacing.md,
    marginBottom: theme.spacing.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  itemImageContainer: {
    width: 70,
    height: 70,
    borderRadius: 4,
    overflow: 'hidden',
    marginRight: theme.spacing.md,
  },
  itemImage: {
    width: '100%',
    height: '100%',
  },
  itemDetails: {
    flex: 1,
    justifyContent: 'center',
  },
  itemName: {
    ...theme.typography.subtitle,
    color: COLORS.text,
    marginBottom: 4,
  },
  itemPrice: {
    ...theme.typography.body,
    color: COLORS.primary,
    marginBottom: 4,
  },
  quantityContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  quantityControls: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 4,
  },
  quantityButton: {
    padding: 8,
    minWidth: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 4,
  },
  disabledButton: {
    opacity: 0.5,
  },
  quantityInput: {
    width: 40,
    textAlign: 'center',
    padding: 4,
    borderLeftWidth: 1,
    borderRightWidth: 1,
    borderColor: COLORS.border,
    ...theme.typography.body,
  },
  itemTotal: {
    ...theme.typography.bodyBold,
    color: COLORS.primary,
  },
  removeButton: {
    justifyContent: 'center',
    paddingLeft: theme.spacing.md,
  },
  footer: {
    padding: theme.spacing.lg,
    borderTopWidth: 1,
    borderTopColor: COLORS.lightGray,
    backgroundColor: COLORS.white,
    elevation: 10,
    zIndex: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  totalContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.md,
  },
  totalText: {
    ...theme.typography.h3,
    color: COLORS.text,
  },
  totalAmount: {
    ...theme.typography.h2,
    color: COLORS.primary,
  },
  checkoutButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 8,
    padding: theme.spacing.md,
    alignItems: 'center',
  },
  checkoutButtonDisabled: {
    opacity: 0.7,
  },
  checkoutButtonText: {
    ...theme.typography.button,
    color: COLORS.white,
  },
  retryButton: {
    marginTop: theme.spacing.md,
    backgroundColor: COLORS.primary,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: theme.spacing.sm,
    borderRadius: 8,
  },
  retryButtonText: {
    color: COLORS.white,
    fontWeight: '600',
  },
  errorBanner: {
    marginHorizontal: theme.spacing.md,
    padding: theme.spacing.sm,
    backgroundColor: '#fee2e2',
    borderRadius: 8,
  },
  errorBannerText: {
    color: COLORS.danger,
  },
  retryLink: {
    marginTop: 4,
    color: COLORS.primary,
    fontWeight: '600',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  successModalCard: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.2,
    shadowRadius: 12,
    elevation: 10,
  },
  successTitle: {
    marginTop: 12,
    fontSize: 24,
    fontWeight: '700',
    color: COLORS.primary,
    textAlign: 'center',
  },
  successText: {
    marginTop: 12,
    fontSize: 15,
    lineHeight: 22,
    color: COLORS.text,
    textAlign: 'center',
  },
  successNote: {
    marginTop: 12,
    fontSize: 13,
    lineHeight: 20,
    color: COLORS.textSecondary,
    textAlign: 'center',
  },
  successActions: {
    width: '100%',
    marginTop: 20,
    gap: 10,
  },
  successPrimaryButton: {
    backgroundColor: COLORS.primary,
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  successPrimaryText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 15,
  },
  successSecondaryButton: {
    backgroundColor: '#F3F4F6',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  successSecondaryText: {
    color: COLORS.text,
    fontWeight: '600',
    fontSize: 15,
  },
});

export default CartScreen;