<?php
// Limpiar cualquier salida anterior
if (ob_get_level()) {
    ob_end_clean();
}

// Iniciar nuevo buffer
ob_start();

error_log("Iniciando generación de PDF");

// Verificar si FPDF está disponible
$fpdfPath = __DIR__ . '/../assets/public/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    die("Error: No se encontró la biblioteca FPDF en: " . $fpdfPath);
}

require($fpdfPath);

// Verificar si la variable $orden está definida
if (!isset($orden) || !is_array($orden)) {
    error_log("Error: La variable \$orden no está definida o no es un array");
    die("Error: No se recibieron datos de la orden de despacho.");
}

error_log("Datos recibidos en descargarOrdenDespacho.php: " . print_r($orden, true));

// La variable $orden ahora viene directamente del método obtenerDatosParaPDF
// No necesitamos hacer reset() ya que es un array simple, no un array de arrays
$datosOrden = $orden;

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode('MULTISERVICIOS CASA LAI, C.A.'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('CARRERA 32 ENTRE CALLES 32 Y 33 Nº 32-42 BARQUISIMETO ESTADO LARA'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('04245483493, 04123661369, 04245483493, 04123661369.'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('ORDEN DE DESPACHO'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode('Documento generado el ' . date('d/m/Y H:i:s')), 0, 0, 'C');
        $this->Ln(3);
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Verificar que $orden sea un array y tenga datos
if (!empty($orden) && is_array($orden)) {
    // Los datos ya vienen en el formato correcto desde obtenerDatosParaPDF
    $cliente = $datosOrden; // Los datos del cliente están en el array
    
    // Verificar que los datos necesarios existen
    if (!isset($datosOrden['id_orden_despachos']) || !isset($datosOrden['id_factura'])) {
        die("Datos de la orden de despacho incompletos.");
    }

    // Encabezado de la orden
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, 'ORDEN DE DESPACHO #' . $datosOrden['id_orden_despachos'], 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 5, utf8_decode('Número de Orden:'), 0, 0);
    $pdf->Cell(0, 5, $datosOrden['id_orden_despachos'], 0, 1);
    
    $pdf->Cell(40, 5, utf8_decode('Número de Factura:'), 0, 0);
    $pdf->Cell(0, 5, $datosOrden['id_factura'], 0, 1);
    
    $pdf->Cell(40, 5, 'Fecha de Despacho:', 0, 0);
    $pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($datosOrden['fecha_despacho'])), 0, 1);
    
    $pdf->Cell(40, 5, 'Estado:', 0, 0);
    $pdf->Cell(0, 5, $datosOrden['estado'], 0, 1);
    
    $pdf->Ln(5);
    
    // Datos del cliente
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'DATOS DEL CLIENTE', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(40, 5, 'Nombre:', 0, 0);
    $pdf->Cell(0, 5, $cliente['cliente'], 0, 1);
    
    $pdf->Cell(40, 5, utf8_decode('Cédula/RIF:'), 0, 0);
    $pdf->Cell(0, 5, $cliente['cedula'], 0, 1);
    
    $pdf->Ln(5);
    
    // Detalles de los productos
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'DETALLE DE PRODUCTOS', 0, 1);
    
    // Encabezado de la tabla de productos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(25, 7, utf8_decode('Código'), 1, 0, 'C');
    $pdf->Cell(80, 7, utf8_decode('Descripción'), 1, 0, 'C');
    $pdf->Cell(25, 7, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Precio', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Total', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 9);
    $total = isset($datosOrden['total']) ? $datosOrden['total'] : 0;
    
    // Verificar si hay productos en la orden
    if (isset($datosOrden['productos']) && is_array($datosOrden['productos'])) {
        foreach ($datosOrden['productos'] as $producto) {
            $subtotal = $producto['cantidad'] * $producto['precio'];
            
            $pdf->Cell(25, 7, $producto['codigo'], 1, 0, 'C');
            $pdf->Cell(80, 7, utf8_decode($producto['producto']), 1, 0);
            $pdf->Cell(25, 7, $producto['cantidad'], 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($producto['precio'], 2, ',', '.') . ' BS', 1, 0, 'R');
            $pdf->Cell(30, 7, number_format($subtotal, 2, ',', '.') . ' BS', 1, 1, 'R');
        }
    } else {
        $pdf->Cell(190, 10, 'No hay productos en esta orden', 1, 1, 'C');
    }
    
    // Total
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(160, 7, 'TOTAL:', 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($total, 2, ',', '.') . ' BS', 1, 1, 'R');
    
    // Observaciones
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'OBSERVACIONES', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, 'Por favor, verifique que todos los productos coincidan con su pedido antes de firmar este documento.', 0, 'L');
    $pdf->Ln(10);
    
    // Firmas
    $pdf->Cell(95, 5, '________________________', 0, 0, 'C');
    $pdf->Cell(95, 5, '________________________', 0, 1, 'C');
    $pdf->Cell(95, 5, 'Firma del Cliente', 0, 0, 'C');
    $pdf->Cell(95, 5, 'Firma del Responsable', 0, 1, 'C');
}

// Nombre del archivo
$nombre_archivo = 'OrdenDespacho_' . $datosOrden['id_orden_despachos'] . '_' . date('Y-m-d') . '.pdf';

// Limpiar el buffer y deshabilitar la salida de errores
while (ob_get_level()) {
    ob_end_clean();
}

// Establecer los encabezados para la descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Enviar el PDF al navegador
$pdf->Output('D', $nombre_archivo, true);
exit;
