<?php
// Verificar si el usuario ha iniciado sesión
require_once __DIR__ . '/../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if (!isset($_SESSION['name'])) {
    // Redirigir al usuario a la página de inicio de sesión
    header('Location: .');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <?php include 'header.php'; ?>
  <style>
    .container {
      max-width: 1200px;
      margin: 40px auto;
    }

    .card-dashboard {
      flex: 1 1 260px;
      min-width: 260px;
      max-width: 320px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      padding: 32px 24px;
      text-align: center;
      margin: 0 auto;
    }

    .card-dashboard h4 {
      margin-bottom: 12px;
      max-width: 150px;
    }

    .modulos-dashboard {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      justify-content: center;
      margin-top: 10px;
      align-items: stretch;
    }

    .btn-dashboard {
      width: 120px;
      text-align: center;
      padding: 6px 8px;
      border-radius: 6px;
      color: white;
      font-size: 12px;
      text-decoration: none;
      display: inline-block;
      transition: transform 0.2s ease, background-color 0.2s ease;
      flex-shrink: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .btn-dashboard:hover {
      transform: scale(1.05);
      filter: brightness(1.1);
    }

    /* Mobile Responsive for Dashboard Cards */
    @media (max-width: 768px) {
      .container {
        margin: 20px auto;
        padding: 0 15px;
      }

      .card-dashboard {
        flex: 1 1 100%;
        min-width: 100%;
        max-width: 100%;
        padding: 20px 16px;
        margin-bottom: 20px;
      }

      .card-dashboard h4 {
        max-width: 100%;
        margin-bottom: 16px;
        font-size: 1.1rem;
      }

      .modulos-dashboard {
        flex-direction: column;
        gap: 12px;
        align-items: center;
        margin-top: 16px;
      }

      .btn-dashboard {
        width: 100%;
        max-width: 200px;
        padding: 12px 16px;
        font-size: 13px;
      }

      .card-dashboard img {
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
      }
    }

    @media (max-width: 480px) {
      .container {
        margin: 15px auto;
        padding: 0 10px;
      }

      .card-dashboard {
        padding: 16px 12px;
        margin-bottom: 15px;
      }

      .card-dashboard h4 {
        font-size: 1rem;
        margin-bottom: 12px;
      }

      .modulos-dashboard {
        gap: 10px;
        margin-top: 12px;
      }

      .btn-dashboard {
        max-width: 180px;
        padding: 10px 14px;
        font-size: 12px;
      }

      .card-dashboard img {
        width: 40px;
        height: 40px;
        margin-bottom: 10px;
      }
    }

    @media (max-width: 360px) {
      .card-dashboard {
        padding: 12px 8px;
      }

      .card-dashboard h4 {
        font-size: 0.9rem;
        margin-bottom: 10px;
      }

      .btn-dashboard {
        max-width: 160px;
        padding: 8px 12px;
        font-size: 11px;
      }

      .card-dashboard img {
        width: 36px;
        height: 36px;
        margin-bottom: 8px;
      }
    }
/* ===== CARRUSEL CENTER MODE ===== */

.carousel-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  padding: 20px 0;
  display: flex;
  justify-content: center;
  align-items: center;
}

.carousel-track {
  display: flex;
  align-items: center;
  gap: 40px;
  transition: transform 0.4s ease;
  padding: 0;
  width: max-content;
}

.carousel-item {
  width: 580px;
  height: 340px;
  flex-shrink: 0;
  transition: transform 0.4s ease, opacity 0.4s ease;
  opacity: 0.6;
  transform: scale(0.9);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 24px;
  text-align: center;
  margin: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  position: relative;
}

/* Card activa centrada */
.carousel-item.active {
  opacity: 1;
  transform: scale(1);
  z-index: 5;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

/* Botones */
.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.6);
  border: none;
  color: white;
  font-size: 28px;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 10;
}

.carousel-btn.prev { left: 250px; }
.carousel-btn.next { right: 250px; }

.carousel-btn:hover {
  background: rgba(0,0,0,0.9);
}

/* Responsive */
@media (max-width: 760px) {
  .carousel-item {
    flex: 0 0 85%;
  }

  .carousel-btn {
    display: none;
  }

  .carousel-btn.prev { left: 10%; }
  .carousel-btn.next { right: 250px; }

}
  </style>
