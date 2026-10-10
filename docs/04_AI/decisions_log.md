# Registro de decisiones — SAVIX POS

## 2026-09-11 — Alcance inicial del producto

- Una instalación corresponde a un único negocio.
- Se soportarán varias sucursales del mismo negocio.
- El catálogo será global, con activación y desactivación por sucursal.
- Las áreas de preparación son configurables por sucursal.
- Se incorpora el rol `dev` con acceso total.
- Los permisos deben poder asignarse desde el sistema.
- La capa visual corporativa toma SAVIX como base; cada negocio personaliza su propia instancia.
- La primera integración de impresión será mediante el diálogo de impresión del sistema operativo o navegador; la impresión Bluetooth directa queda fuera del MVP.

## 2026-09-15 — Decisiones cerradas de Fase 0 y Fase 1

- El nombre de trabajo del producto es `SAVIX POS`; URL objetivo inicial: `savixindustries.com/savix-pos`.
- El proyecto, código de negocio, rutas, documentación y esquema de datos se escriben en español.
- Cada instalación usa la misma plantilla de base de datos y una conexión propia por negocio.
- El piloto es Restaurante-Bar MEDEL: una sucursal matriz, siete mesas, caja, cocina y barra.
- La arquitectura inicial es Laravel 12, MySQL/MariaDB, Inertia y Vue 3. Se deja PWA y operación sin conexión para una fase posterior.
- Moneda: MXN. Precios de compra y venta se almacenan como importes finales, con IVA del 16 % desglosable.
- El acceso diario usa nombre de usuario y contraseña. Las acciones sensibles requieren permiso, motivo y PIN numérico de cuatro dígitos del usuario autorizador.
- Roles iniciales: dev, admin, gerente, caja, mesero, cocina, barra e inventario.
- Los folios de venta usarán una clave por sucursal, con formato objetivo `SX-SM-0001`.
- El catálogo se captura manualmente en el MVP. La importación masiva se resolverá después con plantilla y mapeo.

## 2026-09-21 a 2026-10-07 — Decisiones de operación, inventario y compras

- El pedido se compone de rondas: una ronda confirmada llega una sola vez a las áreas de preparación y las posteriores solo llevan productos nuevos.
- Cocina, barra y cualquier área de preparación son configurables por sucursal; los usuarios de preparación pueden limitarse a áreas asignadas.
- El inventario se descuenta al confirmar la ronda, no al cobrar. Los tipos admitidos son sin control, unidad, receta y mixto.
- Un modificador que elimina ingrediente evita su consumo; uno que agrega ingrediente aumenta el consumo y puede aumentar precio.
- La cancelación requiere permiso, motivo y PIN de autorización. Si no se preparó, se revierten las salidas de inventario; si se preparó, se registra merma.
- Las subcuentas pueden nombrarse y recibir productos individuales. Las partes sin productos se cierran automáticamente al liquidar las partes con consumo.
- Los pagos aceptados en el MVP son efectivo, tarjeta o combinación exacta de ambos. El ticket se emite al cierre total del pedido.
- El ticket se imprime mediante navegador y se adapta a 58 mm u 80 mm. La URL del logo debe ser relativa a `/storage` para funcionar en localhost, IP de red y producción.
- Los ajustes manuales de inventario requieren existencia final y motivo; generan movimiento firmado y bitácora.
- Proveedores pertenecen al negocio; las compras pertenecen a una sucursal y pueden no tener proveedor. Cada renglón de compra incrementa existencias y genera `entrada_compra`.
- Los costos de compra se capturan como importes finales; el sistema almacena subtotal e IVA desglosados conforme a la configuración del negocio.
- Los reportes iniciales son por sucursal y periodo: ventas cerradas, pagos, ticket promedio, compras, productos vendidos y alertas de mínimos.
- La cancelación de compras, edición/baja de proveedores, corte de caja, descuentos, reaperturas, consolidado y PWA permanecen fuera del alcance implementado.
