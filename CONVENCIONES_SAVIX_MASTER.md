# CONVENCIONES MAESTRAS — SAVIX INDUSTRIES

**Documento:** CONVENCIONES_SAVIX_MASTER  
**Empresa:** SAVIX INDUSTRIES  
**Uso:** Proyectos web, móviles, backend, base de datos, documentación técnica y trabajo asistido con IA.  
**Idioma de documentación:** Español  
**Estado:** Base maestra adaptable por proyecto  
**Actualización importante:** Las convenciones de base de datos se rigen por el idioma predominante del proyecto. Para Phil's Drills, el idioma técnico predominante es inglés.

---

## 1. Propósito

Este documento define las convenciones maestras de SAVIX INDUSTRIES para mantener consistencia técnica, visual y documental en proyectos web, móviles, backend y bases de datos.

Debe usarse como referencia antes de crear modelos de datos, esquemas SQL, APIs, estructuras de carpetas, componentes visuales, tokens de diseño, variables CSS, código PHP, código TypeScript / React Native, documentación para IA y prompts de implementación.

Regla principal:

> Cada proyecto puede tener identidad propia, pero debe respetar las convenciones de ingeniería, orden, trazabilidad y calidad de SAVIX INDUSTRIES.

---

## 2. Principios generales

### 2.1 Claridad antes que complejidad

No crear capas, carpetas, abstracciones o patrones que el proyecto todavía no necesita.

Correcto:

```txt
src/features/challenges/
src/features/attempts/
src/shared/theme/
```

Incorrecto si el proyecto no requiere esa complejidad:

```txt
src/core/domain/application/services/interfaces/repositories/
```

### 2.2 Modularidad por dominio

La lógica específica debe vivir cerca del dominio que la usa.

Ejemplo en app móvil:

```txt
src/features/attempts/
  api/
  components/
  screens/
  types/
```

No enviar lógica de intentos a carpetas globales como `src/utils`, `src/services` o `src/types`, salvo que realmente sea compartida por varios dominios.

### 2.3 Separación entre producto y firma corporativa

SAVIX puede actuar como firma técnica, sistema de documentación, convención de arquitectura, créditos discretos, tokens corporativos reutilizables y estándar de calidad.

SAVIX no debe invadir la identidad visual del producto del cliente.

Ejemplo:

- `phildrills.tokens.ts` gobierna la identidad de Phil's Drills.
- `savix.tokens.ts` gobierna identidad corporativa SAVIX.
- `semantic.tokens.ts` decide qué usa cada pantalla.

### 2.4 Nada sensible sin protección

Nunca versionar directamente:

- `.env`
- credenciales
- llaves
- certificados
- contratos
- NDAs
- documentos legales privados
- videos reales de menores
- archivos personales sensibles
- documentos internos con datos legales si el repositorio se compartirá

Usar `.gitignore` desde el inicio.

---

## 3. Convenciones de base de datos

Estas reglas aplican para MySQL, MariaDB o PostgreSQL salvo que el proyecto indique otra cosa.

### 3.1 Idioma de la base de datos

La base de datos debe estar en el **idioma predominante del proyecto**.

Regla:

- Si el proyecto está dominado por español, la base de datos va en español.
- Si el proyecto está dominado por inglés, la base de datos va en inglés.
- No mezclar español e inglés dentro del mismo esquema salvo que exista una justificación documentada.
- La documentación puede estar en español aunque el esquema esté en inglés.
- El código TypeScript / React Native puede estar en inglés, y eso puede justificar que la base de datos también esté en inglés.

Para **Phil's Drills**, el idioma predominante del producto, código y dominios es **inglés**, por lo tanto la base de datos debe usar nombres en inglés.

Equivalencias válidas según idioma del proyecto:

```sql
usuarios              / users
participantes         / participants
retos                 / challenges
intentos              / attempts
sesiones_video        / video_sessions
retroalimentaciones   / feedback
configuraciones       / configurations
```

Incorrecto:

```sql
usuarios
participants
retos
attempts
```

porque mezcla idiomas dentro del mismo esquema.

### 3.2 Nombres de tablas

- Plural.
- `snake_case`.
- En el idioma predominante del proyecto.
- Sin abreviaciones innecesarias.
- Nombre de negocio, no nombre técnico accidental.

Correcto en proyecto español:

```sql
usuarios
participantes
retos
intentos
sesiones_video
retroalimentaciones
configuraciones
```

Correcto en proyecto inglés:

