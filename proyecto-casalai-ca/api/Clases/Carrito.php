<?php
namespace Usuario\ProyectoCasalaiCa;

use DomainException;
use PDO;
use Throwable;
use Usuario\ProyectoCasalaiCa\Config\BD;

class Carrito
{
    public function consultarCarrito(array $requestData)
    {
        $this->procesarOperacion('consultar', $requestData);
    }

    public function agregarProductos(array $requestData)
    {
        $this->procesarOperacion('agregar', $requestData);
    }

    public function actualizarProducto(array $requestData)
    {
        $this->procesarOperacion('actualizar', $requestData);
    }

    public function eliminarProducto(array $requestData)
    {
        $this->procesarOperacion('eliminar', $requestData);
    }

    public function vaciarCarrito(array $requestData)
    {
        $this->procesarOperacion('vaciar', $requestData);
    }

    private function procesarOperacion(string $action, array $requestData): void
    {
        $pdo = null;

        try {
            $payload = \Auth::checkAuth();
            $userId = \is_array($payload)
                ? filter_var($payload['user_id'] ?? null, FILTER_VALIDATE_INT)
                : false;

            if ($userId === false || $userId < 1) {
                errorResponse('Debes iniciar sesión para acceder al carrito.', 401);
            }

            $database = new BD();
            $pdo = $database->getConexion();
            $clientId = \apiResolveClientId($pdo, $userId);

            switch ($action) {
                case 'consultar':
                    successResponse($this->cartResponse($pdo, $clientId), 'Carrito consultado correctamente');
                    break;

                case 'agregar':
                    $this->agregarProductosAlCarrito($pdo, $clientId, $requestData);
                    successResponse(
                        $this->cartResponse($pdo, $clientId),
                        'Producto agregado al carrito correctamente'
                    );
                    break;

                case 'actualizar':
                    $this->actualizarCantidad($pdo, $clientId, $requestData);
                    successResponse($this->cartResponse($pdo, $clientId), 'Cantidad actualizada correctamente');
                    break;

                case 'eliminar':
                    $this->eliminarDelCarrito($pdo, $clientId, $requestData);
                    successResponse($this->cartResponse($pdo, $clientId), 'Producto eliminado del carrito');
                    break;

                case 'vaciar':
                    $this->vaciarDetalleCarrito($pdo, $clientId);
                    successResponse($this->cartResponse($pdo, $clientId), 'Carrito vaciado correctamente');
                    break;
            }
        } catch (DomainException $error) {
            $this->rollbackIfNeeded($pdo);
            errorResponse($error->getMessage(), $error->getCode() ?: 400);
        } catch (Throwable $error) {
            $this->rollbackIfNeeded($pdo);
            error_log('[API carrito] ' . $error->getMessage());
            errorResponse('No se pudo procesar el carrito. Inténtalo de nuevo.', 500);
        }
    }

