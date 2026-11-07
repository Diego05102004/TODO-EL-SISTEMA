<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;

class PermisosReadTest extends TestCase {
    private Permisos $perm;

    protected function setUp(): void {
        $this->perm = new Permisos();
    }

    public function test_listar_roles_y_modulos(): void {
        $roles = $this->perm->getRoles();
        $modulos = $this->perm->getModulos();
        $this->assertIsArray($roles);
        $this->assertIsArray($modulos);
    }

    public function test_obtener_matriz_de_permisos(): void {
        $perms = $this->perm->getPermisosPorRolModulo();
        $this->assertIsArray($perms);
    }

    public function test_obtener_permisos_usuario_modulo(): void {
        // Usamos el primer rol y primer modulo si existen, sino se omite
        $roles = $this->perm->getRoles();
        $modulos = $this->perm->getModulos();
        if (empty($roles) || empty($modulos)) {
            $this->markTestSkipped('No hay roles o módulos para validar permisos.');
        }
        $idRol = (int)$roles[0]['id_rol'];
        $nombreModulo = $modulos[0]['nombre_modulo'];
        $res = $this->perm->getPermisosUsuarioModulo($idRol, $nombreModulo);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('consultar', $res);
        $this->assertArrayHasKey('incluir', $res);
        $this->assertArrayHasKey('modificar', $res);
        $this->assertArrayHasKey('eliminar', $res);
    }
}