```sql
users
participants
challenges
attempts
video_sessions
feedback
configurations
```

Incorrecto:

```sql
user
tbl_users
cat_attempts
ses_vid
data
```

Notas:

- `feedback` puede funcionar como nombre de tabla en inglés aunque sea sustantivo no contable, porque representa una colección de registros de retroalimentación.
- Si se prefiere evitar ambigüedad, también es válido `feedback_entries`, pero debe decidirse una vez y mantenerse consistente.

### 3.3 Nombres de columnas

- Singular.
- `snake_case`.
- En el idioma predominante del proyecto.
- Descriptivas.
- Sin abreviaciones ambiguas.

Correcto en proyecto español:

```sql
nombre
correo
contrasena_hash
duracion_segundos
puntaje
modo_puntuacion
estado_analisis
creado_en
actualizado_en
```

Correcto en proyecto inglés:

```sql
name
email
password_hash
duration_seconds
score
scoring_mode
analysis_status
created_at
updated_at
```

Incorrecto:

```sql
nom_com
apell
pwd
dur
crea_at
upd_at
```

### 3.4 Llaves primarias

Formato obligatorio:

```txt
id_entidad
```

La entidad debe ir en el idioma predominante del proyecto.

Correcto en proyecto español:

```sql
id_usuario
id_participante
id_reto
id_intento
id_sesion_video
id_retroalimentacion
```

Correcto en proyecto inglés:

```sql
id_user
id_participant
id_challenge
id_attempt
id_video_session
id_feedback
```

No usar:

```sql
id
usuario_id
participant_id
challenge_id
```

### 3.5 Llaves foráneas

Formato obligatorio:

```txt
ref_entidad_relacionada
```

La referencia se nombra por el concepto relacionado en singular y en el idioma predominante del proyecto.

Correcto en proyecto español:

```sql
ref_usuario
ref_participante
ref_reto
ref_intento
ref_instructor
```

Correcto en proyecto inglés:

```sql
ref_user
ref_participant
ref_challenge
ref_attempt
ref_instructor
```

No usar:

```sql
usuario_id
participant_id
challenge_id
fk_user
```

### 3.6 Campos obligatorios en todas las tablas

Toda tabla operativa debe incluir los campos obligatorios en el idioma del proyecto.

Proyecto español:

```sql
activo          TINYINT(1) NOT NULL DEFAULT 1,
creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

Proyecto inglés:

```sql
active      TINYINT(1) NOT NULL DEFAULT 1,
created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

Regla:

- `activo` / `active`: baja lógica.
- `creado_en` / `created_at`: fecha de creación.
- `actualizado_en` / `updated_at`: última modificación.

No borrar físicamente registros importantes salvo que el proyecto lo exija por privacidad, retención legal o cumplimiento normativo.

### 3.7 Catálogos y estados

Para estados simples puede usarse `ENUM` en MySQL si el sistema es pequeño y controlado.

Correcto en proyecto español:

```sql
modo_puntuacion ENUM(
  'manual',
  'asistido',
  'automatico_experimental',
  'automatico_validado'
) NOT NULL DEFAULT 'manual'
```

Correcto en proyecto inglés:

```sql
scoring_mode ENUM(
  'manual',
  'assisted',
  'automatic_experimental',
  'automatic_validated'
) NOT NULL DEFAULT 'manual'
```

En sistemas que crecerán, preferir catálogos.

Proyecto español:

```sql
cat_modos_puntuacion
cat_estados_analisis
```

Proyecto inglés:

```sql
cat_scoring_modes
cat_analysis_statuses
```

### 3.8 Tablas catálogo

Usar prefijo `cat_` solo para catálogos reales.

Correcto en proyecto español:

```sql
cat_roles
cat_estados_analisis
cat_modos_puntuacion
```

Correcto en proyecto inglés:

```sql
cat_roles
cat_analysis_statuses
cat_scoring_modes
```

No usar `cat_` para tablas operativas.

Incorrecto:

```sql
cat_users
cat_attempts
cat_usuarios
cat_intentos
```

### 3.9 Tablas pivote

Para relaciones muchos-a-muchos, usar nombres claros de ambas entidades en plural o concepto de relación.

Correcto en proyecto español:

```sql
participantes_grupos
usuarios_roles
retos_programas
```

Correcto en proyecto inglés:

```sql
participants_groups
users_roles
challenges_programs
```

Llave primaria recomendada:

```sql
id_participant_group
```

