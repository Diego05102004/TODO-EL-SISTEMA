# Casos de Prueba para TestLink

Este documento consolida los casos de prueba (Unit e Integración) preparados para los módulos: Login, Catálogo, Orden de Despacho, Proveedores, Clientes, Modelos y Marcas.

## Convenciones
- Estructura por caso: ID, Título, Precondiciones, Datos de prueba, Pasos, Resultado esperado.
- Los IDs siguen el patrón: MOD-TIPO-###, donde TIPO es UNIT o FEAT.

---

## Módulo Login

- [ID] LGN-UNIT-001
  - Título: Login correcto
  - Precondiciones: Usuario `admin` con hash válido y rol asociado
  - Datos: username=admin, password=secret123
  - Pasos: set_username, set_password, existe()
  - Esperado: resultado=existe, mensaje=admin, nombre_rol=Supervisor

- [ID] LGN-UNIT-002
  - Título: Login falla por contraseña
  - Precondiciones: Usuario `admin` existe
  - Datos: username=admin, password=wrong
  - Pasos: existe()
  - Esperado: resultado=noexiste

- [ID] LGN-UNIT-003
  - Título: Solicitar recuperación OK
  - Precondiciones: Email registrado
  - Datos: email=a@b.com
  - Pasos: solicitarRecuperacion(email)
  - Esperado: status=success, devuelve token e id_usuario

- [ID] LGN-UNIT-004
  - Título: Solicitar recuperación email no registrado
  - Precondiciones: Email no registrado
  - Datos: email=no@existe.com
  - Pasos: solicitarRecuperacion(email)
  - Esperado: status=error, mensaje indicativo

- [ID] LGN-UNIT-005
  - Título: Validar token OK
  - Precondiciones: Token vigente en tbl_recuperar
  - Datos: id_usuario=10, token=tok
  - Pasos: validarToken(10, tok)
  - Esperado: arreglo con id_usuario=10

- [ID] LGN-UNIT-006
  - Título: Actualizar password OK (transacción)
  - Precondiciones: Usuario válido
  - Datos: id_usuario=10, nueva_password=newpass
  - Pasos: actualizarPassword(10, newpass)
  - Esperado: true, se marca token utilizado

- [ID] LGN-UNIT-007
  - Título: Registrar usuario+cliente OK
  - Precondiciones: username libre, rol cliente disponible
  - Datos: nombre_usuario=nuevo, clave=clave, cedula=V-12, nombre=Ana, apellido=Gomez, correo=ana@test.com, telefono=0414, direccion=Dir 1
  - Pasos: registrarUsuarioYCliente(datos)
  - Esperado: status=success

- [ID] LGN-UNIT-008
  - Título: Registrar usuario duplicado
  - Precondiciones: username existente
  - Datos: nombre_usuario=existente, resto válidos
  - Pasos: registrarUsuarioYCliente(datos)
  - Esperado: status=error, mensaje duplicado

- [ID] LGN-FEAT-001
  - Título: Login correcto (integración)
  - Precondiciones: Hash coincide
  - Datos: admin/secret123
  - Pasos: existe()
  - Esperado: resultado=existe, nombre_rol=Supervisor

- [ID] LGN-FEAT-002
  - Título: Login falla por contraseña (integración)
  - Precondiciones: Usuario existe
  - Datos: admin/wrong
  - Pasos: existe()
  - Esperado: resultado=noexiste

- [ID] LGN-FEAT-003
  - Título: Solicitar recuperación OK (integración)
  - Precondiciones: Email registrado
  - Datos: a@b.com
  - Pasos: solicitarRecuperacion(email)
  - Esperado: status=success, token, id_usuario

- [ID] LGN-FEAT-004
  - Título: Validar token OK (integración)
  - Datos: id_usuario=10, token=tok
  - Pasos: validarToken(10, tok)
  - Esperado: id_usuario=10

- [ID] LGN-FEAT-005
  - Título: Actualizar password OK (integración)
  - Datos: id_usuario=10, newpass
  - Pasos: actualizarPassword(10, newpass)
  - Esperado: true

- [ID] LGN-FEAT-006
  - Título: Registrar usuario+cliente OK (integración)
  - Datos: ver LGN-UNIT-007
  - Pasos: registrarUsuarioYCliente
  - Esperado: status=success

