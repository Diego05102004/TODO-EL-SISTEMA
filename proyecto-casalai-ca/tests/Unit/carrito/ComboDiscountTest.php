<?php

use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

final class ComboDiscountTest extends TestCase
{
    public function testDiscountAppliesOnlyToComboUnits(): void
    {
        $comboUnitPrice = Productos::calcularPrecioConDescuento(100, 20);
        $individualUnitPrice = Productos::calcularPrecioConDescuento(100, 0);

        $this->assertSame(80.0, $comboUnitPrice);
        $this->assertSame(100.0, $individualUnitPrice);
        $this->assertSame(1080.0, ($comboUnitPrice * 1) + ($individualUnitPrice * 10));
    }

    public function testDiscountBoundaries(): void
    {
        $this->assertSame(100.0, Productos::calcularPrecioConDescuento(100, 0));
        $this->assertSame(0.0, Productos::calcularPrecioConDescuento(100, 100));
    }

    public function testDiscountOutsideAllowedRangeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Productos::calcularPrecioConDescuento(100, 100.01);
    }
}
