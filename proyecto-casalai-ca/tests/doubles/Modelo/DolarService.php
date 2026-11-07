<?php
// Stub de DolarService para pruebas (override del archivo de producción)
// Este archivo se prioriza gracias a include_path configurado en tests/bootstrap.php

class DolarService {
    public function obtenerRegistroDelDia(): array {
        return [
            'precio' => 40.0,
            'fecha' => date('Y-m-d H:i:s'),
        ];
    }

    public function obtenerPrecioDelDia(): float {
        return 40.0;
    }
}