- [ID] LGN-FEAT-007
  - Título: Registrar usuario duplicado (integración)
  - Datos: ver LGN-UNIT-008
  - Pasos: registrarUsuarioYCliente
  - Esperado: status=error

---

## Módulo Catálogo

- [ID] CAT-UNIT-001: Insertar combo → Esperado: true
- [ID] CAT-UNIT-002: Obtener productos activos → Esperado: lista con columnas clave
- [ID] CAT-UNIT-003: Obtener combos agregados → Esperado: lista con precio_total
- [ID] CAT-UNIT-004: Eliminar combo → Esperado: true
- [ID] CAT-UNIT-005: Obtener último id → Esperado: entero
- [ID] CAT-UNIT-006: Crear nuevo combo → Esperado: id
- [ID] CAT-UNIT-007: Insertar producto en combo → Esperado: true

- [ID] CAT-FEAT-001: Insertar combo (Integración) → true
- [ID] CAT-FEAT-002: Obtener productos (Integración) → lista no vacía
- [ID] CAT-FEAT-003: Obtener combos (Integración) → lista con precio_total
- [ID] CAT-FEAT-004: Eliminar combo (Integración) → true
- [ID] CAT-FEAT-005: Obtener último id (Integración) → 15
- [ID] CAT-FEAT-006: Crear nuevo combo (Integración) → id '901'
- [ID] CAT-FEAT-007: Insertar producto en combo (Integración) → true

---

## Módulo Orden de Despacho

- [ID] OD-UNIT-001: Facturas disponibles → lista con id_factura
- [ID] OD-UNIT-002: Obtener por ID → id_orden_despachos=5
- [ID] OD-UNIT-003: Listado con productos → productos no vacíos
- [ID] OD-UNIT-004: Descargar por ID → productos por orden
- [ID] OD-UNIT-005: Detalles de compra → productos
- [ID] OD-UNIT-006: Cambiar estado → true
- [ID] OD-UNIT-007: Anular orden → status=success
- [ID] OD-UNIT-008: Cambiar estatus usuarios → true

- [ID] ODF-FEAT-001: Facturas disponibles (Integración) → lista con id_factura
- [ID] ODF-FEAT-002: Obtener orden por ID (Integración) → id_orden_despachos=5
- [ID] ODF-FEAT-003: Listado con productos (Integración) → productos no vacíos
- [ID] ODF-FEAT-004: Descargar por ID (Integración) → productos
- [ID] ODF-FEAT-005: Detalles de compra (Integración) → productos
- [ID] ODF-FEAT-006: Cambiar estado (Integración) → true
- [ID] ODF-FEAT-007: Anular orden (Integración) → status=success
- [ID] ODF-FEAT-008: Cambiar estatus usuarios (Integración) → true

---

## Módulo Proveedores (Feature)

- [ID] PRV-FEAT-001: Registrar proveedor → true
- [ID] PRV-FEAT-002: Existe nombre proveedor → true
- [ID] PRV-FEAT-003: Obtener último proveedor → arreglo con nombre
- [ID] PRV-FEAT-004: Obtener proveedor por ID → id_proveedor
- [ID] PRV-FEAT-005: Modificar proveedor → true
- [ID] PRV-FEAT-006: Eliminar proveedor → true
- [ID] PRV-FEAT-007: Listar proveedores → lista con id/nombre
- [ID] PRV-FEAT-008: Reporte suministro → lista
- [ID] PRV-FEAT-009: Ranking proveedores → lista
- [ID] PRV-FEAT-010: Comparación de precios → lista
- [ID] PRV-FEAT-011: Dependencia proveedores → datos
- [ID] PRV-FEAT-012: Cambiar estatus → true

---

## Módulo Clientes (Feature)

- [ID] CLI-FEAT-001: Registrar cliente → true
- [ID] CLI-FEAT-002: Existe número de cédula → true
- [ID] CLI-FEAT-003: Obtener último cliente → arreglo
- [ID] CLI-FEAT-004: Obtener por ID → id_cliente
- [ID] CLI-FEAT-005: Modificar cliente → true
- [ID] CLI-FEAT-006: Eliminar lógico → true
- [ID] CLI-FEAT-007: Eliminar cliente → true
- [ID] CLI-FEAT-008: Listar clientes → lista
- [ID] CLI-FEAT-009: Listar todos clientes → lista