o en español:

```sql
id_participante_grupo
```

Foráneas en proyecto inglés:

```sql
ref_participant
ref_group
```

Foráneas en proyecto español:

```sql
ref_participante
ref_grupo
```

### 3.10 Comentarios SQL

Usar comentarios para campos delicados o no obvios.

Correcto en proyecto español:

```sql
uri_video VARCHAR(500) DEFAULT NULL COMMENT 'Ruta local o remota del video si existe';
```

Correcto en proyecto inglés:

```sql
video_uri VARCHAR(500) DEFAULT NULL COMMENT 'Local or remote video path if it exists';
```

No comentar lo obvio.

### 3.11 Regla de consistencia obligatoria

Antes de crear migraciones, definir en documentación:

```txt
database_language: english | spanish
```

Para Phil's Drills:

```txt
database_language: english
```

Por lo tanto, cualquier modelo de datos de Phil's Drills debe usar:

```txt
users
participants
challenges
attempts
video_sessions
feedback
configurations
active
created_at
updated_at
```

y no:

```txt
usuarios
participantes
retos
intentos
sesiones_video
retroalimentaciones
configuraciones
activo
creado_en
actualizado_en
```

---

## 4. Modelo de datos recomendado para Phil's Drills

Este ejemplo corrige el modelo preliminar para respetar las convenciones SAVIX con idioma predominante **inglés**.

> Nota: No convertir este esquema directamente en migración productiva sin validar privacidad, consentimiento, backend y alcance real del MVP.

### 4.1 `users`

Adultos, instructores, administradores o cuentas de acceso. No implica necesariamente que un menor tenga cuenta propia.

