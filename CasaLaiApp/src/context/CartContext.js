import React, {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useSelector } from 'react-redux';
import * as cartService from '../api/cartService';

export const CartContext = createContext(null);

const getUserId = (user, jwtPayload) => {
  const id = user?.id_usuario
    ?? user?.id
    ?? jwtPayload?.user_id
    ?? jwtPayload?.id_usuario
    ?? jwtPayload?.sub;

  return id === undefined || id === null || id === '' ? null : String(id);
};

export const CartProvider = ({ children }) => {
  const { isAuthenticated, user, jwtPayload } = useSelector((state) => state.auth);
  const userId = getUserId(user, jwtPayload);
  const requestVersion = useRef(0);
  const activeMutations = useRef(0);
  const activeMutationUserId = useRef(null);
  const activeUserId = useRef(null);
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [isMutating, setIsMutating] = useState(false);
  const [error, setError] = useState(null);

  const applyCart = useCallback((cart) => {
    setItems(cart.items);
    setTotal(cart.total);
  }, []);

  const refreshCart = useCallback(async () => {
    if (activeMutations.current > 0 && activeMutationUserId.current === userId) {
      return;
    }

    if (!isAuthenticated || !userId) {
      setItems([]);
      setTotal(0);
      setIsLoading(false);
      if (isAuthenticated && !userId) {
        setError('No se pudo identificar la cuenta para cargar el carrito. Inicia sesión nuevamente.');
      } else {
        setError(null);
      }
      return;
    }

    const currentVersion = ++requestVersion.current;
    setIsLoading(true);
    setError(null);

    try {
      const cart = await cartService.getCart();
      if (currentVersion === requestVersion.current) {
        applyCart(cart);
      }
    } catch (requestError) {
      if (currentVersion === requestVersion.current) {
        setError(requestError?.message || 'No se pudo cargar el carrito.');
      }
      throw requestError;
    } finally {
      if (currentVersion === requestVersion.current) {
        setIsLoading(false);
      }
    }
  }, [applyCart, isAuthenticated, userId]);

  useEffect(() => {
    requestVersion.current += 1;
    const nextUserId = isAuthenticated ? userId : null;
    if (activeUserId.current !== nextUserId) {
      setItems([]);
      setTotal(0);
      setError(null);
      activeUserId.current = nextUserId;
    }

    if (!isAuthenticated) {
      setIsLoading(false);
      setIsMutating(false);
      return undefined;
    }

    if (!userId) {
      setIsLoading(false);
      setError('No se pudo identificar la cuenta para cargar el carrito. Inicia sesión nuevamente.');
      return undefined;
    }

    refreshCart().catch(() => {});
    return () => {
      requestVersion.current += 1;
    };
  }, [isAuthenticated, userId, refreshCart]);

  const mutate = useCallback(async (operation) => {
    if (!isAuthenticated || !userId) {
      throw new Error('Inicia sesión para sincronizar tu carrito.');
    }

    const mutationUserId = userId;
    const mutationVersion = ++requestVersion.current;
    activeMutationUserId.current = mutationUserId;
    setIsLoading(false);
    activeMutations.current += 1;
    setIsMutating(true);
    setError(null);

    try {
      const cart = await operation();
      if (isAuthenticated && userId === mutationUserId && mutationVersion === requestVersion.current) {
        applyCart(cart);
      }
      return cart;
    } catch (requestError) {
      if (isAuthenticated && userId === mutationUserId) {
        setError(requestError?.message || 'No se pudo actualizar el carrito.');
      }
      throw requestError;
    } finally {
      activeMutations.current = Math.max(0, activeMutations.current - 1);
      if (activeMutations.current === 0) {
        activeMutationUserId.current = null;
      }
      setIsMutating(activeMutations.current > 0);
    }
  }, [applyCart, isAuthenticated, userId]);

  const addItem = useCallback(
    (product, quantity = 1) => mutate(() => cartService.addToCart(product, quantity)),
    [mutate]
  );
  const updateItemQuantity = useCallback(
    (cartItemId, quantity) => {
      if (quantity <= 0) {
        return mutate(() => cartService.removeCartItem(cartItemId));
      }
      return mutate(() => cartService.updateCartItem(cartItemId, quantity));
    },
    [mutate]
  );
  const removeItem = useCallback(
    (cartItemId) => mutate(() => cartService.removeCartItem(cartItemId)),
    [mutate]
  );
  const clearCart = useCallback(
    () => mutate(() => cartService.clearRemoteCart()),
    [mutate]
  );

  const value = useMemo(() => ({
    items,
    cartItems: items,
    total,
    isLoading,
    isMutating,
    error,
    refreshCart,
    addItem,
    updateItemQuantity,
    removeItem,
    clearCart,
    getItemCount: () => items.reduce((count, item) => count + (Number(item.quantity) || 0), 0),
  }), [
    addItem,
    clearCart,
    error,
    isLoading,
    isMutating,
    items,
    refreshCart,
    removeItem,
    total,
    updateItemQuantity,
  ]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};