---

## Módulo Modelos (Feature)

- [ID] MOD-FEAT-001: Registrar modelo → true
- [ID] MOD-FEAT-002: Existe nombre modelo → true
- [ID] MOD-FEAT-003: Obtener último modelo → arreglo
- [ID] MOD-FEAT-004: Obtener por ID → id_modelo
- [ID] MOD-FEAT-005: Combo marcas → lista
- [ID] MOD-FEAT-006: Modificar modelo → true
- [ID] MOD-FEAT-007: Eliminar con productos asociados → manejo esperado
- [ID] MOD-FEAT-008: Eliminar sin productos asociados → true
- [ID] MOD-FEAT-009: Obtener modelo con marca → arreglo
- [ID] MOD-FEAT-010: Listar modelos → lista

---

## Módulo Marcas (Feature)

- [ID] MRK-FEAT-001: Registrar marca → true
- [ID] MRK-FEAT-002: Existe nombre marca → true
- [ID] MRK-FEAT-003: Obtener última marca → arreglo
- [ID] MRK-FEAT-004: Obtener por ID → id_marca
- [ID] MRK-FEAT-005: Modificar marca → true
- [ID] MRK-FEAT-006: Eliminar marca → true
- [ID] MRK-FEAT-007: Listar marcas → lista
- [ID] MRK-FEAT-008: Verificar modelos asociados → false (o comportamiento esperado)

---

## Módulo Perfil (Unit e Integración)

- [ID] PRF-UNIT-001: Ingresar usuario → true
- [ID] PRF-UNIT-002: Modificar usuario → true
- [ID] PRF-UNIT-003: Existencias (usuario/cedula/correo) → true/false según caso
- [ID] PRF-UNIT-004: Obtener último usuario → arreglo con username
- [ID] PRF-UNIT-005: Obtener usuario por id → arreglo con id_usuario
- [ID] PRF-UNIT-006: Eliminar usuario → true
- [ID] PRF-UNIT-007: Cambiar estatus → true
- [ID] PRF-UNIT-008: Reporte de roles → lista con nombre_rol, cantidad
- [ID] PRF-UNIT-009: Actualizar perfil (update dinámico) → true
- [ID] PRF-UNIT-010: Listar usuarios por estatus → lista con estatus

- [ID] PRF-FEAT-001: Ingresar usuario (Integración) → true
- [ID] PRF-FEAT-002: Modificar usuario (Integración) → true
- [ID] PRF-FEAT-003: Existencias (Integración) → true/false según caso
- [ID] PRF-FEAT-004: Obtener último usuario (Integración) → arreglo con username
- [ID] PRF-FEAT-005: Obtener usuario por id (Integración) → arreglo con id_usuario
- [ID] PRF-FEAT-006: Eliminar usuario (Integración) → true
- [ID] PRF-FEAT-007: Cambiar estatus (Integración) → true
- [ID] PRF-FEAT-008: Reporte de roles (Integración) → lista
- [ID] PRF-FEAT-009: Actualizar perfil (Integración) → true
- [ID] PRF-FEAT-010: Listar usuarios por estatus (Integración) → lista

---

## Detalles ampliados por módulo

### Catálogo

- [ID] CAT-UNIT-001
  - Título: Insertar combo
  - Precondiciones: Conexión activa
  - Datos: id_producto=1, cantidad=2
  - Pasos: setIdProducto(1), setCantidad(2), insertarCombo()
  - Esperado: true

- [ID] CAT-UNIT-002
  - Título: Obtener productos activos
  - Precondiciones: Productos activos y relaciones con modelo/categoría
  - Datos: N/A
  - Pasos: obtenerProductos()
  - Esperado: lista con columnas id_producto, nombre_producto, nombre_modelo, categoria, stock, precio

- [ID] CAT-UNIT-003
  - Título: Obtener combos agregados
  - Precondiciones: Combos existentes
  - Datos: N/A
  - Pasos: obtenerCombos()
  - Esperado: lista con id_combo, productos (concatenado), precio_total

- [ID] CAT-UNIT-004
  - Título: Eliminar combo
  - Precondiciones: id_combo válido
  - Datos: id_combo=10
  - Pasos: eliminarCombo(10)
  - Esperado: true

