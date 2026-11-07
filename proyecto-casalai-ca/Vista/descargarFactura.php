<?php
ob_start(); // Iniciar buffer de salida
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
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, mb_convert_encoding('No se garantiza la disponibilidad de los productos una vez pasados los días después de ser creada la orden de compra', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(), 0, 0, 'C');
    }

    function Row($data, $widths, $lineHeight = 6) {
        $maxLines = 1;
        foreach ($data as $index => $text) {
            $maxLines = max($maxLines, $this->NbLines($widths[$index], $text));
        }
        $height = $lineHeight * $maxLines;

        if ($this->GetY() + $height > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($widths[0], 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1);
            $this->Cell($widths[1], 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $this->Cell($widths[2], 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $this->Cell($widths[3], 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $this->Ln();
            $this->SetFont('Arial', '', 10);
        }

        foreach ($data as $index => $text) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $widths[$index], $height);
            if ($index === 0) {
                $this->MultiCell($widths[$index], $lineHeight, $text, 0, 'L');
            } else {
                $this->Cell($widths[$index], $height, $text, 0, 0, 'C');
            }
            $this->SetXY($x + $widths[$index], $y);
        }

        $this->Ln($height);
    }

    function NbLines($width, $text) {
        $fontWidths = $this->CurrentFont['cw'];
        if ($width == 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $maxWidth = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $text = str_replace("\r", '', $text);
        $length = strlen($text);
        if ($length > 0 && $text[$length - 1] === "\n") {
            $length--;
        }

        $lineCount = 1;
        $lineWidth = 0;
        $lastSpace = -1;
        $lineStart = 0;
        for ($index = 0; $index < $length; $index++) {
            $character = $text[$index];
            if ($character === "\n") {
                $index++;
                $lastSpace = -1;
                $lineStart = $index;
                $lineWidth = 0;
                $lineCount++;
                continue;
            }
            if ($character === ' ') {
                $lastSpace = $index;
            }
            $lineWidth += $fontWidths[$character] ?? 0;
            if ($lineWidth > $maxWidth) {
                if ($lastSpace === -1) {
                    if ($index === $lineStart) {
                        $index++;
                    } else {
                        $index = $lineStart;
                    }
                } else {
                    $index = $lastSpace;
                }
                $lastSpace = -1;
                $lineStart = $index + 1;
                $lineCount++;
                $lineWidth = 0;
            }
        }

        return $lineCount;
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Datos del cliente
if (!empty($res)) {
    $cliente = $res[0]; // Los datos del cliente se repiten por fila

    $pdf->Cell(50, 5, mb_convert_encoding('CÓDIGO DE ORDEN DE COMPRA: ' . $cliente['id_factura'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('NOMBRE: ' . $cliente['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('C.I.: V' . $cliente['cedula'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('DIRECCIÓN: ' . $cliente['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('TELÉFONO: ' . $cliente['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(50, 5, mb_convert_encoding('FECHA DOCUMENTO: ' . $cliente["fecha"], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(5);
}
/*
// Encabezado tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(130, 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(20, 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(20, 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();
*/
$pdf->SetFont('Arial', '', 10);
$total_documento = 0;
$ahorro_combos = 0;

// Calcular anchos dinámicos para precio y total
$ancho_precio_max = 25; // ancho mínimo
$ancho_total_max = 25; // ancho mínimo

foreach ($res as $item) {
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $item['cantidad'] * $precio_unitario;
    
    $texto_precio = number_format($precio_unitario, 2) . ' BS';
    $texto_total = number_format($subtotal, 2) . ' BS';
    
    $ancho_precio = $pdf->GetStringWidth($texto_precio) + 10; // +10 para padding
    $ancho_total = $pdf->GetStringWidth($texto_total) + 10;
    
    if ($ancho_precio > $ancho_precio_max) {
        $ancho_precio_max = $ancho_precio;
    }
    if ($ancho_total > $ancho_total_max) {
        $ancho_total_max = $ancho_total;
    }
}

// Ajustar ancho de descripción (total 190 - cantidad(20) - precio - total)
$ancho_descripcion = 190 - 20 - $ancho_precio_max - $ancho_total_max;

// Dibujar encabezado con anchos calculados
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_precio_max, 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_total_max, 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);

foreach ($res as $item) {
    $descripcion = trim($item['producto'] . ' ' . $item['nombre_modelo'] . ' ' . $item['nombre_marca']);
    if (!empty($item['id_combo'])) {
        $nombreCombo = trim((string)($item['nombre_combo'] ?? ''));
        $descripcion .= ' (Combo: ' . ($nombreCombo !== '' ? $nombreCombo : '#' . $item['id_combo']) . ')';
    }
    $cantidad = $item['cantidad'];
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $cantidad * $precio_unitario;
    if (!empty($item['id_combo'])) {
        $precio_base = (float)($item['precio_base_convertido'] ?? $precio_unitario);
        $ahorro_combos += max(0, $precio_base - $precio_unitario) * $cantidad;
    }

    $pdf->Row([
        mb_convert_encoding($descripcion, 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding((string)$cantidad, 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding(number_format($precio_unitario, 2) . ' BS', 'ISO-8859-1', 'UTF-8'),
        mb_convert_encoding(number_format($subtotal, 2) . ' BS', 'ISO-8859-1', 'UTF-8')
    ], [$ancho_descripcion, 20, $ancho_precio_max, $ancho_total_max]);

    $total_documento += $subtotal;
}

// Totales
$pdf->Ln(5);
$descuento = floatval($cliente['descuento']);
$iva = ($total_documento - $descuento) * 0.16;
$total_final = ($total_documento - $descuento) + $iva;

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('SUB-TOTAL', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($total_documento, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('AHORRO POR COMBOS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($ahorro_combos, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('I.V.A 16%', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($iva, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('TOTAL DOCUMENTO', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($total_final, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln(10);

// Limpiar el buffer
ob_end_clean();

// Nombre del archivo
$nombre_archivo = $cliente['nombre'] . '_' . $cliente['cedula'] . '_factura_' . $cliente['id_factura'] . '_' . date('Y-m-d', strtotime($cliente['fecha'])) . '.pdf';

// Descargar el archivo
$pdf->Output('D', mb_convert_encoding($nombre_archivo, 'ISO-8859-1', 'UTF-8'));
exit;
?>
