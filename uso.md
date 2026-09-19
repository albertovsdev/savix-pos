# SAVIX POS — instalación local desde GitHub

Esta guía instala una copia nueva de SAVIX POS en una computadora con Windows. Está pensada para usar XAMPP, mantener la información de cada negocio en su propia base de datos y abrir el sistema desde la misma computadora o desde dispositivos de la red local.

> La copia desde GitHub incluye el código, pero no incluye datos de negocio, usuarios, archivos `.env`, dependencias ni la base de datos de otra computadora.

## 1. Requisitos

Instala o verifica lo siguiente:

- Windows 10 u 11.
- [Git para Windows](https://git-scm.com/download/win).
- XAMPP con PHP 8.2 o superior y MySQL/MariaDB.
- [Composer 2](https://getcomposer.org/download/).
- [Node.js 20 LTS o superior](https://nodejs.org/).

En el panel de XAMPP inicia **MySQL**. Apache no es obligatorio para esta primera instalación, porque el sistema se ejecutará con el servidor de Laravel.

Comprueba las herramientas desde PowerShell:

```powershell
git --version
C:\xampp\php\php.exe -v
composer --version
node --version
npm --version
```

Si Composer no encuentra PHP, agrega `C:\xampp\php` a la variable de entorno `Path` de Windows y abre una nueva ventana de PowerShell.

## 2. Descargar el proyecto

Abre PowerShell y ejecuta:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/albertovsdev/savix-pos.git
cd savix-pos
```

Para actualizar una copia que ya existe, no vuelvas a clonar. Usa la sección [Actualizaciones](#8-actualizaciones-desde-github).

## 3. Crear la base de datos vacía

Con MySQL iniciado en XAMPP:

1. Abre `http://localhost/phpmyadmin`.
2. Selecciona **Nueva**.
3. Crea la base de datos con el nombre `savix_pos`.
4. Elige la intercalación `utf8mb4_unicode_ci` o `utf8mb4_general_ci`.

No importes una base de datos de otro negocio: cada instalación de SAVIX POS debe iniciar con su propia base de datos.

## 4. Configurar el archivo `.env`

Desde `C:\xampp\htdocs\savix-pos`:

```powershell
Copy-Item .env.example .env
notepad .env
```

En `.env`, verifica al menos estas variables. Ajusta usuario y contraseña si tu MySQL no usa la configuración predeterminada de XAMPP:

```dotenv
APP_NAME="SAVIX POS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=savix_pos
DB_USERNAME=root
DB_PASSWORD=
```

Nunca subas `.env` a GitHub: contiene la configuración privada de esa computadora.

## 5. Instalar dependencias y preparar la base de datos

En la carpeta del proyecto ejecuta, en este orden:

```powershell
composer install
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan db:seed --force
npm ci
npm run build
```

Las migraciones crean las tablas. El comando `db:seed` carga los roles, permisos y módulos requeridos por la configuración inicial. Es seguro ejecutarlo de nuevo durante una actualización porque los catálogos se actualizan sin duplicarse.

Como alternativa, después de crear y configurar `.env`, puedes ejecutar:

```powershell
composer run setup
```

## 6. Iniciar SAVIX POS

Cada vez que vayas a usar el sistema, abre PowerShell en la carpeta del proyecto y ejecuta:

```powershell
cd C:\xampp\htdocs\savix-pos
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Abre en el navegador:

```text
http://127.0.0.1:8000
```

La primera vez aparecerá la configuración inicial. Captura el negocio, la sucursal matriz y el administrador. Conserva en un lugar seguro el usuario, contraseña y PIN de cuatro dígitos.

Para detener el servidor, vuelve a PowerShell y presiona `Ctrl + C`.

## 7. Acceso desde celulares o tablets en la red local

Esto sirve para pruebas dentro de la misma red Wi-Fi; no publica el sistema en Internet.

1. Verifica que la computadora y los dispositivos estén en la misma red.
2. Ejecuta el servidor escuchando en la red local:

   ```powershell
   C:\xampp\php\php.exe artisan serve --host=0.0.0.0 --port=8000
   ```

3. Ejecuta `ipconfig` y ubica la dirección IPv4 de la red activa, por ejemplo `192.168.1.25`.
4. En el celular o tablet abre `http://192.168.1.25:8000` reemplazando la dirección de ejemplo.
5. Si Windows pregunta por permisos de red para PHP, permite únicamente redes privadas.

Para este modo cambia también `APP_URL` en `.env` por la dirección local elegida y ejecuta:

```powershell
C:\xampp\php\php.exe artisan config:clear
```

No abras el puerto 8000 hacia Internet ni uses esta modalidad como servidor público.

## 8. Actualizaciones desde GitHub

Primero realiza una copia de seguridad de la base de datos desde phpMyAdmin: selecciona `savix_pos`, entra a **Exportar**, usa formato SQL y guarda el archivo en un lugar seguro.

Después, con el servidor detenido, ejecuta:

```powershell
cd C:\xampp\htdocs\savix-pos
git status --short
git pull origin main
composer install
npm ci
npm run build
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan db:seed --force
C:\xampp\php\php.exe artisan optimize:clear
```

Si `git status --short` muestra archivos, detente antes de hacer `git pull`: hay cambios locales que primero debes revisar o respaldar.

No uses `migrate:fresh`, `migrate:reset` ni `migrate:rollback` en una instalación que ya tenga información real, porque pueden eliminar datos.

## 9. Diagnóstico rápido

| Situación | Acción recomendada |
| --- | --- |
| El navegador muestra conexión rechazada | Confirma que `artisan serve` sigue abierto y usa exactamente `http://127.0.0.1:8000`. |
| Error de conexión a MySQL | Inicia MySQL desde XAMPP y revisa `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`. |
| Falta una tabla | Ejecuta `C:\xampp\php\php.exe artisan migrate --force`. |
| No aparece la configuración inicial | Ejecuta `C:\xampp\php\php.exe artisan db:seed --force` y verifica que la base `savix_pos` esté vacía de negocios. |
| No cargan estilos después de actualizar | Ejecuta `npm run build` y `C:\xampp\php\php.exe artisan optimize:clear`. |
| No abre desde el celular | Confirma que ambos equipos estén en la misma Wi-Fi, usa la IPv4 correcta y permite PHP en redes privadas. |

## 10. Estado funcional actual

La instalación actual permite configurar negocio, sucursales, usuarios, permisos, catálogo, insumos, recetas, extras y modificadores. También permite abrir pedidos de mesa o mostrador, crear rondas independientes y enviarlas a sus áreas de preparación configuradas.

La pantalla de cocina/barra, cobros, tickets definitivos e inventario operativo continúan en desarrollo; no deben considerarse listos para una operación comercial todavía.