- [ID] CAT-UNIT-005
  - Título: Obtener último id de combo
  - Precondiciones: Al menos un combo
  - Datos: N/A
  - Pasos: obtenerUltimoIdCombo()
  - Esperado: entero (p.ej. 15)

- [ID] CAT-UNIT-006
  - Título: Crear nuevo combo
  - Precondiciones: N/A
  - Datos: N/A
  - Pasos: crearNuevoCombo()
  - Esperado: id insertado (string/int)

- [ID] CAT-UNIT-007
  - Título: Insertar producto en combo
  - Precondiciones: Combo existente
  - Datos: id_combo=10, id_producto=1, cantidad=3
  - Pasos: insertarProductoEnCombo(10,1,3)
  - Esperado: true

- [ID] CAT-FEAT-001..007: Igual que Unit, ejercitando flujo completo con stubs de integración

### Orden de Despacho

- [ID] OD-UNIT-001
  - Título: Facturas disponibles
  - Precondiciones: Facturas pagadas sin OD asociada
  - Datos: N/A
  - Pasos: obtenerFacturasDisponibles()
  - Esperado: lista con id_factura, fecha, nombre

- [ID] OD-UNIT-002
  - Título: Obtener orden por ID
  - Precondiciones: Orden existente
  - Datos: id=5
  - Pasos: obtenerOrdenPorId(5)
  - Esperado: arreglo con id_orden_despachos=5

- [ID] OD-UNIT-003
  - Título: Listado de órdenes con productos
  - Precondiciones: Órdenes activas y detalles asociados
  - Datos: N/A
  - Pasos: getordendespacho()
  - Esperado: cada orden incluye productos[] no vacíos

- [ID] OD-UNIT-004
  - Título: Descargar orden por ID
  - Precondiciones: Orden existente
  - Datos: id=9
  - Pasos: DescargarOrdenDespacho(9)
  - Esperado: lista con productos de la factura

- [ID] OD-UNIT-005
  - Título: Detalles de compra por despacho
  - Precondiciones: Detalles existentes
  - Datos: idDespacho=1
  - Pasos: getDetallesCompra(1)
  - Esperado: arreglo con productos

- [ID] OD-UNIT-006
  - Título: Cambiar estado de orden
  - Precondiciones: Orden existente
  - Datos: id=9, estado='Despachado'
  - Pasos: cambiarEstadoOrden(9,'Despachado')
  - Esperado: true

- [ID] OD-UNIT-007
  - Título: Anular orden
  - Precondiciones: Orden existente
  - Datos: id=9
  - Pasos: anularOrdenDespacho(9)
  - Esperado: status=success

- [ID] OD-UNIT-008
  - Título: Cambiar estatus (usuarios)
  - Precondiciones: setId(123)
  - Datos: estatus='Activo'
  - Pasos: cambiarEstatus('Activo')
  - Esperado: true

- [ID] ODF-FEAT-001..008: Igual que Unit, ejercitando join y subconsultas

### Proveedores

- [ID] PRV-FEAT-001
  - Título: Registrar proveedor
  - Precondiciones: N/A
  - Datos: nombre, rif, teléfono, correo, dirección
  - Pasos: registrarproveedor()
  - Esperado: true

- [ID] PRV-FEAT-002
  - Título: Existe nombre proveedor
  - Precondiciones: Proveedor registrado
  - Datos: nombre='X'
  - Pasos: existeNombreProveedor('X')
  - Esperado: true

- [ID] PRV-FEAT-003..012
  - Títulos/Flujos: Último, Por ID, Modificar, Eliminar, Listar, Reporte suministro, Ranking, Comparación precios, Dependencia, Cambiar estatus
  - Precondiciones: Datos según flujo
  - Datos: según flujo (p.ej., id_proveedor, id_producto, estatus)
  - Pasos: invocar método correspondiente
  - Esperado: acorde a cada reporte/acción

### Clientes

- [ID] CLI-FEAT-001
  - Título: Registrar cliente
  - Precondiciones: N/A
  - Datos: nombre, cédula, dirección, teléfono, correo
  - Pasos: ingresarclientes()
  - Esperado: true

