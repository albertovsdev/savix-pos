# Tareas de implementación

## Estado sincronizado al 2026-10-07

| Etapa | Estado | Entregado |
| --- | --- | --- |
| Fase 0 | Completada | Decisiones de producto, piloto MEDEL, stack y modelo por instalación. |
| Fase 1 | Completada | Configuración inicial, acceso, PIN, roles, permisos, auditoría, negocio, sucursales y ticket. |
| Fase 2 | Completada | Catálogo, disponibilidad por sucursal, insumos, recetas, extras y modificadores. |
| Fase 3 | Completada | Mesas, mostrador, pedidos, borradores, rondas y preparación. |
| Fase 4 | Completada | Cancelaciones autorizadas, merma, devolución de inventario y estados de preparación. |
| Fase 5 | Implementada en núcleo | Subcuentas, pagos mixtos, cierre, tickets e impresión. Pendientes: descuentos, reapertura y corte. |
| Fase 6 | Implementada en núcleo | Inventario, ajustes, proveedores, compras y reportes básicos. Pendientes: consolidado, exportación y piloto manual completo. |
| Fase 7 | Pendiente | Diseño premium, controles de caja y reportes avanzados. |
| Fase 8 | Pendiente | PWA, trabajo sin conexión y sincronización. |

## Próxima tarea recomendada

Realizar una pasada de diseño premium transversal con revisión de contraste, jerarquía, tamaños táctiles, navegación móvil, listados, formularios y estados vacíos. No modificar las reglas de venta, inventario, cobro ni permisos durante esta tarea sin pruebas adicionales.

## Pendientes funcionales posteriores

- Edición y baja lógica de proveedores.
- Cancelación/reversión de compras con movimientos inversos de inventario.
- Descuentos autorizados, reapertura de venta, movimientos y corte de caja.
- Reportes de mermas/auditoría, consolidado de sucursales y exportación.
- Importación masiva con plantilla/mapeo por negocio.
- PWA, cola local y sincronización idempotente.

## Estado actual

- La sección histórica siguiente queda conservada como referencia. El estado vigente es la tabla superior.

## Reglas de ejecución

- Revisar `git status --short` antes de una fase.
- Aplicar cambios pequeños y verificables.
- Documentar decisiones que afecten datos, permisos o inventario.
- Ejecutar pruebas de aceptación de la fase antes de continuar.