</head>

<body class="fondo"
  style="height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php
include 'NewNavBar.php';

// Inicializar variables de permisos y rol si no existen
if (!isset($permisosConsulta)) {
    $permisosConsulta = [];
}

if (!isset($nombre_rol)) {
    $nombre_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Cliente';
}

// Debug: mostrar información de sesión y permisos
error_log("Dashboard - Rol: " . $nombre_rol);
error_log("Dashboard - Permisos: " . print_r($permisosConsulta, true));

// AGREGAR MÓDULOS DE REPORTES AL ARRAY PRINCIPAL (igual que en navbar)
$modulos = [
    'Usuario' => ['Gestionar Usuario', 'assets/img/users-round.svg', '?pagina=usuario'],
    'Recepcion' => ['Gestionar Recepcion', 'assets/img/package-open.svg', '?pagina=recepcion'],
    'Despacho' => ['Gestionar Despacho', 'assets/img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'assets/img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'assets/img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'assets/img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'assets/img/package-search.svg', '?pagina=categoria'],
    'Compra Física' => ['Gestionar Ventas Presenciales', 'assets/img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'assets/img/truck.svg', '?pagina=proveedor'],
    'Clientes' => ['Gestionar Clientes', 'assets/img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'assets/img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'assets/img/credit-card.svg', '?pagina=pasarela'],
    'Pedidos' => ['Gestionar Pedidos', 'assets/img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'assets/img/list-ordered.svg', '?pagina=ordendespacho'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'assets/img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'assets/img/dollar-sign.svg', '?pagina=finanza'],
    'permisos' => ['Gestionar Permisos', 'assets/img/key-round.svg', '?pagina=permiso'],
    'Roles' => ['Gestionar Roles', 'assets/img/user-round-search.svg', '?pagina=rol'],
    'bitacora' => ['Gestionar Bitácora', 'assets/img/notebook.svg', '?pagina=bitacora'],
    'Backup' => ['Gestionar Backup', 'assets/img/files.svg', '?pagina=backup'],
    
    // MÓDULOS DE REPORTES (AGREGADOS)
    'reporteUsuario' => ['Reporte de Perfiles', 'assets/img/chart-bar.svg', '?pagina=reporteUsuario'],
    'reporteInventario' => ['Reporte de Inventario', 'assets/img/chart-bar.svg', '?pagina=reporteInventario'],
    'reporteProductos' => ['Reporte de Productos', 'assets/img/chart-bar.svg', '?pagina=reporteProductos'],
    'reporteProveedores' => ['Reporte de Proveedores', 'assets/img/chart-bar.svg', '?pagina=reporteProveedores'],
    'reporteVentas' => ['Reporte de Despachos', 'assets/img/chart-bar.svg', '?pagina=reporteVentas'],
    'reporteFinanzas' => ['Reporte de Finanzas', 'assets/img/chart-bar.svg', '?pagina=reporteFinanzas'],
    'reporteCliente' => ['Reporte de Clientes', 'assets/img/chart-bar.svg', '?pagina=reporteCliente'],
];

// Define los grupos de módulos y su icono (ACTUALIZADO CON CONDICIONALES)
$grupos = [
    'Administrar Perfiles' => [
        'modulos' => ['Usuario'],
        'reportes' => ['reporteUsuario'],
        'icon' => 'assets/img/users-round.svg',
        'color' => '#4e73df',
        'condicion' => !empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Inventario' => [
        'modulos' => ['Recepcion'],
        'reportes' => ['reporteInventario'],
        'icon' => 'assets/img/package-open.svg',
        'color' => '#1cc88a',
        'condicion' => !empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Productos' => [
        'modulos' => ['Marcas', 'Modelos', 'Productos', 'Categorias'],
        'reportes' => ['reporteProductos'],
        'icon' => 'assets/img/package-search.svg',
        'color' => '#36b9cc',
        'condicion' => ($nombre_rol !== 'Cliente') && (
            !empty($permisosConsulta['Marcas']) || 
            !empty($permisosConsulta['Modelos']) || 
            !empty($permisosConsulta['Productos']) || 
            !empty($permisosConsulta['Categorias']) ||
            !empty($permisosConsulta['reporteProductos'])
        )
    ],
    'Administrar Proveedores' => [
        'modulos' => ['Proveedores'],
        'reportes' => ['reporteProveedores'],
        'icon' => 'assets/img/truck.svg',
        'color' => '#f6c23e',
        'condicion' => !empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Clientes' => [
        'modulos' => ['Clientes'],
        'reportes' => ['reporteCliente'],
        'icon' => 'assets/img/users-round.svg',
        'color' => '#e74a3b',
        'condicion' => !empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Ventas' => [
        'modulos' => ['Catalogo', 'Compra Física', 'pasarela', 'Pedidos', 'Ordenes de despacho', 'Despacho'],
        'reportes' => ['reporteVentas'],
        'icon' => 'assets/img/shopping-cart.svg',
        'color' => '#858796',
        'condicion' => !empty($permisosConsulta['Catalogo']) || 
                      !empty($permisosConsulta['Compra Física']) || 
                      !empty($permisosConsulta['pasarela']) || 
                      !empty($permisosConsulta['Pedidos']) || 
                      !empty($permisosConsulta['Ordenes de despacho']) || 
                      !empty($permisosConsulta['Despacho']) ||
                      !empty($permisosConsulta['reporteVentas'])
    ],
    'Administrar Finanzas' => [
        'modulos' => ['Cuentas bancarias', 'Finanzas'],
        'reportes' => ['reporteFinanzas'],
        'icon' => 'assets/img/dollar-sign.svg',
        'color' => '#20c997',
        'condicion' => ((!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])) && $nombre_rol !== 'Cliente')
    ],
    'Administrar Seguridad' => [
        'modulos' => ['permisos', 'Roles', 'bitacora', 'Backup'],
        'reportes' => [],
        'icon' => 'assets/img/key-round.svg',
        'color' => '#fd7e14',
        'condicion' => !empty($permisosConsulta['permisos']) || 
                      !empty($permisosConsulta['Roles']) || 
                      !empty($permisosConsulta['bitacora']) || 
                      !empty($permisosConsulta['Backup'])
    ],
];