- [ID] CLI-FEAT-002
  - Título: Existe número de cédula
  - Precondiciones: Cliente registrado
  - Datos: cedula='V-100'
  - Pasos: existeNumeroCedula('V-100')
  - Esperado: true

- [ID] CLI-FEAT-003..009
  - Títulos/Flujos: Último, Por ID, Modificar, Eliminar lógico, Eliminar, Listar, Listar todos activos
  - Precondiciones/Datos/Pasos: según flujo
  - Esperado: acorde a cada operación

### Modelos

- [ID] MOD-FEAT-001
  - Título: Registrar modelo
  - Precondiciones: Marca válida
  - Datos: id_marca, nombre_modelo
  - Pasos: registrarmodelo()
  - Esperado: true

- [ID] MOD-FEAT-002..010
  - Títulos/Flujos: Existe nombre, Último, Por ID, Combo marcas, Modificar, Eliminar con/sin productos, Modelo con marca, Listar
  - Precondiciones/Datos/Pasos: según flujo
  - Esperado: acorde a cada operación

### Marcas

- [ID] MRK-FEAT-001
  - Título: Registrar marca
  - Precondiciones: N/A
  - Datos: nombre_marca
  - Pasos: registrarmarca()
  - Esperado: true

- [ID] MRK-FEAT-002..008
  - Títulos/Flujos: Existe nombre, Última, Por ID, Modificar, Eliminar, Listar, Verificar modelos asociados
  - Precondiciones/Datos/Pasos: según flujo
  - Esperado: acorde a cada operación

### Perfil (Usuarios)

- [ID] PRF-UNIT-001
  - Título: Ingresar usuario
  - Precondiciones: Conexión de seguridad e inventario disponibles
  - Datos: username, clave, id_rol, correo, nombres, apellidos, telefono, cedula
  - Pasos: set*, ingresarUsuario()
  - Esperado: true y creación de cliente si no existe

- [ID] PRF-UNIT-002..010
  - Títulos/Flujos: Modificar usuario, Existencias, Último, Por ID, Eliminar, Cambiar estatus, Reporte roles, Actualizar perfil, Listar por estatus
  - Precondiciones/Datos/Pasos: según flujo
  - Esperado: acorde a cada operación

- [ID] PRF-FEAT-001..010: Igual que Unit, validando operación con joins/transacciones

### Carrito

- [ID] CRT-UNIT-001
  - Título: Crear carrito
  - Precondiciones: Cliente válido
  - Datos: id_cliente=5
  - Pasos: crearCarrito(5)
  - Esperado: true

- [ID] CRT-UNIT-002
  - Título: Obtener carrito por cliente
  - Precondiciones: Carrito existente
  - Datos: id_cliente=5
  - Pasos: obtenerCarritoPorCliente(5)
  - Esperado: arreglo con id_carrito

- [ID] CRT-UNIT-003
  - Título: Agregar producto (actualiza existente)
  - Precondiciones: Detalle existente
  - Datos: id_carrito=10, id_producto=1, cantidad=3
  - Pasos: agregarProductoAlCarrito(10,1,3)
  - Esperado: true

- [ID] CRT-UNIT-004
  - Título: Agregar producto (inserta nuevo)
  - Precondiciones: Detalle no existe
  - Datos: id_carrito=10, id_producto=2, cantidad=1
  - Pasos: agregarProductoAlCarrito(10,2,1)
  - Esperado: true

- [ID] CRT-UNIT-005
  - Título: Obtener productos del carrito
  - Precondiciones: Carrito con productos
  - Datos: id_carrito=10
  - Pasos: obtenerProductosDelCarrito(10)
  - Esperado: lista con subtotal por item

- [ID] CRT-UNIT-006
  - Título: Actualizar cantidad de producto
  - Datos: id_carrito_detalle=77, cantidad=5
  - Pasos: actualizarCantidadProducto(77,5)
  - Esperado: true

- [ID] CRT-UNIT-007
  - Título: Eliminar producto del carrito
  - Datos: id_carrito_detalle=77
  - Pasos: eliminarProductoDelCarrito(77)
  - Esperado: true

- [ID] CRT-UNIT-008
  - Título: Vaciar carrito
  - Datos: id_carrito=10
  - Pasos: eliminarTodoElCarrito(10)
  - Esperado: true

