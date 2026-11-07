export const calculateStockUsed = (items, productId) => (
  items.reduce((total, item) => {
    if (item.isCombo && Array.isArray(item.comboProducts)) {
      const comboProduct = item.comboProducts.find(
        (product) => String(product.id_producto ?? product.id) === String(productId)
      );

      if (comboProduct) {
        return total
          + (Number(item.quantity) || 1)
          * (Number(comboProduct.quantity ?? comboProduct.cantidad) || 1);
      }
    }

    if (String(item.id_producto ?? item.id) === String(productId)) {
      return total + (Number(item.quantity) || 1);
    }

    return total;
  }, 0)
);
