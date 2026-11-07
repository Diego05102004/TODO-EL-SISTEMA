<?php
/**
 * Script de prueba para verificar productos congelados y generación de combos
 */

echo "=== VERIFICACIÓN DE PRODUCTOS CONGELADOS Y COMBOS ===\n\n";

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'casalai_principal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado a la base de datos\n\n";
} catch (PDOException $e) {
    die("✗ Error de conexión: " . $e->getMessage() . "\n");
}

// Consultar productos con stock
echo "--- PRODUCTOS CON STOCK ---\n";
$stmt = $pdo->query("
    SELECT id_producto, nombre_producto, stock, stock_minimo, stock_maximo, estado
    FROM tbl_productos
    WHERE stock IS NOT NULL AND stock_minimo IS NOT NULL
    ORDER BY id_producto
");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productos_congelados = [];
$productos_populares = [];
$productos_normales = [];

foreach ($productos as $prod) {
    $stock = (int)$prod['stock'];
    $min_stock = (int)$prod['stock_minimo'];
    $excedente = $stock - $min_stock;

    echo sprintf(
        "ID %d: %s | Stock: %d | Min: %d | Excedente: %d\n",
        $prod['id_producto'],
        $prod['nombre_producto'],
        $stock,
        $min_stock,
        $excedente
    );

    if ($excedente > 0) {
        $productos_congelados[] = $prod;
    } elseif ($stock < $min_stock) {
        $productos_populares[] = $prod;
    } else {
        $productos_normales[] = $prod;
    }
}

echo sprintf(
    "\n--- RESUMEN ---\n" .
    "Productos congelados (stock > min): %d\n" .
    "Productos populares (stock >= 2*min): %d\n" .
    "Productos normales: %d\n",
    count($productos_congelados),
    count($productos_populares),
    count($productos_normales)
);

// Consultar ventas recientes (últimos 90 días)
echo "\n--- VENTAS RECIENTES (ÚLTIMOS 90 DÍAS) ---\n";
$stmt = $pdo->query("
    SELECT id_producto, COUNT(*) as ventas, SUM(cantidad) as cantidad_total, MAX(fecha) as ultima_venta
    FROM tbl_factura_detalle df
    JOIN tbl_facturas f ON df.factura_id = f.id_factura
    WHERE f.fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY id_producto
    ORDER BY ventas DESC
");
$ventas_por_producto = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Top 10 productos por ventas:\n";
foreach (array_slice($ventas_por_producto, 0, 10) as $venta) {
    echo sprintf(
        "ID %d: %d ventas, %d unidades, última: %s\n",
        $venta['id_producto'],
        $venta['ventas'],
        $venta['cantidad_total'],
        $venta['ultima_venta']
    );
}

// Verificar co-ocurrencia entre productos congelados y populares
echo "\n--- ANÁLISIS DE CO-OCURRENCIA (COMBOS) ---\n";

if (count($productos_congelados) === 0) {
    echo "✗ NO HAY PRODUCTOS CONGELADOS - NO SE GENERARÁN COMBOS\n";
    echo "Solución: Ejecutar seed_ventas_historicas.php con más stock en algunos productos\n";
} elseif (count($productos_populares) === 0) {
    echo "✗ NO HAY PRODUCTOS POPULARES - NO SE GENERARÁN COMBOS\n";
    echo "Solución: Ejecutar seed_ventas_historicas.php para generar ventas\n";
} else {
    echo sprintf(
        "✓ Hay %d productos congelados y %d productos populares\n" .
        "✓ SE GENERARÁN COMBOS ESTRATÉGICOS\n\n",
        count($productos_congelados),
        count($productos_populares)
    );

    // Analizar co-ocurrencia
    $congelados_ids = array_column($productos_congelados, 'id_producto');
    $populares_ids = array_column($productos_populares, 'id_producto');

    echo "Buscando co-ocurrencias en facturas...\n";

    $stmt = $pdo->query("
        SELECT df1.id_producto as prod1, df2.id_producto as prod2, COUNT(DISTINCT df1.factura_id) as co_ocurrencias
        FROM tbl_factura_detalle df1
        JOIN tbl_factura_detalle df2 ON df1.factura_id = df2.factura_id AND df1.id_producto < df2.id_producto
        JOIN tbl_facturas f ON df1.factura_id = f.id_factura
        WHERE f.fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        AND df1.id_producto IN (" . implode(',', array_merge($congelados_ids, $populares_ids)) . ")
        AND df2.id_producto IN (" . implode(',', array_merge($congelados_ids, $populares_ids)) . ")
        GROUP BY df1.id_producto, df2.id_producto
        ORDER BY co_ocurrencias DESC
        LIMIT 20
    ");
    $co_ocurrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($co_ocurrencias) > 0) {
        echo "\nTop co-ocurrencias encontradas:\n";
        foreach ($co_ocurrencias as $co) {
            echo sprintf(
                "Productos %d y %d: %d facturas juntos\n",
                $co['prod1'],
                $co['prod2'],
                $co['co_ocurrencias']
            );
        }
    } else {
        echo "⚠ No se encontraron co-ocurrencias históricas\n";
        echo "Los combos se generarán basados en popularidad, no en co-ocurrencia\n";
    }
}

echo "\n=== CONCLUSIÓN ===\n";
if (count($productos_congelados) >= 2 && count($productos_populares) >= 1) {
    echo "✓ CONDICIONES SATISFACTORIAS PARA GENERACIÓN DE COMBOS\n";
    echo sprintf(
        "  - Productos congelados: %d (mínimo 1)\n" .
        "  - Productos populares: %d (mínimo 1)\n" .
        "  - Ventas históricas disponibles\n",
        count($productos_congelados),
        count($productos_populares)
    );
} else {
    echo "✗ CONDICIONES INSUFICIENTES PARA GENERACIÓN DE COMBOS\n";
    echo sprintf(
        "  - Productos congelados: %d (requerido: ≥1)\n" .
        "  - Productos populares: %d (requerido: ≥1)\n",
        count($productos_congelados),
        count($productos_populares)
    );
}
