import { useContext } from 'react';
import { CartContext } from '../context/CartContext';

export const useCart = () => {
  const cart = useContext(CartContext);
  if (!cart) {
    throw new Error('useCart debe utilizarse dentro de CartProvider.');
  }

  return cart;
};

export default useCart;
