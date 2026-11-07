<?php
ob_start(); // Iniciar buffer de salida para evitar errores de envío de cabeceras
require('assets/public/fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, mb_convert_encoding('MULTISERVICIOS CASA LAI, C.A.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, mb_convert_encoding('CARRERA 32 ENTRE CALLES 32 Y 33 Nº 32-42 BARQUISIMETO ESTADO LARA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('04245483493, 04123661369, 04245483493, 04123661369.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SERVICIO TÉCNICO A IMPRESORAS GARANTIZADO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 5, mb_convert_encoding('No se garantiza la disponibilidad de los productos una vez pasados los días después de ser creada la orden de compra', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(), 0, 0, 'C');
    }
}

// Inicializar PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Verificar que existan datos en la variable $res provista por el controlador
if (!empty($res)) {
    $cliente = $res[0]; // Extraer datos del cliente (comunes en todas las filas de la consulta)

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 5, mb_convert_encoding('CÓDIGO DE ORDEN DE COMPRA: ' . $cliente['id_orden'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 5, mb_convert_encoding('NOMBRE: ' . $cliente['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('C.I. / R.I.F.: ' . $cliente['cedula'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('DIRECCIÓN: ' . $cliente['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('TELÉFONO: ' . $cliente['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(3);
    $pdf->Cell(50, 5, mb_convert_encoding('FECHA DOCUMENTO: ' . date('d-m-Y', strtotime($cliente["fecha"])), 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(5);
}

$total_documento = 0;
$ancho_precio_max = 25; 
$ancho_total_max = 25; 

// Cálculo dinámico de anchos de columna para evitar desbordamiento de cifras
foreach ($res as $item) {
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $item['cantidad'] * $precio_unitario;
    
    $texto_precio = number_format($precio_unitario, 2) . ' BS';
    $texto_total = number_format($subtotal, 2) . ' BS';
    
    $ancho_precio = $pdf->GetStringWidth($texto_precio) + 8; 
    $ancho_total = $pdf->GetStringWidth($texto_total) + 8;
    
    if ($ancho_precio > $ancho_precio_max) $ancho_precio_max = $ancho_precio;
    if ($ancho_total > $ancho_total_max) $ancho_total_max = $ancho_total;
}

// Ancho disponible total en A4 estándar es ~190mm
$ancho_descripcion = 190 - 20 - $ancho_precio_max - $ancho_total_max;

// Renderizar Encabezados de la Tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell(20, 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_precio_max, 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_total_max, 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();

// Renderizar Filas de Productos
$pdf->SetFont('Arial', '', 10);
foreach ($res as $item) {
    $descripcion = $item['producto'] . ' ' . $item['nombre_modelo'] . ' ' . $item['nombre_marca'];
    $cantidad = $item['cantidad'];
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $cantidad * $precio_unitario;

    $pdf->Cell($ancho_descripcion, 7, mb_convert_encoding($descripcion, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(20, 7, mb_convert_encoding($cantidad, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($ancho_precio_max, 7, mb_convert_encoding(number_format($precio_unitario, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
    $pdf->Cell($ancho_total_max, 7, mb_convert_encoding(number_format($subtotal, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
    $pdf->Ln();

    $total_documento += $subtotal;
}

// Cálculos del bloque de Totales Financieros
$pdf->Ln(5);
$descuento = floatval($cliente['descuento'] ?? 0);
$subtotal_neto = $total_documento - $descuento;
$iva = $subtotal_neto * 0.16;
$total_final = $subtotal_neto + $iva;

$ancho_bloque_valores = 20 + $ancho_precio_max + $ancho_total_max;

$pdf->SetFont('Arial', 'B', 10);

// Fila Subtotal
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('SUB-TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell($ancho_bloque_valores, 7, mb_convert_encoding(number_format($total_documento, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 1, 'R');

// Fila Descuento
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DESCUENTO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell($ancho_bloque_valores, 7, mb_convert_encoding(number_format($descuento, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 1, 'R');

// Fila Delivery
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DELIVERY', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell($ancho_bloque_valores, 7, mb_convert_encoding('0.00 BS', 'ISO-8859-1', 'UTF-8'), 1, 1, 'R');

// Fila IVA
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('I.V.A 16%', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->Cell($ancho_bloque_valores, 7, mb_convert_encoding(number_format($iva, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 1, 'R');

// Fila Total Documento
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('TOTAL ORDEN DE COMPRA', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
$pdf->SetFillColor(240, 240, 240); // Ligero fondo gris para resaltar el total
$pdf->Cell($ancho_bloque_valores, 7, mb_convert_encoding(number_format($total_final, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 1, 'R', true);

// Limpiar el buffer de salida
ob_end_clean();

// Seguridad: Sanitizar el nombre del cliente eliminando caracteres incompatibles con nombres de archivo HTTP
$nombre_cliente_safe = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', $cliente['nombre']));

// Construcción del nombre del archivo final
$nombre_archivo = 'Orden_' . $cliente['id_orden'] . '_' . $nombre_cliente_safe . '_' . date('Y-m-d') . '.pdf';

// Forzar descarga del navegador
$pdf->Output('D', mb_convert_encoding($nombre_archivo, 'ISO-8859-1', 'UTF-8'));
exit;
?>