- [ID] CRT-UNIT-009
  - Título: Agregar combo al carrito (transaccional)
  - Precondiciones: Combo con detalles
  - Datos: id_carrito=10, id_combo=99
  - Pasos: agregarComboAlCarrito(10,99)
  - Esperado: true (commit)

- [ID] CRT-UNIT-010
  - Título: Obtener cantidad productos (conexión 'C')
  - Datos: id_usuario=123
  - Pasos: obtenerCantidadProductosCarrito(123)
  - Esperado: entero (p.ej., 4)

- [ID] CRT-UNIT-011
  - Título: Obtener resumen carrito (conexión 'C')
  - Datos: id_usuario=123
  - Pasos: obtenerResumenCarrito(123)
  - Esperado: arreglo con id_carrito, total_productos, total_precio

- [ID] CRT-UNIT-012
  - Título: Registrar compra (flujo feliz)
  - Precondiciones: Productos válidos con cantidades numéricas
  - Datos: id_carrito=10, id_cliente=5, productos=[{id_producto,cantidad},...]
  - Pasos: registrarCompra(10,5,productos)
  - Esperado: true (commit)

- [ID] CRT-FEAT-001..012: Igual que Unit, ejercitando inserciones, actualizaciones y métricas con stub de conexión 'C'

### Usuarios

- [ID] USR-UNIT-001
  - Título: Ingresar usuario
  - Precondiciones: Conexión de seguridad e inventario disponibles
  - Datos: username, clave, id_rol, correo, nombres, apellidos, telefono, cedula
  - Pasos: set*, ingresarUsuario()
  - Esperado: true y creación de cliente si no existe

- [ID] USR-UNIT-002
  - Título: Modificar usuario
  - Precondiciones: Usuario existente
  - Datos: id_usuario, nuevos datos (username, id_rol, nombres, apellidos, correo, telefono, cedula, [password opcional])
  - Pasos: set*, modificarUsuario(id)
  - Esperado: true y actualización de cliente si aplica

- [ID] USR-UNIT-003
  - Título: Existencias (usuario/cedula/correo)
  - Precondiciones: Datos registrados según caso
  - Datos: username, cedula, correo
  - Pasos: existeUsuario(), existeCedula(), existeCorreo()
  - Esperado: true/false según caso

- [ID] USR-UNIT-004
  - Título: Obtener último usuario
  - Precondiciones: Al menos un usuario
  - Datos: N/A
  - Pasos: obtenerUltimoUsuario()
  - Esperado: arreglo con username e id_rol/nombre_rol

- [ID] USR-UNIT-005
  - Título: Obtener usuario por id
  - Precondiciones: Usuario existente
  - Datos: id_usuario
  - Pasos: obtenerUsuarioPorId(id)
  - Esperado: arreglo con id_usuario y nombre_rol

- [ID] USR-UNIT-006
  - Título: Eliminar usuario
  - Precondiciones: Usuario existente
  - Datos: id_usuario
  - Pasos: eliminarUsuario(id)
  - Esperado: true

- [ID] USR-UNIT-007
  - Título: Cambiar estatus
  - Precondiciones: setId(id_usuario)
  - Datos: estatus ('habilitado'/'deshabilitado')
  - Pasos: cambiarEstatus(estatus)
  - Esperado: true

- [ID] USR-UNIT-008
  - Título: Reporte de roles
  - Precondiciones: Roles/usuarios registrados
  - Datos: N/A
  - Pasos: obtenerReporteRoles()
  - Esperado: lista con nombre_rol y cantidad

- [ID] USR-UNIT-009
  - Título: Actualizar perfil (update dinámico)
  - Precondiciones: Usuario existente
  - Datos: id_usuario, subset de campos (p.ej., nombres, telefono, [password hashable], correo opcional vacío no actualiza)
  - Pasos: actualizarPerfil(id, datos)
  - Esperado: true

- [ID] USR-UNIT-010
  - Título: Listar usuarios por estatus
  - Precondiciones: Usuarios con estatus
  - Datos: estatus='habilitado'
  - Pasos: getusuarios('habilitado')
  - Esperado: lista con estatus

- [ID] USR-FEAT-001..010: Igual que Unit, validando joins/transacciones y sincronización con clientes

### Rol