    private function agregarProductosAlCarrito(PDO $pdo, int $clientId, array $requestData): void
    {
        $idCombo = null;
        $requestedItems = $requestData['items'] ?? null;
        if (!empty($requestData['id_combo'])) {
            $idCombo = $this->requirePositiveInteger($requestData['id_combo'], 'id_combo');
            $comboQuantity = $this->requirePositiveInteger($requestData['cantidad'] ?? 1, 'cantidad', 999);
            $comboStatement = $pdo->prepare(
                'SELECT descuento FROM tbl_combo WHERE id_combo = :id_combo AND activo = 1'
            );
            $comboStatement->execute([':id_combo' => $idCombo]);
            if ($comboStatement->fetch(PDO::FETCH_ASSOC) === false) {
                throw new DomainException('El combo ya no está disponible.', 404);
            }

            $comboItemsStatement = $pdo->prepare(
                "SELECT cd.id_producto, cd.cantidad
                 FROM tbl_combo_detalle cd
                 INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                 WHERE cd.id_combo = :id_combo AND p.estado = 'habilitado'"
            );
            $comboItemsStatement->execute([':id_combo' => $idCombo]);
            $comboItems = $comboItemsStatement->fetchAll(PDO::FETCH_ASSOC);
            if ($comboItems === []) {
                throw new DomainException('El combo no contiene productos disponibles.', 409);
            }
            $requestedItems = array_map(
                static function (array $item) use ($comboQuantity, $idCombo): array {
                    return [
                        'id_producto' => $item['id_producto'],
                        'cantidad' => (int)$item['cantidad'] * $comboQuantity,
                        'id_combo' => $idCombo,
                    ];
                },
                $comboItems
            );
        } elseif (!\is_array($requestedItems)) {
            $requestedItems = [[
                'id_producto' => $requestData['id_producto'] ?? null,
                'cantidad' => $requestData['cantidad'] ?? 1,
                'id_combo' => null,
            ]];
        }
        if ($requestedItems === []) {
            errorResponse('Debes indicar al menos un producto para agregar.', 422);
        }

        $itemsByOriginAndProduct = [];
        foreach ($requestedItems as $requestedItem) {
            if (!\is_array($requestedItem)) {
                errorResponse('El formato de los productos del carrito no es válido.', 422);
            }

            $productId = $this->requirePositiveInteger($requestedItem['id_producto'] ?? null, 'id_producto');
            $quantity = $this->requirePositiveInteger($requestedItem['cantidad'] ?? null, 'cantidad', 999);
            $originCombo = $idCombo === null ? null : $idCombo;
            $key = ($originCombo ?? 'individual') . ':' . $productId;
            if (!isset($itemsByOriginAndProduct[$key])) {
                $itemsByOriginAndProduct[$key] = [
                    'id_producto' => $productId,
                    'cantidad' => 0,
                    'id_combo' => $originCombo,
                ];
            }
            $itemsByOriginAndProduct[$key]['cantidad'] += $quantity;
            if ($itemsByOriginAndProduct[$key]['cantidad'] > 999) {
                errorResponse('La cantidad por producto no puede superar 999 unidades.', 422);
            }
        }

        $pdo->beginTransaction();
        $cartStatement = $pdo->prepare(
            'SELECT id_carrito
             FROM tbl_carrito
             WHERE id_cliente = :id_cliente
             ORDER BY fecha_creacion DESC
             LIMIT 1
             FOR UPDATE'
        );
        $cartStatement->execute([':id_cliente' => $clientId]);
        $cartId = $cartStatement->fetchColumn();

        if (!$cartId) {
            $createCart = $pdo->prepare('INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)');
            $createCart->execute([':id_cliente' => $clientId]);
            $cartId = (int) $pdo->lastInsertId();
        }

        foreach ($itemsByOriginAndProduct as $item) {
            $productId = (int)$item['id_producto'];
            $quantity = (int)$item['cantidad'];
            $originCombo = $item['id_combo'];
            $productStatement = $pdo->prepare(
                "SELECT stock, precio
                 FROM tbl_productos
                 WHERE id_producto = :id_producto AND estado = 'habilitado'
                 FOR UPDATE"
            );
            $productStatement->execute([':id_producto' => $productId]);
            $product = $productStatement->fetch(PDO::FETCH_ASSOC);
            if ($product === false) {
                throw new DomainException('Uno de los productos ya no está disponible.', 404);
            }
            $stock = (int)$product['stock'];

            $existingCondition = $originCombo === null ? 'id_combo IS NULL' : 'id_combo = :id_combo';
            $existingStatement = $pdo->prepare(
                "SELECT id_carrito_detalle, cantidad
                 FROM tbl_carritodetalle
                 WHERE id_carrito = :id_carrito AND id_producto = :id_producto
                   AND estatus = 'pendiente' AND $existingCondition
                 LIMIT 1 FOR UPDATE"
            );
            $existingParams = [
                ':id_carrito' => $cartId,
                ':id_producto' => $productId,
            ];
            if ($originCombo !== null) {
                $existingParams[':id_combo'] = $originCombo;
            }
            $existingStatement->execute($existingParams);
            $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);
            $nextQuantity = (int) ($existing['cantidad'] ?? 0) + $quantity;

            $allCartQuantityStatement = $pdo->prepare(
                'SELECT COALESCE(SUM(cantidad), 0)
                 FROM tbl_carritodetalle
                 WHERE id_carrito = :id_carrito AND id_producto = :id_producto'
            );
            $allCartQuantityStatement->execute([
                ':id_carrito' => $cartId,
                ':id_producto' => $productId,
            ]);
            if ((int)$allCartQuantityStatement->fetchColumn() + $quantity > (int)$stock) {
                throw new DomainException('No hay stock suficiente para la cantidad solicitada.', 409);
            }

            $unitPrice = (float)$product['precio'];
            if ($originCombo !== null) {
                $discountStatement = $pdo->prepare(
                    'SELECT c.descuento, cd.cantidad
                     FROM tbl_combo c
                     INNER JOIN tbl_combo_detalle cd ON cd.id_combo = c.id_combo
                     WHERE c.id_combo = :id_combo AND cd.id_producto = :id_producto'
                );
                $discountStatement->execute([
                    ':id_combo' => $originCombo,
                    ':id_producto' => $productId,
                ]);
                $comboDetail = $discountStatement->fetch(PDO::FETCH_ASSOC);
                if (!$comboDetail || (int)$comboDetail['cantidad'] <= 0 ||
                    $quantity % (int)$comboDetail['cantidad'] !== 0 ||
                    (float)$comboDetail['descuento'] < 0 || (float)$comboDetail['descuento'] > 100) {
                    throw new DomainException('El producto no corresponde a una cantidad válida del combo.', 409);
                }
                $unitPrice = round((float)$product['precio'] * (1 - (float)$comboDetail['descuento'] / 100), 2);
            }

            if ($existing) {
                $updateStatement = $pdo->prepare(
                    'UPDATE tbl_carritodetalle
                     SET cantidad = :cantidad, precio_unitario = :precio_unitario
                     WHERE id_carrito_detalle = :id_carrito_detalle'
                );
                $updateStatement->execute([
                    ':cantidad' => $nextQuantity,
                    ':precio_unitario' => $unitPrice,
                    ':id_carrito_detalle' => $existing['id_carrito_detalle'],
                ]);
            } else {
                $insertStatement = $pdo->prepare(
                    "INSERT INTO tbl_carritodetalle
                     (id_carrito, id_producto, cantidad, precio_unitario, id_combo, estatus)
                     VALUES (:id_carrito, :id_producto, :cantidad, :precio_unitario, :id_combo, 'pendiente')"
                );
                $insertStatement->execute([
                    ':id_carrito' => $cartId,
                    ':id_producto' => $productId,
                    ':cantidad' => $quantity,
                    ':precio_unitario' => $unitPrice,
                    ':id_combo' => $originCombo,
                ]);
            }
        }

