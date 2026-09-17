# Plan de implementación — SAVIX POS

## Propósito

Construir un punto de venta web configurable por negocio y escalable por sucursales. El sistema debe cubrir operación de mostrador y restaurante, preparación por áreas, inventario unitario o por receta, pagos divididos y personalización de identidad y tickets.

## Decisiones confirmadas

- Una instalación corresponde a un solo negocio. No habrá cambio entre negocios desde una misma cuenta operativa.
- Un negocio puede tener varias sucursales.
- El catálogo nace global para el negocio y cada sucursal puede activar o desactivar productos, categorías o sabores sin eliminar su historial.
- Cada sucursal configura sus propias áreas de preparación. Solo se muestran las áreas activas para esa sucursal.
- Se incluirá el rol `dev`, con acceso total para soporte y administración técnica.
- Los permisos se asignarán dentro del sistema por rol y también de forma granular por usuario cuando sea necesario.
- La identidad corporativa SAVIX será la base visual inicial. Cada negocio podrá sustituir logo y colores de su instancia sin modificar la marca corporativa SAVIX.
- La primera estrategia de impresión será la impresión normal del navegador hacia una impresora térmica instalada o emparejada en el dispositivo. La impresión directa por Bluetooth se evaluará después.

## Modelo funcional de alto nivel

```text
Negocio
└── Sucursales
    ├── Áreas de preparación
    ├── Cajas y dispositivos
    ├── Inventario
    └── Catálogo disponible

Pedido
├── Rondas
├── Productos y modificadores
├── Áreas de preparación
├── Subcuentas
├── Pagos
└── Movimientos de inventario
```

## Fase 0 — Descubrimiento y base técnica

### Alcance

- Definir el nombre comercial del producto, URL inicial y texto de marca.
- Confirmar `database_language`. La recomendación técnica es `english` para el esquema y código, manteniendo interfaz y documentación en español; esto reduce fricción con Laravel. Si se elige español, se respetarán `usuarios`, `ref_sucursal`, `creado_en` y demás convenciones SAVIX de forma consistente.
- Preparar diagrama de entidades y estados de pedido antes de crear migraciones.
- Definir formatos de ticket: 58 mm y 80 mm.
- Confirmar una impresora térmica de prueba y el dispositivo inicial: computadora, Android o iPad.
- Registrar límites reales del hosting: versión PHP, MySQL, SSH/Composer, Cron, SSL, espacio, respaldos y límites de proceso.

### Pruebas de aceptación

- Documento de decisiones aprobado.
- Modelo de datos y estados validados con casos de restaurante y mostrador.
- Proyecto local inicia y muestra una pantalla de salud.

## Fase 1 — Fundación, acceso y configuración del negocio

### Alcance

- Crear aplicación Laravel, entorno local, `.env.example`, migraciones y política de respaldo.
- Implementar inicio de sesión, recuperación de acceso y cierre de sesión.
- Crear negocio, sucursales, usuarios, roles y permisos.
- Implementar rol `dev` y auditoría de acciones sensibles.
- Configurar nombre, logo, ticket, moneda, impuestos y colores base por negocio.
- Crear sistema de tokens visuales `--savix-pos-*` que traduzca la configuración de colores del negocio a toda la interfaz.

### Pruebas de aceptación

- Un administrador crea una sucursal y un usuario mesero.
- Un mesero no puede abrir configuración, inventario ni reportes si no tiene permiso.
- Un `dev` puede consultar y administrar todos los módulos autorizados.
- Al cambiar el color primario y el logo, la interfaz y vista previa del ticket se actualizan sin valores de color hardcodeados.

## Fase 2 — Catálogo, disponibilidad y modificadores

### Alcance

- Categorías, productos, precios, imágenes opcionales y áreas de preparación.
- Catálogo global del negocio con disponibilidad por sucursal.
- Productos sin inventario, por unidad o por receta.
- Ingredientes, unidades de medida, recetas, extras y modificadores.
- Modificadores que eliminan ingredientes, agregan ingredientes o solo informan una nota de preparación.