// FUNCIÓN PARA VERIFICAR SI UN MÓDULO DEBE MOSTRARSE
function mostrarModulo($modulo, $permisosConsulta, $nombre_rol) {
    // Si es Cliente, mostrar solo módulos específicos
    if ($nombre_rol === 'Cliente') {
        $modulosCliente = ['Catalogo', 'pasarela', 'Pedidos'];
        return in_array($modulo, $modulosCliente) && !empty($permisosConsulta[$modulo]);
    }
    
    // Para otros roles, verificar permiso
    return !empty($permisosConsulta[$modulo]);
}
?>

<div class="container">
  <h3 class="tabla-titulo-2" style="margin-top:20px; margin-bottom:20px;">Panel Principal</h3>
    <div class="carousel-container">
        <button class="carousel-btn prev">&#10094;</button>

        <div class="carousel-track">
            <!-- Aquí se generan dinámicamente tus cards -->
          <?php
        // Debug: mostrar información completa antes del foreach
        error_log("Dashboard - Iniciando generación de cards");
        error_log("Dashboard - Total grupos: " . count($grupos));
        error_log("Dashboard - Sesión completa: " . print_r($_SESSION, true));
        
        // TEMPORAL: Forzar mostrar todos los grupos para diagnóstico
        $mostrarTodosGrupos = true; // Cambiar a false cuando funcione
        
        foreach ($grupos as $grupo => $info) {
            error_log("Dashboard - Evaluando grupo: $grupo");
            error_log("Dashboard - Condición del grupo: " . ($info['condicion'] ? 'true' : 'false'));
            
            // Verificar si el grupo debe mostrarse (igual que en navbar)
            if (!$mostrarTodosGrupos && !$info['condicion']) {
                error_log("Dashboard - Grupo $grupo omitido por condición falsa");
                continue;
            }

            $modulosPermitidos = [];

            // Agregar módulos principales con permisos
            foreach ($info['modulos'] as $mod) {
                $permisoModulo = $mostrarTodosGrupos ? true : mostrarModulo($mod, $permisosConsulta, $nombre_rol);
                error_log("Dashboard - Módulo $mod: " . ($permisoModulo ? 'permitido' : 'denegado'));
                if ($permisoModulo) {
                    $modulosPermitidos[] = $mod;
                }
            }

            // Agregar reportes con permisos (usando la misma lógica que en el navbar)
            foreach ($info['reportes'] as $reporte) {
                $mostrarReporte = false;
                switch ($reporte) {
                    case 'reporteUsuario':
                        $mostrarReporte = !empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente';
                        break;
                    case 'reporteInventario':
                        $mostrarReporte = !empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente';
                        break;
                    case 'reporteProductos':
                        $mostrarReporte = ($nombre_rol !== 'Cliente') && (
                            !empty($permisosConsulta['Marcas']) || 
                            !empty($permisosConsulta['Modelos']) || 
                            !empty($permisosConsulta['Productos']) || 
                            !empty($permisosConsulta['Categorias'])
                        );
                        break;
                    case 'reporteProveedores':
                        $mostrarReporte = !empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente';
                        break;
                    case 'reporteVentas':
                        $mostrarReporte = !empty($permisosConsulta['Despacho']) && $nombre_rol !== 'Cliente';
                        break;
                    case 'reporteFinanzas':
                        $mostrarReporte = ((!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])) && $nombre_rol !== 'Cliente');
                        break;
                    case 'reporteCliente':
                        $mostrarReporte = !empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente';
                        break;
                    default:
                        $mostrarReporte = !empty($permisosConsulta[$reporte]);
                }
                if ($mostrarReporte && !empty($modulos[$reporte])) {
                    $modulosPermitidos[] = $reporte;
                }
            }

            // Mostrar el grupo solo si tiene módulos o reportes permitidos
            if (count($modulosPermitidos) > 0) {
                $icono = $info['icon'];
                $color = $info['color'];
                
                // Debug: mostrar que se está generando esta card
                error_log("Dashboard - Generando card para grupo: $grupo con " . count($modulosPermitidos) . " módulos");
          ?>
          <div class="card-dashboard carousel-item" style="border-top:6px solid <?php echo $color; ?>;">
            <img src="<?php echo $icono; ?>" alt="icono" style="width:56px; height:56px; margin-bottom:18px;">
            <div class="modulos-dashboard">
              <h4 style="color:<?php echo $color; ?>;"><?php echo htmlspecialchars($grupo); ?></h4>
              <?php foreach ($modulosPermitidos as $modulo): ?>
              <a href="<?php echo $modulos[$modulo][2]; ?>" class="btn-dashboard"
                style="background:<?php echo $color; ?>;">
                <?php echo htmlspecialchars($modulos[$modulo][0]); ?>
              </a>
              <?php endforeach; ?>

            </div>
          </div>
          <?php
              }
          }
          ?>
        </div>

        <button class="carousel-btn next">&#10095;</button>
    </div>