- [ID] ROL-UNIT-001
  - Título: Registrar rol (con permisos)
  - Precondiciones: Módulos disponibles, inserción de permisos por módulo/acción
  - Datos: nombre_rol='Nuevo Rol'
  - Pasos: setNombreRol, registrarRol()
  - Esperado: true y N inserts en permisos (p.ej., 18)

- [ID] ROL-UNIT-002
  - Título: Existe nombre de rol
  - Precondiciones: Rol existente
  - Datos: nombre_rol='Operador'
  - Pasos: existeNombreRol('Operador')
  - Esperado: true

- [ID] ROL-UNIT-003
  - Título: Obtener último rol
  - Precondiciones: Al menos un rol
  - Pasos: obtenerUltimoRol()
  - Esperado: arreglo con nombre_rol

- [ID] ROL-UNIT-004
  - Título: Obtener rol por id
  - Datos: id_rol=5
  - Pasos: obtenerRolPorId(5)
  - Esperado: arreglo con id_rol=5

- [ID] ROL-UNIT-005
  - Título: Consultar roles
  - Pasos: consultarRoles()
  - Esperado: lista con id_rol, nombre_rol

- [ID] ROL-UNIT-006
  - Título: Modificar rol
  - Datos: id_rol=3, nombre nuevo
  - Pasos: setNombreRol, modificarRol(3)
  - Esperado: true

- [ID] ROL-UNIT-007
  - Título: Eliminar rol
  - Datos: id_rol=3
  - Pasos: eliminarRol(3)
  - Esperado: true

- [ID] ROL-UNIT-008
  - Título: Tiene usuarios asignados
  - Datos: id_rol=3
  - Pasos: tieneUsuariosAsignados(3)
  - Esperado: false (según stub)

- [ID] ROL-FEAT-001..008: Igual que Unit, validando inserción de permisos y consultas

### Ventas Presenciales (Compra Física)

- [ID] VP-FEAT-001
  - Título: Registrar y consultar compra
  - Precondiciones: Productos válidos, cliente válido
  - Datos: detalle productos, métodos de pago
  - Pasos: registrar compra (comprafisica), luego g_Compras()
  - Esperado: status=success y compra listada

- [ID] VP-FEAT-002
  - Título: Compra con múltiples pagos
  - Precondiciones: Métodos de pago múltiples soportados
  - Datos: lista de pagos (efectivo, punto, transferencia, etc.)
  - Pasos: registrar compra con múltiples pagos
  - Esperado: status=success y pagos reflejados

- [ID] VP-FEAT-003
  - Título: Compra inválida (rollback)
  - Precondiciones: Forzar error en detalle/validación
  - Datos: detalle inválido
  - Pasos: intentar registrar
  - Esperado: status=error y rollback

- [ID] VP-FEAT-004
  - Título: Campos vacíos (cliente requerido)
  - Precondiciones: Cliente vacío/no válido
  - Datos: cliente=null
  - Pasos: intentar registrar
  - Esperado: status=error

---

## Módulo Productos (Unit)

