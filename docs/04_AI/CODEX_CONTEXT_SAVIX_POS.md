# Contexto de Codex — SAVIX POS

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
