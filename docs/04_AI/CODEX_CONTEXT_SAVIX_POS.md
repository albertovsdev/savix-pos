# Contexto de Codex — SAVIX POS

## Estado sincronizado al 2026-10-07

### Base técnica

- Proyecto en `C:\proyectos\savix-pos`.
- Laravel 12, PHP 8.2, MySQL/MariaDB, Vue 3, Inertia y Vite.
- Base de datos y negocio en español. Usar `id_entidad`, `ref_entidad`, `activo`, `creado_en` y `actualizado_en`.
- Una instalación equivale a un negocio; el negocio puede tener varias sucursales.
- La base local ya ejecutó las migraciones hasta `2026_09_30_000004_crear_proveedores_compras_y_permisos`.
- No ejecutar `migrate:fresh`, `migrate:reset`, `migrate:rollback` ni comandos destructivos en una base con datos.

### Funcionalidad implementada

- Configuración inicial, autenticación, PIN, roles, permisos, bitácora, negocio, sucursales e identidad/ticket.
- Catálogo global por sucursal, áreas de preparación, productos, insumos, recetas, extras y modificadores.
- Mesas, mostrador, borradores, rondas, notas, preparación por área y cierre de venta.
- Cancelaciones autorizadas, devolución de inventario y merma.
- División de cuenta, pagos efectivo/tarjeta combinados y ticket de navegador de 58 mm/80 mm.
- Inventario por unidad, receta y mixto; ajustes y movimientos por venta, cancelación y compra.
- Proveedores, compras por sucursal, entradas de inventario y reportes básicos por sucursal/periodo.

### Reglas de negocio importantes

- Se descuenta inventario al confirmar la ronda.
- Una cancelación no preparada devuelve exactamente los movimientos de salida; una preparada conserva la salida y genera merma.
- Los precios son finales en MXN. IVA se desglosa usando la configuración del negocio.
- El logo de ticket debe usar ruta relativa `/storage/...`; no usar URL absoluta basada en `APP_URL`.
- Compras aceptan proveedor opcional y solo recursos controlables: productos `unidad`/`mixto` o insumos.
- Las compras aún no se cancelan desde interfaz; no implementar reversión sin crear movimientos inversos y auditoría.
- Reportes actuales son por sucursal, no consolidados.

### Estado de Git y verificación

- `HEAD` y `origin/main` están en `1adbdda docs(instalacion): agrega guia para equipo local`.
- Las fases posteriores están implementadas localmente y aún no están agrupadas en commits funcionales.
- Hay cambios no confirmados en controladores, páginas Vue, migraciones, pruebas, seeders, rutas, tickets y documentación. No descartar ni restaurar cambios ajenos.
- Última verificación completa: `php artisan test` aprobó 25 pruebas y 339 verificaciones; `npm run build` aprobó.

### Próximo orden recomendado

1. Ejecutar y documentar una pasada de diseño premium transversal sin alterar contratos funcionales.
2. Implementar proveedores editables/baja lógica y cancelación reversible de compras con entrada/salida auditada.
3. Completar controles de caja: descuentos autorizados, reapertura, movimientos y corte.
4. Expandir reportes: consolidado de sucursales, mermas, auditoría y exportaciones.
5. Solo después: PWA, caché y sincronización sin conexión.

### Disciplina de trabajo

- Leer `CONVENCIONES_SAVIX_MASTER.md`, `PRODUCT.md`, `DESIGN.md`, `docs/01_PRODUCTO/PLAN_IMPLEMENTACION.md` y este archivo antes de cambios relevantes.
- Revisar `git status --short` antes de una fase y no mezclar cambios no relacionados.
- Para bugs, usar diagnóstico sistemático y agregar una prueba de regresión.
- Para cambios de datos, crear migraciones incrementales, aplicar `php artisan migrate --force` únicamente cuando esté verificado y nunca borrar datos reales.
- Verificar con `C:\xampp\php\php.exe artisan test`, `npm run build` y `git diff --check`.

- Idioma de documentación, copy visible, código de negocio, rutas y base de datos: español.
- Base de datos: MySQL/MariaDB; tablas operativas en plural, llaves `id_entidad`, referencias `ref_entidad`, y auditoría temporal `creado_en` / `actualizado_en`, conforme a `CONVENCIONES_SAVIX_MASTER.md`.
- Arquitectura objetivo: Laravel 12, MySQL/MariaDB, Vue 3 con Inertia y Vite. PWA progresiva, después del MVP operativo.
- No introducir procesos persistentes, Redis, WebSockets ni paquetes no justificados en el MVP.
- Todos los cambios respetan `CONVENCIONES_SAVIX_MASTER.md`.
- Tokens corporativos SAVIX: `--savix-*`.
- Tokens de producto POS: `--savix-pos-*`.
- La personalización del negocio debe afectar tokens semánticos, no CSS hardcodeado.
- No incluir credenciales, `.env`, archivos sensibles ni datos reales en Git.
- Cada instalación representa un negocio. Las sucursales pertenecen a ese negocio y el catálogo global puede habilitarse o deshabilitarse por sucursal.
- Las acciones sensibles exigen permiso, motivo y PIN de autorización; deben quedar en bitácora.
