<p align="center">
  <img src="https://raw.githubusercontent.com/EdwinB1025/tazzavera-api/DEV/public/favicon.svg" width="120">
</p>

  <p align="center"><strong><em style="font-size: 24px;">TAZAVERA API</em></strong></p>


Backend REST de **Tazavera**, la plataforma de verificación de café de especialidad. Esta API sirve el modelo de datos, la autenticación OAuth2/PKCE, y el motor de **consenso** que promedia y contrasta las evaluaciones de los especialistas sobre cada café que ofrece una cafetería de especialidad.

Proyecto académico (bootcamp) con vocación de producto real. Construido en **Laravel 13 + Passport + MySQL 8**, consumido por un front separado ([`tazavera-app`](https://github.com/EdwinB1025/tazavera-app), monolito Livewire).

> Este repositorio es **solo la API**. El front (Livewire, D3, Leaflet, rueda de sabores) vive en un repositorio aparte.

## 🕵️ El problema que ataca

Hay mucho café "specialty" que en realidad no lo es, y el consumidor de a pie no tiene cómo saberlo — la calificación queda en manos de pocos, y eso sesga el mercado. Tazavera no se conforma con un rating más: **verifica** lo que una cafetería dice vender. Si declara "notas frutales, acidez alta, proceso natural", varios especialistas catan el producto real y el sistema deriva un consenso que confirma o desmiente esa afirmación.

El sistema de evaluación se basa en el **SCA Coffee Value Assessment (CVA v2, standard provisional 2024-2025)**, adaptando el método de cupping formal a condiciones reales de cafetería (café ya preparado, sin réplica estricta del protocolo de tazas físicas).

## 🧪 Modelo de evaluación (resumen)

- 🔬 **Descriptive (especialista)** — registro objetivo de intensidad. Siete ejes (fragancia, aroma, flavor, aftertaste, acidez, dulzor, mouthfeel) en escala 0-15, más descriptores CATA de una taxonomía jerárquica y Main Tastes.
- ⭐ **Affective (especialista)** — el veredicto de calidad, ocho ejes (los siete + overall) en escala 1-9. Deriva un **cupping score 0-100** por evaluación y alimenta el **consenso agregado** a nivel de offering.
- 👤 **Consumer** — reacción simple homologada a la escala 1-9. Rol previsto en el ENUM; su flujo propio queda en backlog.

Especialista y consumidor **no se promedian entre sí** — se contrastan atributo por atributo; ahí vive el valor de inteligencia de mercado.

### El consenso (motor de esta API)

Cuando una offering alcanza **≥5 evaluaciones cerradas de especialista**, cerrar una evaluación dispara —vía evento y un **worker de cola**— el recálculo del consenso (`OfferingConsensusService::recompute`):

- **Promedios por eje** (`*_avg`) y **cupping score agregado** (fórmula CVA `0.65625·Σ + 52.75 − 2u − 4d`, redondeo a 0.25).
- **Concordancia inter-especialista** por parte descriptive/affective (índice de dispersión normalizada, `1 − σ/σ_max`), con detalle por eje en `axis_concordances`.
- **Árbol de sabores del consenso** (`offering_tastes`): descriptores agrupados, cada uno con cuántos especialistas distintos lo marcaron.
- **`verification_status`**: `provisional` → `verified` al alcanzar el umbral.

El detalle de fórmulas, umbral, y la reinterpretación de las deducciones `u` (no-uniformidad, derivada de la dispersión inter-especialista) y `d` (defectos) está en la documentación de diseño.

## 📚 Documentación

To be updated.

> 🔄 Ante una discrepancia entre docs y código, **el código es la fuente de verdad**.

## 🛠️ Stack

- 🐘 **Framework:** Laravel 13, PHP 8.5
- 🔐 **Auth:** Laravel Passport 13 (OAuth2 + PKCE) + Fortify (sesión web para el flujo de autorización)
- 🛡️ **Roles/permisos:** Spatie Laravel-Permission (guard `api`)
- 🗄️ **Base de datos:** MySQL 8 (dev/prod) · SQLite `:memory:` (tests)
- ⚙️ **Colas:** driver `database` (worker de consenso); `sync` en tests
- 🧪 **Tests:** Pest (TDD)

## ✅ Requisitos previos

- PHP 8.5+ con las extensiones estándar de Laravel + **`ext-sodium`** (requerida por Passport en runtime — ver `deployment-notes.md`)
- Composer 2.x
- **MySQL 8** corriendo y accesible
- Node.js 18+ y npm (solo si se compilan las vistas mínimas de OAuth)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/EdwinB1025/tazzavera-api.git
cd tazzavera-api
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` para apuntar a MySQL 8:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tazavera
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

Crear la base de datos vacía antes de migrar:

```bash
mysql -u root -p -e "CREATE DATABASE tazavera;"
```

### 4. Instalar Passport (OAuth2)

```bash
php artisan install:api --passport
php artisan passport:keys
```

> ⚠️ Si `install:api --passport` falla revirtiendo `composer.json`, casi seguro es `ext-sodium` deshabilitado — habilitar `extension=sodium` en `php.ini` y reintentar (detalle en `deployment-notes.md`).

Crear un cliente público (PKCE) para el front:

```bash
php artisan passport:client --public
```

### 5. Migrar y poblar

```bash
php artisan migrate --seed
```

🌱 El seeder puebla la taxonomía olfativa (desde CSV, fuente WCR Sensory Lexicon), catálogo de cafés, tostadores, inventario, y datos base para probar el flujo de punta a punta.

## ▶️ Levantar el proyecto

### Servidor

```bash
php artisan serve
```

La API queda en `http://localhost:8000`.

### Worker de consenso

En dev, para procesar el recálculo de consenso en background:

```bash
php artisan queue:work
```

> Alternativamente, `QUEUE_CONNECTION=sync` en `.env` corre los jobs inline sin worker (más simple para desarrollar; se pierde el desacople). En producción el worker se mantiene vivo con Supervisor / Docker `restart: always` — ver `deployment-notes.md`.

### Tests

```bash
php artisan test
```

Corren sobre SQLite `:memory:` con `QUEUE_CONNECTION=sync`, así que el consenso se ejecuta inline sin necesidad de worker.

## 🔑 Autenticación (OAuth2 / PKCE)

Login en tres pasos:

1. `POST /login` — crea la sesión web (Fortify); devuelve `{"two_factor": false}`, **sin** token.
2. `GET /oauth/authorize` — con esa sesión + parámetros PKCE, devuelve el `code` (302, consentimiento omitido para el cliente first-party).
3. `POST /oauth/token` — intercambia `code` + `code_verifier` por `access_token` + `refresh_token`.

El `access_token` autentica las rutas `auth:api` vía `Authorization: Bearer`. Dos scopes: `profile:read` (por defecto) y `profile:write` (step-up para acciones sensibles). Detalle completo en `endpoints.md`.

## 📊 Estado de implementación

**API completa.** Todos los endpoints del MVP están construidos y cubiertos por tests Pest:

| Pieza | Estado |
|---|---|
| Auth OAuth2 + PKCE (Passport) | ✅ End-to-end (Postman + Pest) |
| Scopes + step-up + rechazo de wildcard | ✅ Implementado |
| CRUD de usuarios (soft + hard delete) | ✅ Implementado |
| Offerings (batch create, delete single/batch, index filtrado, show) | ✅ Implementado |
| Evaluations (create, update, close, delete, index filtrado, show) | ✅ Implementado |
| Cupping score individual (0-100, 8 ejes reales) | ✅ Implementado |
| Consenso agregado (worker por evento al **cerrar**) | ✅ Implementado |
| Concordancia inter-especialista (dispersión normalizada) | ✅ Implementada (columnas + `axis_concordances`) |
| Deducciones `−4d` (defectos) y `−2u` (no-uniformidad) en cupping | ✅ Implementadas |
| Árbol de sabores del consenso (`offering_tastes`) | ✅ Poblado (padres + hojas, count = especialistas distintos) |
| `verification_status` (provisional → verified) | ✅ Implementado |

## 🗺️ Backlog

Lo que **deliberadamente se dejó fuera del MVP** para mantener el alcance manejable — funcionalidad de producto futura, no deuda técnica.

- 🖥️ **Front / interfaz de usuario** — esta API se consume hoy desde el monolito `tazavera-app`; un front dedicado que consuma esta REST (SPA o móvil) está pendiente de desarrollar.
- 👤 **Evaluación consumer** — el rol existe en el ENUM, pero su formulario, validación y estructura propia (CATA restringido a los niveles superiores de la taxonomía) quedan por definir; probablemente una entidad separada.
- 🏪 **Baseline de la cafetería** — endpoint dedicado para la evaluación provisional que la cafetería declara sobre su propia offering (una por offering, con ownership y unicidad). Diseñado, no construido.
- 🔀 **Consenso segregado por método de extracción** — hoy el consenso mezcla espresso, V60, prensa francesa, etc. Separar el cálculo por método cuando haya volumen suficiente para no perder muestra.
- ⏱️ **Cronjob de cierre automático de evaluaciones** — cerrar en masa evaluaciones abiertas más de cierto tiempo (Laravel scheduler), en vez de depender del cierre manual. Distinto del worker reactivo de consenso.
- 🎯 **Tag Q de calibración en acidez** — cruzar el tag con la certificación del evaluador para detectar si los especialistas Q-certificados concuerdan más entre sí en los descriptores de acidez.
- 🧾 **Marketplace transaccional** — órdenes, pagos y comunicación con la cafetería. El MVP es directorio + verificación, no venta.
- 💸 **Payout automatizado** a cafeterías (settlement programado).
- 🏅 **Panel de fidelización para cafeterías** — suscripciones, puntos.
- 📈 **Reportes de tendencias de mercado** — valor ponderado por atributo según segmento de consumidor.
- 🎓 **Verificación de especialista — subsistema completo** — banco de preguntas, auto-validación y combinación de certificación Q Grader + exposición + calificación comunitaria. El MVP se queda con la declaración mínima.
- ☕ **Tercer lado del mercado — especialistas monetizados** — consultoría, recetas, workshops de cata.
- 🚚 **Integración logística con terceros** — APIs de delivery.
- 🌱 **Physical assessment del café verde** — evaluación del grano sin tostar; el estándar SCA para esto está en fase alpha.
- 📖 **Guía de uso + FAQ** — diferida hasta que las decisiones de evaluación estén cerradas.
- 🧮 **Refinamiento estadístico del consenso** — revisar `σ_max` (teórico vs. realista) y evaluar ICC / Fleiss como índice de concordancia cuando haya volumen multi-offering suficiente.

## 📝 Notas

- 🔍 `php artisan tinker` para inspeccionar datos rápido (ej. `Evaluation::first()->affective`).
- 🩹 Si algo falla al migrar con error de sintaxis SQL, revisar que `.env` apunte a MySQL 8 y no a `sqlite`.
- 🔄 El worker cachea el código en memoria: tras editar el servicio de consenso en dev, reiniciar `queue:work` (o usar `sync`).