        $pdo->commit();
    }

    private function actualizarCantidad(PDO $pdo, int $clientId, array $requestData): void
    {
        $detailId = $this->requirePositiveInteger($requestData['id_carrito_detalle'] ?? null, 'id_carrito_detalle');
        $quantity = $this->requirePositiveInteger($requestData['cantidad'] ?? null, 'cantidad', 999);

        $pdo->beginTransaction();
        $detailStatement = $pdo->prepare(
            "SELECT cd.id_carrito, cd.id_producto, cd.id_combo, p.stock, p.precio,
                    c.descuento, cdCombo.cantidad AS cantidad_combo
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito cart ON cart.id_carrito = cd.id_carrito
             INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
             LEFT JOIN tbl_combo c ON c.id_combo = cd.id_combo
             LEFT JOIN tbl_combo_detalle cdCombo
                ON cdCombo.id_combo = cd.id_combo AND cdCombo.id_producto = cd.id_producto
             WHERE cd.id_carrito_detalle = :id_carrito_detalle
               AND cart.id_cliente = :id_cliente
               AND cd.estatus = 'pendiente'
             LIMIT 1
             FOR UPDATE"
        );
        $detailStatement->execute([
            ':id_carrito_detalle' => $detailId,
            ':id_cliente' => $clientId,
        ]);
        $detail = $detailStatement->fetch(PDO::FETCH_ASSOC);
        if (!$detail) {
            throw new DomainException('El producto no existe en tu carrito.', 404);
        }

        if ($detail['id_combo'] !== null) {
            $comboQuantity = (int)$detail['cantidad_combo'];
            if ($detail['descuento'] === null || $comboQuantity <= 0 ||
                $quantity % $comboQuantity !== 0 ||
                (float)$detail['descuento'] < 0 || (float)$detail['descuento'] > 100) {
                throw new DomainException('La cantidad debe corresponder a unidades completas del combo.', 422);
            }

            $comboCount = intdiv($quantity, $comboQuantity);
            $componentsStatement = $pdo->prepare(
                "SELECT cd.id_producto, cd.cantidad, p.stock, p.precio
                 FROM tbl_combo_detalle cd
                 INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
                 WHERE cd.id_combo = :id_combo
                 FOR UPDATE"
            );
            $componentsStatement->execute([':id_combo' => (int)$detail['id_combo']]);
            $components = $componentsStatement->fetchAll(PDO::FETCH_ASSOC);
            $cartComboStatement = $pdo->prepare(
                "SELECT id_carrito_detalle, id_producto
                 FROM tbl_carritodetalle
                 WHERE id_carrito = :id_carrito
                   AND id_combo = :id_combo
                   AND estatus = 'pendiente'
                 FOR UPDATE"
            );
            $cartComboStatement->execute([
                ':id_carrito' => (int)$detail['id_carrito'],
                ':id_combo' => (int)$detail['id_combo'],
            ]);
            $cartComboRows = $cartComboStatement->fetchAll(PDO::FETCH_ASSOC);
            $rowsByProduct = [];
            foreach ($cartComboRows as $cartComboRow) {
                $productId = (int)$cartComboRow['id_producto'];
                if (isset($rowsByProduct[$productId])) {
                    throw new DomainException('El combo tiene productos duplicados en el carrito.', 409);
                }
                $rowsByProduct[$productId] = (int)$cartComboRow['id_carrito_detalle'];
            }
            if (!$components || count($rowsByProduct) !== count($components)) {
                throw new DomainException(
                    'El combo está incompleto en el carrito; elimínalo y agrégalo nuevamente.',
                    409
                );
            }

            $otherQuantityStatement = $pdo->prepare(
                'SELECT COALESCE(SUM(cantidad), 0)
                 FROM tbl_carritodetalle
                 WHERE id_carrito = :id_carrito AND id_producto = :id_producto
                   AND (id_combo IS NULL OR id_combo <> :id_combo)'
            );
            $updateStatement = $pdo->prepare(
                "UPDATE tbl_carritodetalle
                 SET cantidad = :cantidad, precio_unitario = :precio_unitario
                 WHERE id_carrito_detalle = :id_carrito_detalle
                   AND estatus = 'pendiente'"
            );

            foreach ($components as $component) {
                $productId = (int)$component['id_producto'];
                $componentQuantity = (int)$component['cantidad'];
                if ($componentQuantity <= 0 || !isset($rowsByProduct[$productId])) {
                    throw new DomainException(
                        'El combo está incompleto en el carrito; elimínalo y agrégalo nuevamente.',
                        409
                    );
                }
                $nextQuantity = $comboCount * $componentQuantity;
                if ($nextQuantity > 999) {
                    throw new DomainException('La cantidad por producto no puede superar 999 unidades.', 422);
                }

                $otherQuantityStatement->execute([
                    ':id_carrito' => (int)$detail['id_carrito'],
                    ':id_producto' => $productId,
                    ':id_combo' => (int)$detail['id_combo'],
                ]);
                if ((int)$otherQuantityStatement->fetchColumn() + $nextQuantity > (int)$component['stock']) {
                    throw new DomainException('No hay stock suficiente para completar la cantidad del combo.', 409);
                }

                $updateStatement->execute([
                    ':cantidad' => $nextQuantity,
                    ':precio_unitario' => round(
                        (float)$component['precio'] * (1 - (float)$detail['descuento'] / 100),
                        2
                    ),
                    ':id_carrito_detalle' => $rowsByProduct[$productId],
                ]);
            }

            $pdo->commit();
            return;
        }

        $otherQuantityStatement = $pdo->prepare(
            'SELECT COALESCE(SUM(cantidad), 0)
             FROM tbl_carritodetalle
             WHERE id_carrito = :id_carrito AND id_producto = :id_producto
               AND id_carrito_detalle <> :id_carrito_detalle'
        );
        $otherQuantityStatement->execute([
            ':id_carrito' => (int)$detail['id_carrito'],
            ':id_producto' => (int)$detail['id_producto'],
            ':id_carrito_detalle' => $detailId,
        ]);
        if ((int)$otherQuantityStatement->fetchColumn() + $quantity > (int)$detail['stock']) {
            throw new DomainException('No hay stock suficiente para la cantidad solicitada.', 409);
        }

        $updateStatement = $pdo->prepare(
            'UPDATE tbl_carritodetalle
             SET cantidad = :cantidad, precio_unitario = :precio_unitario
             WHERE id_carrito_detalle = :id_carrito_detalle'
        );
        $updateStatement->execute([
            ':cantidad' => $quantity,
            ':precio_unitario' => (float)$detail['precio'],
            ':id_carrito_detalle' => $detailId,
        ]);
        $pdo->commit();
    }

    private function eliminarDelCarrito(PDO $pdo, int $clientId, array $requestData): void
    {
        $detailId = $this->requirePositiveInteger($requestData['id_carrito_detalle'] ?? null, 'id_carrito_detalle');
        $pdo->beginTransaction();
        $detailStatement = $pdo->prepare(
            "SELECT cd.id_carrito, cd.id_combo
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             WHERE cd.id_carrito_detalle = :id_carrito_detalle
               AND c.id_cliente = :id_cliente
               AND cd.estatus = 'pendiente'
             LIMIT 1
             FOR UPDATE"
        );
        $detailStatement->execute([
            ':id_carrito_detalle' => $detailId,
            ':id_cliente' => $clientId,
        ]);
        $detail = $detailStatement->fetch(PDO::FETCH_ASSOC);
        if (!$detail) {
            throw new DomainException('El producto no existe en tu carrito.', 404);
        }

        $comboCondition = $detail['id_combo'] === null
            ? 'cd.id_carrito_detalle = :id_carrito_detalle'
            : 'cd.id_combo = :id_combo';
        $deleteStatement = $pdo->prepare(
            "DELETE cd
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             WHERE cd.id_carrito = :id_carrito
               AND c.id_cliente = :id_cliente
               AND cd.estatus = 'pendiente'
               AND $comboCondition"
        );
        $deleteParams = [
            ':id_carrito' => (int)$detail['id_carrito'],
            ':id_cliente' => $clientId,
        ];
        if ($detail['id_combo'] === null) {
            $deleteParams[':id_carrito_detalle'] = $detailId;
        } else {
            $deleteParams[':id_combo'] = (int)$detail['id_combo'];
        }
        $deleteStatement->execute($deleteParams);

        if ($deleteStatement->rowCount() === 0) {
            throw new DomainException('El producto no existe en tu carrito.', 404);
        }
        $pdo->commit();
    }

    private function vaciarDetalleCarrito(PDO $pdo, int $clientId): void
    {
        $deleteStatement = $pdo->prepare(
            'DELETE cd
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             WHERE c.id_cliente = :id_cliente'
        );
        $deleteStatement->execute([':id_cliente' => $clientId]);
    }

    private function cartResponse(PDO $pdo, int $clientId): array
    {
        $cartStatement = $pdo->prepare(
            'SELECT id_carrito
             FROM tbl_carrito
             WHERE id_cliente = :id_cliente
             ORDER BY fecha_creacion DESC
             LIMIT 1'
        );
        $cartStatement->execute([':id_cliente' => $clientId]);
        $cartId = $cartStatement->fetchColumn();

        if (!$cartId) {
            return ['items' => [], 'total' => 0];
        }

        $itemsStatement = $pdo->prepare(
            "SELECT cd.id_carrito_detalle, cd.id_producto, cd.id_combo, c.nombre_combo,
                    cd.cantidad, cdCombo.cantidad AS cantidad_combo,
                    p.nombre_producto AS name,
                    COALESCE(cd.precio_unitario, p.precio, 0) AS price,
                    COALESCE(cd.precio_unitario, p.precio, 0) * cd.cantidad AS subtotal,
                    p.stock, p.imagen AS image
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
             LEFT JOIN tbl_combo c ON c.id_combo = cd.id_combo
             LEFT JOIN tbl_combo_detalle cdCombo
                ON cdCombo.id_combo = cd.id_combo AND cdCombo.id_producto = cd.id_producto
             WHERE cd.id_carrito = :id_carrito AND cd.estatus = 'pendiente'
             ORDER BY cd.id_carrito_detalle"
        );
        $itemsStatement->execute([':id_carrito' => $cartId]);
        $items = $itemsStatement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item['image'] = \getImageUrl($item['image'] ?? null);
        }
        unset($item);

        $total = array_reduce(
            $items,
            static function (float $sum, array $item): float {
                return $sum + (float)$item['subtotal'];
            },
            0.0
        );

        return ['items' => $items, 'total' => round($total, 2)];
    }

    private function requirePositiveInteger($value, string $field, int $maximum = PHP_INT_MAX): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < 1 || $number > $maximum) {
            errorResponse("El campo {$field} debe ser un entero válido.", 422);
        }

        return $number;
    }

    private function rollbackIfNeeded(?PDO $pdo): void
    {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