```sql
CREATE TABLE users (
  id_user          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(120) NOT NULL,
  email            VARCHAR(191) DEFAULT NULL UNIQUE,
  password_hash    VARCHAR(255) DEFAULT NULL,
  role             ENUM('participant', 'instructor', 'admin') NOT NULL DEFAULT 'participant',
  active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 4.2 `participants`

Niños, jóvenes o usuarios que realizan el reto. Puede existir aunque no tenga cuenta propia.

```sql
CREATE TABLE participants (
  id_participant    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_instructor    INT UNSIGNED DEFAULT NULL,
  name              VARCHAR(120) NOT NULL,
  age               TINYINT UNSIGNED DEFAULT NULL,
  guardian_name     VARCHAR(120) DEFAULT NULL,
  active            TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ref_instructor) REFERENCES users(id_user)
);
```

### 4.3 `challenges`

Retos disponibles. El MVP inicia con un reto principal de 20 segundos.

```sql
CREATE TABLE challenges (
  id_challenge       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title              VARCHAR(160) NOT NULL,
  description        TEXT,
  duration_seconds   SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  challenge_type     VARCHAR(80) NOT NULL DEFAULT 'catch_20s',
  is_daily           TINYINT(1) NOT NULL DEFAULT 0,
  active_date        DATE DEFAULT NULL,
  active             TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 4.4 `attempts`

Cada intento realizado por un participante.

```sql
CREATE TABLE attempts (
  id_attempt         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_participant    INT UNSIGNED NOT NULL,
  ref_challenge      INT UNSIGNED NOT NULL,
  score              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  duration_seconds   SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  scoring_mode       ENUM(
    'manual',
    'assisted',
    'automatic_experimental',
    'automatic_validated'
  ) NOT NULL DEFAULT 'manual',
  notes              TEXT,
  active             TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ref_participant) REFERENCES participants(id_participant),
  FOREIGN KEY (ref_challenge) REFERENCES challenges(id_challenge)
);
```

### 4.5 `video_sessions`

Información técnica de video asociada a un intento.

```sql
CREATE TABLE video_sessions (
  id_video_session       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_attempt            INT UNSIGNED NOT NULL,
  local_uri              VARCHAR(500) DEFAULT NULL,
  remote_uri             VARCHAR(500) DEFAULT NULL,
  analysis_status        ENUM(
    'not_requested',
    'pending',
    'processing',
    'completed',
    'failed',
    'not_viable'
  ) NOT NULL DEFAULT 'not_requested',
  analysis_result_json   JSON DEFAULT NULL,
  retain_until           DATETIME DEFAULT NULL COMMENT 'Estimated deletion date if a retention policy exists',
  active                 TINYINT(1) NOT NULL DEFAULT 1,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ref_attempt) REFERENCES attempts(id_attempt)
);
```

### 4.6 `feedback`

Comentarios o evaluación cualitativa.

```sql
CREATE TABLE feedback (
  id_feedback       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_user          INT UNSIGNED DEFAULT NULL,
  ref_participant   INT UNSIGNED DEFAULT NULL,
  comment           TEXT NOT NULL,
  rating            TINYINT UNSIGNED DEFAULT NULL,
  active            TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ref_user) REFERENCES users(id_user),
  FOREIGN KEY (ref_participant) REFERENCES participants(id_participant)
);
```

### 4.7 Relaciones iniciales

```txt
users 1 -> many participants
participants 1 -> many attempts
challenges 1 -> many attempts
attempts 1 -> 0..1 video_sessions
users 1 -> many feedback
participants 1 -> many feedback
```

### 4.8 Fuera del MVP inicial

No agregar todavía:

- Payments.
- Licenses.
- Schools.
- Institutional groups.
- Public rankings.
- Advanced legal audit.
- Advanced video retention.
- Definitive consent models.
- AI model training tables.

Estas entidades solo deben agregarse cuando el discovery confirme necesidad real.

---

## 5. Convenciones para proyectos web HTML/CSS/JS/PHP

### 5.1 CSS variables

Todas las variables CSS deben usar prefijo:

```txt
--savix-*
```

Estructura:

```txt
--savix-{categoria}-{variante}
```

Correcto:

```css
--savix-color-primary
--savix-color-primary-light
--savix-bg-card
--savix-text-primary
--savix-border-primary
--savix-shadow-md
--savix-radius-lg
--savix-font-display
--savix-ease-out
--savix-duration-normal
--savix-space-4
```

Incorrecto:

```css
--color-primary
--primary
--card-bg
--radius
```

### 5.2 CSS por tokens

Los estilos deben consumir tokens, no valores hardcodeados.

Correcto:

```css
.card {
  background: var(--savix-bg-card);
  border: 1px solid var(--savix-border-primary);
  border-radius: var(--savix-radius-lg);
  padding: var(--savix-space-5);
}
```

Incorrecto:

```css
.card {
  background: white;
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 24px;
}
```

### 5.3 Naming de clases CSS

Usar BEM simplificado con prefijo de producto o `savix` para componentes corporativos.

Ejemplos:

```css
.savix-credit
.savix-credit__separator
.savix-btn
.savix-btn--primary

.phildrills-card
.phildrills-card__title
.phildrills-card--active
```

### 5.4 PHP

Variables de infraestructura y servicios globales usan prefijo:

```php
$savix_db
$savix_config
$savix_session
$savix_user
```

Variables locales de negocio usan `snake_case` sin prefijo:

```php
$participants
$current_attempt
$file_name
$token
```

Funciones globales de infraestructura usan prefijo:

```php
savix_db()
savix_get_config()
savix_auth_check()
savix_redirect()
savix_escape()
```

Constantes PHP usan prefijo:

```php
SAVIX_APP_URL
SAVIX_UPLOAD_DIR
SAVIX_SESSION_NAME
SAVIX_BRAND_NAME
SAVIX_BRAND_URL
SAVIX_APP_OWNER
```

### 5.5 Seguridad web mínima

Aplicar siempre:

- PDO + prepared statements.
- `htmlspecialchars()` o helper `savix_escape()`.
- `.env` fuera de Git.
- CSRF token en formularios de mutación.
- Validación de permisos antes de servir archivos.
- Protección de carpetas sensibles con `.htaccess` cuando aplique.
- No exponer rutas reales del servidor.
- No guardar contraseñas sin `password_hash()`.

---

## 6. Convenciones para React Native / Expo / TypeScript

### 6.1 Idioma del código

En React Native y TypeScript se recomienda usar inglés para carpetas, archivos, componentes, tipos, funciones, hooks, stores y servicios.

Ejemplos:

```txt
participants
challenges
attempts
video
instructor
admin
```

La documentación y copy visible pueden estar en español o inglés según el proyecto.

### 6.2 Estructura base

```txt
src/
  application/
    bootstrap/
    navigation/
    providers/
  features/
    auth/
    participants/
    challenges/
    attempts/
    video/
    instructor/
    admin/
  shared/
    api/
    components/
      ui/
      savix/
    config/
    theme/
    utils/
  assets/
    fonts/
    images/
    sounds/
```

### 6.3 Reglas de carpetas

No crear carpetas globales legacy por costumbre:

```txt
src/components/
src/hooks/
src/store/
src/types/
src/theme/
src/utils/
src/navigation/
```

Crear esas carpetas solo si existe una razón técnica confirmada. Preferir:

```txt
src/shared/components/ui/
src/shared/theme/
src/shared/utils/
src/application/navigation/
src/features/<domain>/
```

### 6.4 Componentes

Componentes en `PascalCase`:

```txt
ChallengeScreen.tsx
AttemptResultCard.tsx
VideoCaptureScreen.tsx
SavixCredit.tsx
```

Props:

```ts
type AttemptResultCardProps = {
  score: number;
  durationSeconds: number;
};
```

### 6.5 Hooks

Hooks con prefijo `use`:

```ts
useAttemptTimer.ts
useVideoCapture.ts
useDemoParticipants.ts
```

Si el hook es de un dominio, vive en el dominio:

```txt
src/features/attempts/hooks/useAttemptTimer.ts
```

### 6.6 Tipos

Tipos e interfaces en `PascalCase`:

```ts
type UserRole = 'participant' | 'instructor' | 'admin';

type ScoringMode =
  | 'manual'
  | 'assisted'
  | 'automatic_experimental'
  | 'automatic_validated';
```

Los tipos deben vivir cerca del dominio:

```txt
src/features/attempts/types/index.ts
```

### 6.7 Constantes

Constantes globales en `UPPER_SNAKE_CASE` si representan configuración estable:

```ts
export const SAVIX_BRAND = {
  name: 'SAVIX INDUSTRIES',
  slogan: 'Save. Solve. Scale.',
} as const;
```

Constantes de dominio pueden agruparse por objeto:

```ts
export const challengeDefaults = {
  durationSeconds: 20,
} as const;
```

### 6.8 Tokens visuales en React Native

En React Native no usar variables CSS `--savix-*`. Usar objetos TypeScript.

Correcto:

```ts
export const savix = {
  color: {
    violet: '#6C63FF',
    onyx: '#0A0A0A',
  },
  space: {
    1: 4,
    2: 8,
    3: 12,
    4: 16,
  },
} as const;
```

Para producto:

```ts
export const phildrills = {
  color: {
    primary: '#18ECEE',
    dark: '#040404',
  },
} as const;
```

Tokens semánticos:

```ts
export const semantic = {
  bg: {
    primary: phildrills.color.dark,
    card: '#101418',
  },
  text: {
    primary: '#FFFFFF',
    secondary: 'rgba(255,255,255,0.72)',
  },
  accent: {
    primary: phildrills.color.primary,
    corporate: savix.color.violet,
  },
} as const;
```

### 6.9 Estilos React Native

Evitar hardcodear colores, espacios y radios dentro de pantallas cuando ya existen tokens.

Correcto:

```ts
const styles = StyleSheet.create({
  card: {
    backgroundColor: theme.bg.card,
    borderRadius: theme.radius.lg,
    padding: theme.space[5],
  },
});
```

Incorrecto:

```ts
const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 24,
  },
});
```

### 6.10 Expo / EAS

Todo proyecto móvil serio debe tener:

```txt
app.json o app.config.ts
eas.json
package.json
tsconfig.json
```

Perfiles recomendados en `eas.json`:

```json
{
  "cli": {
    "version": ">= 15.0.0"
  },
  "build": {
    "development": {
      "developmentClient": true,
      "distribution": "internal"
    },
    "preview": {
      "distribution": "internal"
    },
    "preview-apk": {
      "android": {
        "buildType": "apk"
      },
      "distribution": "internal"
    },
    "production": {
      "autoIncrement": true
    }
  },
  "submit": {
    "production": {}
  }
}
```

Regla:

- Expo Go sirve para demo temprana sin módulos nativos complejos.
- Development build se usa cuando se requieren configuraciones nativas, cámara avanzada, permisos, módulos no soportados o pruebas más cercanas a producción.
- EAS no debe introducirse como excusa para construir features grandes antes de validar el MVP.

---

## 7. Convenciones de API

### 7.1 Rutas

Para apps móviles con frontend TypeScript, las rutas pueden estar en inglés si el código está en inglés:

```txt
/auth/login
/participants
/challenges
/attempts
/video-sessions
```

Para sistemas PHP web internos en español, las rutas pueden estar en español:

```txt
acciones/crear_participante.php
acciones/guardar_intento.php
```

La decisión debe documentarse por proyecto.

### 7.2 Payloads

Idealmente, los payloads del API deben coincidir con el lenguaje técnico del frontend si el frontend es TypeScript en inglés.

Ejemplo mobile:

```ts
type AttemptPayload = {
  participantId: number;
  challengeId: number;
  score: number;
  durationSeconds: number;
  scoringMode: ScoringMode;
};
```

El backend traduce hacia base de datos.

Para proyecto inglés:

```txt
participantId -> ref_participant
challengeId -> ref_challenge
score -> score
durationSeconds -> duration_seconds
scoringMode -> scoring_mode
```

Para proyecto español:

```txt
participantId -> ref_participante
challengeId -> ref_reto
score -> puntaje
durationSeconds -> duracion_segundos
scoringMode -> modo_puntuacion
```

### 7.3 Endpoints centralizados

En React Native, centralizar rutas en:

```txt
src/shared/api/endpoints.ts
```

APIs por dominio:

```txt
src/features/attempts/api/attemptsApi.ts
```

No llamar `fetch` o `axios` directamente desde todas las pantallas.

---

## 8. Git y commits

### 8.1 `.gitignore`

Debe existir desde el inicio.

Ignorar siempre:

```gitignore
node_modules/
.expo/
dist/
web-build/
android/
ios/
.env
.env.*
!.env.example
keys/
*.p8
*.p12
*.pem
*.key
*.mobileprovision
*.cer
tmp/
temp/
*.tmp
```

Para proyectos con documentación sensible:

```gitignore
assets/savix/documentacion/
assets/docs/*.pdf
assets/docs/**/*.pdf
assets/fonts/*.zip
```

### 8.2 Commits

Usar mensajes claros:

```txt
chore: initial project structure
docs: define MVP scope
docs: add master conventions
feat: add demo challenge flow
fix: correct timer reset behavior
refactor: move scoring logic to attempts domain
```

### 8.3 Antes de cada fase

Ejecutar:

```bash
git status --short
```

Hacer commit antes de cambios grandes.

---

## 9. Verificaciones mínimas

### 9.1 TypeScript

Todo proyecto TypeScript debe tener:

```json
{
  "scripts": {
    "typecheck": "tsc --noEmit --pretty false"
  }
}
```

Ejecutar:

```bash
npm run typecheck
```

### 9.2 Expo

Verificaciones iniciales:

```bash
npm install
npm run typecheck
npx expo start
```

### 9.3 Antes de aceptar cambios de Codex

Revisar:

- Archivos creados.
- Archivos modificados.
- Archivos eliminados.
- Resultado de verificaciones.
- Si tocó assets sensibles.
- Si introdujo dependencias innecesarias.
- Si respetó estructura por features.
- Si respetó convenciones de base de datos.

---

## 10. Formato obligatorio para respuestas de Codex

Codex debe cerrar cada respuesta así:

```md
## Resumen de cambios

### Archivos creados
- `ruta/archivo.ext` — descripción breve

### Archivos modificados
- `ruta/archivo.ext` — descripción breve

### Archivos movidos
- `ruta/origen` → `ruta/destino` — motivo
- Si no se movieron archivos: No se movieron archivos.

### Archivos eliminados
- `ruta/archivo.ext` — motivo
- Si no se eliminaron archivos: No se eliminaron archivos.

### Qué se corrigió o implementó
- Punto 1
- Punto 2
- Punto 3

### Verificaciones realizadas
- `comando ejecutado` — resultado real

### Riesgos o pendientes
- Pendiente 1
- Pendiente 2

### Siguiente paso recomendado
- Una acción concreta y pequeña para continuar.
```

No debe repetir instrucciones plantilla como si fueran resultado.

---

## 11. Reglas de uso de IA

Cuando se trabaje con IA:

1. Primero leer contexto.
2. No programar sin entender alcance.
3. No inventar requisitos.
4. No borrar archivos sensibles.
5. No mover assets sin confirmación.
6. No instalar paquetes sin justificar.
7. No crear arquitectura excesiva.
8. No truncar archivos.
9. No ocultar verificaciones fallidas.
10. Reportar riesgos y pendientes.

Para proyectos con IA, mantener:

```txt
docs/04_AI/
  CODEX_CONTEXT_*.md
  implementation_tasks.md
  codex_prompts.md
  decisions_log.md
```

---

## 12. Regla final

> SAVIX INDUSTRIES debe producir software limpio, documentado, escalable y presentable, sin perder control del alcance ni comprometer calidad por velocidad.