- [ID] PRD-UNIT-001
  - Título: CRUD básico de producto
  - Precondiciones: Tablas `tbl_categoria` y `tbl_modelos` con al menos 1 registro; transacciones habilitadas
  - Datos: código único, nombre, descripción, id_modelo, stock(10), stock_min(1), stock_max(100), garantía(12m), precio(9.99)
  - Pasos: `ingresarProducto()` → `obtenerProductoPorId()` → `modificarProducto()` → `obtenerProductoStock()` → `eliminarProducto()`
  - Esperado: id numérico; lectura ok; `modificarProducto()` true; listado es arreglo; `eliminarProducto()` retorna `['success'=>true] 

---

## Módulo Pagos – Pasarela (Integración)

- [ID] PGS-FEAT-001
  - Título: Acción no válida en controlador
  - Precondiciones: Controlador `Controlador/PasareladePago.php` accesible; ejecución por CLI permitida
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal que hace require del controlador con `$_POST` inyectado
  - Esperado: salida decodificable a arreglo (manejo robusto de acciones no válidas)

---

## Módulo Notificaciones (Integración)

- [ID] NTN-FEAT-001
  - Título: Esquema mínimo SQLite en memoria para notificaciones
  - Precondiciones: Extensión `pdo_sqlite` disponible; `modelo/notificacion.php` accesible
  - Datos: Esquema mínimo (`tbl_rol`, `tbl_usuarios`, `tbl_permisos`) y seed básico
  - Pasos: inicializar PDO `sqlite::memory:` → crearEsquema() → seedBasico() → invocar funciones del modelo
  - Esperado: asserts del test pasan sin excepciones

---

## Módulo Backup (Integración)

- [ID] BCK-FEAT-001
  - Título: Generar y listar backups
  - Precondiciones: Carpeta `db/backup/` con permisos de escritura
  - Datos: nombre archivo `int_test.sql`
  - Pasos: `generar('int_test.sql')` → crear archivo manual `manual.sql` → `listar()`
  - Esperado: `listar()` retorna arreglo que contiene `manual.sql` y, si `generar()` tuvo éxito, también `int_test.sql` con tamaño > 0

- [ID] BCK-FEAT-002
  - Título: Restaurar archivo inexistente
  - Datos: `no-existe.sql`
  - Pasos: `restaurar('no-existe.sql')`
  - Esperado: `false`

- [ID] BCK-FEAT-003
  - Título: Restaurar archivo vacío no rompe
  - Datos: archivo vacío `vacio.sql`
  - Pasos: `restaurar('vacio.sql')`
  - Esperado: retorno booleano sin excepciones

---

## Módulo Bitácora (Integración)

- [ID] BTC-FEAT-001
  - Título: Acción no válida en controlador de bitácora
  - Precondiciones: `Controlador/bitacora.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar controlador con `$_POST` inyectado
  - Esperado: estructura decodificable (arreglo)

---

## Módulo Productos (Integración)

- [ID] PRD-FEAT-001
  - Título: Acción no válida en controlador de productos
  - Precondiciones: `Controlador/producto.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar controlador con `$_POST` inyectado
  - Esperado: `status='error'` y `message` contiene 'Acción no válida'

---

## Módulo Permisos (Integración)

- [ID] PER-FEAT-001
  - Título: Acción no válida en controlador de permisos
  - Precondiciones: `Controlador/permiso.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar controlador con `$_POST` inyectado
  - Esperado: estructura decodificable (arreglo)

---

## Módulos adicionales (Integración)

### Categoría

- [ID] CAT-FEAT-INT-001
  - Título: Acción no válida en controlador de categoría
  - Archivo: `tests/Integration/Categoria/CategoriaControllerTest.php`
  - Precondiciones: `Controlador/categoria.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal que hace require del controlador con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo) o `status='error'` con mensaje indicativo

### Cuenta

- [ID] CUE-FEAT-INT-001
  - Título: Acción no válida en controlador de cuenta
  - Archivo: `tests/Integration/Cuenta/CuentaControllerTest.php`
  - Precondiciones: `Controlador/cuenta.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)

### Despacho

- [ID] DSP-FEAT-INT-001
  - Título: Acción no válida en controlador de despacho
  - Archivo: `tests/Integration/Despacho/DespachoControllerTest.php`
  - Precondiciones: `Controlador/ordendespacho.php` (o equivalente) accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)

### Finanza

- [ID] FNZ-FEAT-INT-001
  - Título: Acción no válida en controlador de finanza
  - Archivo: `tests/Integration/Finanza/FinanzaControllerTest.php`
  - Precondiciones: `Controlador/finanza.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)

### Pedidos / Factura

- [ID] FCT-FEAT-INT-001
  - Título: Acción no válida en controlador de factura/pedidos
  - Archivo: `tests/Integration/Pedidos/FacturaControllerTest.php`
  - Precondiciones: `Controlador/factura.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)

### Recepción

- [ID] RCP-FEAT-INT-001
  - Título: Acción no válida en controlador de recepción
  - Archivo: `tests/Integration/Recepcion/RecepcionControllerTest.php`
  - Precondiciones: `Controlador/recepcion.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)

### Marcas (Integración)

- [ID] MRK-FEAT-INT-001
  - Título: Acción no válida en controlador de marca
  - Archivo: `tests/Integration/MarcaControllerTest.php`
  - Precondiciones: `Controlador/marca.php` accesible por CLI
  - Datos: POST `{ accion: 'desconocida' }`
  - Pasos: ejecutar script temporal con `$_POST` inyectado
  - Esperado: respuesta decodificable (arreglo)
