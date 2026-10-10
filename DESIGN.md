# Diseño de SAVIX POS

## Escena de uso
Un cajero revisa y opera ventas en una laptop bajo luz intensa; un mesero consulta el pedido desde un teléfono en movimiento; cocina observa estados desde un dispositivo fijo. La interfaz debe ser clara en tema claro y oscuro, con controles grandes y jerarquía estable.

## Estrategia de color
Producto de color restringido. SAVIX es la identidad inicial, pero cada negocio reemplaza la paleta de marca mediante tokens semánticos.

## Tokens corporativos base
- Onyx: #0A0A0A
- Deep: #1A1A2E
- Slate: #2D2D3A
- Violet: #6C63FF
- Violet soft: #9993DD
- Bone: #F5F4F0
- Light: #EDEDED

## Reglas
- Variables CSS con prefijo --savix-pos-.
- El color de marca solo señala acción primaria, selección y foco.
- Tipografía de interfaz: sistema sans, con jerarquía compacta.
- Radio de controles: 10px a 14px.
- Estados semánticos obligatorios: éxito, advertencia, error, información y deshabilitado.
- Navegación predecible, formularios sin modales innecesarios y layouts responsivos estructurales.

## Estado actual de la interfaz

La base visual está aplicada en panel, administración, catálogo, operación, preparación, caja, inventario, compras y reportes. El negocio puede definir colores, tema y logo; el ticket usa una ruta relativa para que el logo funcione en localhost, red local y hosting.

Los modales se usan para autorizaciones y confirmaciones sensibles. La siguiente mejora visual es una pasada transversal premium: tipografía, contraste, densidad de información, navegación móvil, consistencia de botones/listados y estados vacíos. No se deben alterar flujos funcionales durante esa pasada sin pruebas.
