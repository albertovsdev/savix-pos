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