</div>
  <script>
    const sesion = <?php echo json_encode($_SESSION); ?>;
  </script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const track = document.querySelector(".carousel-track");
    const items = document.querySelectorAll(".carousel-item");
    const btnNext = document.querySelector(".carousel-btn.next");
    const btnPrev = document.querySelector(".carousel-btn.prev");

    if (!track || items.length === 0) {
        console.log('No se encontraron elementos del carrusel');
        return;
    }

    let currentIndex = 0;

    function updateCarousel() {
        // Remover clase active de todos los items
        items.forEach(item => item.classList.remove("active"));

        // Agregar clase active al item actual
        items[currentIndex].classList.add("active");

        // Calcular el desplazamiento para centrar el item activo
        const itemWidth = items[0].offsetWidth + 30; // ancho + gap
        const containerWidth = track.parentElement.offsetWidth;
        const offset = (currentIndex * itemWidth) - (containerWidth / 2) + (items[0].offsetWidth / 2);

        // Aplicar transformación
        track.style.transform = `translateX(-${offset}px)`;
        
        console.log(`Item actual: ${currentIndex}, Offset: ${offset}px`);
    }

    // Event listeners para botones
    btnNext.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % items.length;
        updateCarousel();
    });

    btnPrev.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        updateCarousel();
    });

    // Inicializar carrusel
    updateCarousel();

    // Manejar responsive
    window.addEventListener('resize', () => {
        updateCarousel();
    });
});
</script>

<script src="assets/javascript/jwt_validator.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>