### Pruebas de aceptación

- Una sucursal desactiva helados sin afectar la sucursal principal.
- Una cemita “sin aguacate” no consume aguacate.
- “Extra quesillo” aumenta el precio y el consumo configurado.
- Un producto solo llega a las áreas activas que le correspondan.

## Fase 3 — Venta, mesas y rondas

### Alcance

- Ventas de mostrador y mesas.
- Apertura de mesa, pedido en borrador y confirmación antes de envío.
- Rondas independientes dentro de una misma cuenta.
- Reasignación de mesa, notas y trazabilidad de cambios.
- Vista de venta optimizada para tablet.

### Pruebas de aceptación

- Una primera ronda enviada no reaparece al enviar una segunda ronda.
- La mesa permanece abierta y conserva el total acumulado.
- Un pedido de mostrador puede cobrar sin usar mesas.
- Toda modificación deja usuario, fecha y motivo cuando aplique.

## Fase 4 — Preparación e inventario

### Alcance

- Pantallas por área: cocina, barra y áreas personalizadas.
- Estados de preparación: pendiente, en preparación, listo, entregado y cancelado.
- Libro de movimientos de inventario: entrada, consumo, reversión, merma, ajuste y traspaso futuro.
- Cancelación con decisión “no preparado” o “preparado / merma”.

### Pruebas de aceptación

- Cocina y barra reciben únicamente su parte de la ronda nueva.
- Un producto cancelado antes de prepararse devuelve el inventario consumido.
- Un producto ya preparado genera merma y no devuelve existencias.
- El historial explica cada cambio de inventario.

## Fase 5 — Subcuentas, pagos, tickets y caja

### Alcance

- Subcuentas por mesa: Parte 1, Parte 2, Parte 3 o nombres de comensales.
- Asignación de productos, cantidades y artículos compartidos a subcuentas.
- Cobros en efectivo, tarjeta y combinados.
- Descuentos y autorizaciones basadas en permisos.
- Ticket global y ticket individual por subcuenta.
- Apertura, movimientos y corte de caja.

### Pruebas de aceptación

- Una mesa puede cobrar tres subcuentas sin duplicar productos ni inventario.
- Un artículo compartido se reparte correctamente.
- Un pago combinado calcula saldo y cambio correctamente.
- La impresión desde el navegador produce un ticket legible en 58 mm y 80 mm.

## Fase 6 — Reportes, control de sucursales y piloto

### Alcance

- Ventas por periodo, sucursal, usuario, área y producto.
- Productos vendidos, mermas, existencias y movimientos de inventario.
- Auditoría de descuentos, cancelaciones, reaperturas y ajustes.
- Panel de disponibilidad de catálogo por sucursal.
- Prueba con un negocio piloto y datos de ejemplo realistas.

### Pruebas de aceptación

- Los reportes de una sucursal no mezclan ventas o inventario de otra.
- Un usuario sin permiso no ve información sensible.
- Flujo completo probado: pedido, dos rondas, preparación, cancelación, división de cuenta, pago e impresión.

## Fase 7 — PWA, sincronización y expansión

### Alcance

- Instalable como PWA en tablet y escritorio.
- Caché de interfaz y catálogo.
- Cola local de operaciones para conectividad intermitente.
- Sincronización, prevención de duplicados y resolución de conflictos.
- Preparación para múltiples sucursales con consulta consolidada.

### Pruebas de aceptación

- El sistema abre con la interfaz disponible sin conexión.
- Las operaciones permitidas se encolan y se sincronizan una sola vez al recuperar conectividad.
- No se duplican ventas, pagos ni movimientos de inventario.

## Criterio de avance

No se inicia una fase hasta que las pruebas de aceptación de la anterior pasen y queden registradas. Las decisiones que alteren el modelo de datos se documentan antes de generar migraciones.
