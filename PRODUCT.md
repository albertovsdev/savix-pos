# SAVIX POS — Contexto de producto

## Propósito

SAVIX POS es un punto de venta web configurable para restaurantes, bares, negocios de alimentos y mostrador. Cada instalación opera un único negocio y conserva sus propios datos, usuarios, identidad e inventario.

## Usuarios y operación

- Meseros: abren mesas, capturan productos, agregan modificadores y envían rondas.
- Cocina y barra: reciben solo los productos de su área y actualizan el estado de preparación.
- Caja: divide cuentas, registra pagos mixtos, cierra ventas e imprime tickets.
- Inventario: consulta existencias, registra ajustes y compras.
- Administración: configura negocio, sucursales, usuarios, permisos, catálogo y ticket.
- Dev: soporte técnico con acceso total autorizado.

## Flujo principal

```text
Mesa o mostrador → productos → personalizaciones → confirmar ronda
→ cocina/barra → nuevas rondas si aplica → dividir cuenta si aplica
→ cobrar → ticket → cierre de venta
```

## Reglas de producto

- Cada ronda enviada es inmutable para preparación; una ronda posterior solo contiene productos nuevos.
- Un pedido permanece abierto hasta que todas sus partes de cuenta estén pagadas.
- Los modificadores pueden retirar ingredientes, agregar insumos o dejar una nota de preparación.
- El inventario se descuenta al enviar la ronda: por unidad, por receta o ambos.
- Una cancelación sin preparación devuelve existencias; una cancelación ya preparada registra merma.
- Los precios son importes finales en MXN; el IVA se desglosa para ticket y compras según la configuración del negocio.
- Las acciones sensibles requieren permiso, motivo y, cuando corresponde, PIN de un autorizador.

## Principios de experiencia

- La operación diaria debe ser inmediata, táctil y legible bajo presión.
- La configuración compleja se concentra fuera de la venta diaria.
- Los estados importantes deben ser inequívocos: borrador, enviado, en preparación, listo, cobrado y cancelado.
- La identidad del negocio debe sustituir visualmente la base SAVIX sin romper el sistema de diseño.

## Alcance actual

La operación de venta, preparación, caja, tickets, inventario, proveedores, compras y reportes básicos está implementada. El siguiente enfoque es pulir el diseño y completar controles administrativos y reportes avanzados antes de preparar PWA y sincronización sin conexión.
