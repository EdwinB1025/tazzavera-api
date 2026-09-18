# Modelo de entidades — esquema MySQL

> Definición de campos por entidad (MySQL 8).
>
> **Convención:** todas las tablas tienen `id` BIGINT UNSIGNED PK AI + `created_at`/`updated_at`. Se omiten abajo. Propiedades: PK · FK · UQ · NN · NULL · DEFAULT · der (derivado en backend).
> **Roles:** gestionados por Spatie (tablas propias del paquete), NO como columna en `users`.
> **Criterio relacional vs JSON:** relacional lo que se filtra / agrupa / ordena; JSON los datos crudos anidados que solo se leen enteros (para agregar o mostrar). El filtrado por sabores y ejes vive en el **agregado** (`offerings`), no en las evaluaciones individuales.
> **Nombres de columna:** snake_case (convención BD). Lo que entra/sale por la API (body, query params, respuestas) usa camelCase — ver `endpoints.md`.

---

## Actores (User Layer)

### `users`
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `name` | VARCHAR(60) | NN |
| `surname` | VARCHAR(60) | NULL |
| `email` | VARCHAR(255) | UQ, NN |
| `email_verified_at` | TIMESTAMP | NULL |
| `password` | VARCHAR(255) | NN |
| `two_factor_secret` | TEXT | NULL |
| `two_factor_recovery_codes` | TEXT | NULL |
| `two_factor_confirmed_at` | TIMESTAMP | NULL |
| `remember_token` | VARCHAR(100) | NULL |
| `deleted_at` | TIMESTAMP | NULL (soft delete) |

Rol vía Spatie (`model_has_roles`), no columna propia.

### `contacts` (polimórfica 1-N — reutilizable por cualquier entidad con contacto)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `contactable_type` | VARCHAR(255) | NN |
| `contactable_id` | BIGINT UNSIGNED | NN |
| `is_primary` | BOOLEAN | NN, DEFAULT 0 (marca el contacto principal; unicidad del primary controlada en backend) |
| `phone` | VARCHAR(25) | NULL |
| `email` | VARCHAR(255) | NULL |
| `web` | VARCHAR(255) | NULL |
| `social` | VARCHAR(255) | NULL |
| `address` | VARCHAR(255) | NULL |
| `country` | VARCHAR(60) | NULL |
| `city` | VARCHAR(90) | NULL |
| `postal_code` | VARCHAR(12) | NULL |

INDEX (`contactable_type`,`contactable_id`) — no unique: una entidad puede tener **varios** contactos. Relación `morphMany`; cada contacto pertenece a un único dueño (User, Location, …). Sin FK de BD (polimórfica): la integridad del `contactable` la gestiona Laravel a través de la relación; los contactos huérfanos se limpian por evento del modelo dueño. Reutilizable por `roasteries` en el futuro sin rediseño.

### `locations` (punto de venta; pertenece a un user-negocio)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `user_id` | BIGINT UNSIGNED | FK→users, NN |
| `name` | VARCHAR(150) | NN |
| `description` | VARCHAR(255) | NN |
| `latitud` | DECIMAL(10,8) | NN |
| `longitud` | DECIMAL(11,8) | NN |

Contacto vía `contacts` polimórfica (`morphMany` — puede tener varios).

---

## Catálogo (Product Layer)

### `roasteries` (tostador)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `name` | VARCHAR(150) | NN |
| `description` | TEXT | NULL |

Sin contacto por ahora; en el futuro vía `contacts` polimórfica.

### `coffees` (el café genérico; atributos de origen)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `name` | VARCHAR(150) | NN |
| `roast_level` | ENUM('light','medium_light','medium','medium_dark','dark') | NN, DEFAULT 'medium' |
| `process` | VARCHAR(60) | NN |
| `variety` | VARCHAR(60) | NN |
| `country` | VARCHAR(60) | NN |
| `region` | VARCHAR(90) | NULL |
| `producer` | VARCHAR(150) | NULL |
| `altitude` | INT UNSIGNED | NULL |
| `lot` | VARCHAR(60) | NULL (lote de origen) |

Sin JSON `extrinsics`: todos sus campos son columnas planas. Sin `roastery` (el tostador vive en `coffee_inventory`). `medium` = línea base de cupping.

### `certification_types` (catálogo de certificaciones)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `code` | VARCHAR(60) | UQ, NN (slug estable: `organic`, `fairtrade`, … — clave pública y de ruta) |
| `description` | VARCHAR(150) | NN (etiqueta de display: orgánico, Fairtrade, …) |

Sin `ulid`: `code` es el identificador público único, así que esta entidad es la excepción a la regla "ulid en todo el dominio" — tiene slug natural estable que el resto de entidades no tiene (dos cafés pueden llamarse igual; `organic` es único por definición). El modelo resuelve el route binding por `code` (`getRouteKeyName()` → `'code'`), no por id ni ulid. Fuente de verdad en un PHP enum (`App\Enums\CertificationType`): el seeder puebla la tabla desde `cases()` (`code` = `$case->value`, `description` = `$case->label()`), con `create()` en foreach para que no haga falta ulid ni ids fijos. El front consume la lista por API y la trata como enum en runtime (itera `code`, no lo hardcodea → valores nuevos sin desplegar front).

### `certifications` (asociativa coffee ↔ certification_type, N-N)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `coffee_id` | BIGINT UNSIGNED | FK→coffees (ON DELETE CASCADE), NN |
| `certification_type_id` | BIGINT UNSIGNED | FK→certification_types (ON DELETE CASCADE), NN |
| `issued_at` | DATE | NN (fecha de emisión de la certificación) |
| `expires_at` | DATE | NULL (fecha de caducidad; NULL = no vence — p. ej. Cup of Excellence, premio a un lote sin vencimiento) |

UNIQUE (`coffee_id`,`certification_type_id`) — un café no repite tipo. Según CVA (Standard 105), la certificación es un atributo del café/origen, por eso cuelga de `coffee`, no del lote de tostado. **ON DELETE CASCADE en ambas FK:** al borrar el café o el tipo, la fila de vínculo muere (la entidad del otro lado sobrevive). **Semántica de `expires_at` NULL = perpetua:** la lógica de vigencia futura trata el NULL como "siempre vigente", no como "vencida por falta de dato" — el filtro sería `whereNull('expires_at')->orWhere('expires_at','>',now())`. Sin modelo pivote todavía: `issued_at`/`expires_at` se escriben con `attach($id, [...])` y se leen vía `withPivot('issued_at','expires_at')` en la relación `belongsToMany`; `wherePivot` cubre el filtrado por vigencia sin necesitar modelo (el modelo pivote solo se añadiría para castear las fechas a Carbon o encapsular métodos de vigencia — ver backlog).

### `coffee_inventory` (asociativa roastery ↔ coffee; el lote de tostado)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `roastery_id` | BIGINT UNSIGNED | FK→roasteries (ON DELETE RESTRICT), NN |
| `coffee_id` | BIGINT UNSIGNED | FK→coffees (ON DELETE RESTRICT), NN |
| `roast_lot` | VARCHAR(60) | NULL (lote de tostador; dato informativo) |
| `production_date` | DATE | NN |

Lleva `ulid` (entidad de dominio expuesta por API — el front referencia un lote concreto al crear una offering). Nombre de tabla en singular `coffee_inventory` (el modelo `CoffeeInventory` requiere `protected $table = 'coffee_inventory'`, porque Eloquent pluralizaría a `coffee_inventories`). UNIQUE (`roastery_id`,`coffee_id`,`production_date`) — una producción por café por día. El café genérico (`coffees`) tiene su propio lote de origen (`coffees.lot`), distinto del lote de tostado (`roast_lot`). **ON DELETE RESTRICT en ambas FK:** un lote sí tiene datos propios y lo referencia `offerings` con RESTRICT; RESTRICT aquí impide que borrar un café o una tostadora arrastre lotes por debajo y puentee ese escudo. Coffees y roasteries hoy son datos de seeder sin endpoint de borrado, así que el RESTRICT no se dispara en producción — solo protege ante borrados manuales.

---

## Evaluación

### `offerings` (locations ↔ coffee_inventory; agregación del consenso descompuesta)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `location_id` | BIGINT UNSIGNED | FK→locations (ON DELETE RESTRICT), NN |
| `coffee_inventory_id` | BIGINT UNSIGNED | FK→coffee_inventory (ON DELETE RESTRICT), NN |
| `evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `defective_evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `cupping_avg` | DECIMAL(4,2) | NULL, der (0-100; filtrable por score) |
| `fragrance_avg` | DECIMAL(3,1) | NULL, der |
| `aroma_avg` | DECIMAL(3,1) | NULL, der |
| `flavor_avg` | DECIMAL(3,1) | NULL, der |
| `aftertaste_avg` | DECIMAL(3,1) | NULL, der |
| `acidity_avg` | DECIMAL(3,1) | NULL, der |
| `sweetness_avg` | DECIMAL(3,1) | NULL, der |
| `mouthfeel_avg` | DECIMAL(3,1) | NULL, der |
| `overall_avg` | DECIMAL(3,1) | NULL, der |
| `concordance` | DECIMAL(4,3) | NULL, der (Kendall's W 0-1) |
| `verification_status` | ENUM('provisional','verified') | NN, DEFAULT 'provisional', der |

UNIQUE (`location_id`,`coffee_inventory_id`). El `consensus` JSON del diseño anterior se descompuso: `cupping_avg` + los 8 ejes (`*_avg`, incl. `fragrance`) son columnas filtrables; los sabores (main_tastes + cata) pasaron a `offering_tastes`. Ya no hay JSON en offerings. Derivados: `updateConsensus()` recalcula columnas + reescribe `offering_tastes` cuando la offering tiene >5 evaluaciones `closed` + `specialist`. **Modelo `Offering`:** `$fillable = ['location_id', 'coffee_inventory_id']` (los derivados los pone el backend, no el request); casts decimales con la precisión de cada columna (`decimal:N` — devuelven string, castear en `updateConsensus` si se opera numéricamente); `HasPublicUlid`. Al crear (`store`), los defaults de columna (verification_status='provisional', counts=0) NO se reflejan en el objeto en memoria — usar `refresh()` tras `create()` para que la respuesta traiga los defaults reales de BD.

### `offering_tastes` (referencias taxonómicas agregadas del consenso; main_tastes + defects + cata por eje, unificados)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE CASCADE), NN |
| `taxonomy_ref` | BIGINT UNSIGNED | FK→olfactory_taxonomies, NN (id del nodo de la taxonomía maestra) |
| `type` | ENUM('main_tastes','defects','fragrance','aroma','flavor','aftertaste','mouthfeel') | NN (origen + eje unificados; coincide con la clave de origen en el JSON de la evaluación) |
| `level` | ENUM('0','1','2') | NN (nivel de la taxonomía; cast a `integer` en el modelo `OfferingTaste` para leerlo como entero) |
| `parent_id` | BIGINT UNSIGNED | NULL, FK→offering_tastes (ON DELETE SET NULL) — auto-referencial: padre en el árbol del consenso de ESTA offering |
| `count` | INT UNSIGNED | NN (frecuencia entre evaluaciones cerradas) |

`type` fusiona origen y eje en una sola columna. `main_tastes` y `defects` son grupos **transversales** (sabores básicos y defectos, sin eje propio); `fragrance`, `aroma`, `flavor`, `aftertaste`, `mouthfeel` son los ejes que capturan cata. **NO** aparecen `acidity`, `sweetness` ni `overall`: esos ejes no llevan descriptores de cata (solo puntuación), así que nunca generan filas aquí. Cada valor del ENUM coincide con la clave de origen en el JSON de la evaluación → `updateConsensus()` mapea origen→type sin traducir. Todos los grupos (main_tastes, defects, cata, mouthfeel) referencian `olfactory_taxonomies` vía `taxonomy_ref` (la taxonomía se reestructuró con un campo `categories` JSON que incluye `aromatics`, `main_tastes`, `defects`, `mouthfeel`).

**Dos referencias con propósitos distintos:** `taxonomy_ref` → vínculo con la **taxonomía maestra** (qué sabor es: nombre, color, nodo). `parent_id` → **auto-referencial a `offering_tastes`**, reconstruye el árbol anidado del consenso DE ESTA offering (no la jerarquía de la taxonomía maestra) — mismo patrón que `olfactory_taxonomies` para presentación en árbol (children recursivos). `updateConsensus()` al poblar debe insertar padres antes que hijos y resolver el `parent_id` a la fila padre recién insertada de la misma offering.

`level` ENUM('0','1','2') en BD, con cast `integer` en el modelo (evita el gotcha de comparar string vs int al leer). Índice en `taxonomy_ref` (filtro de sabores del buscador) y en `offering_id`. UNIQUE (`offering_id`,`taxonomy_ref`,`type`) — un mismo sabor, en el mismo eje, no se repite por offering. Derivada: `updateConsensus()` borra y reinserta las filas de cada offering en el recálculo. Modelo `OfferingTaste`: relaciones `offering()`, `taxonomy()` (belongsTo con FK `taxonomy_ref`), `parent()`/`children()` (self-referencial por `parent_id`); sin `HasPublicUlid` (tabla agregada interna, no expuesta individualmente).

**Consultas sobre `offering_tastes`:**
- **Búsqueda general por sabor** (¿el café tiene X?): `WHERE taxonomy_ref = X` — verifica existencia, ignora `type`.
- **Búsqueda por sabor en eje** (query params `aromaCata`, `flavorCata`, `fragranceCata`, `aftertasteCata`): `WHERE taxonomy_ref = X AND type = 'aroma'` — el `type` da el eje directo.
- **Representación gráfica** (perfil de consenso por eje / radar): árbol anidado reconstruido por `parent_id` (children recursivos), reutilizando el patrón del resource de taxonomía. La búsqueda corre como query directa sobre la tabla base indexada.

> **PENDIENTE (`count`):** decidir si `count` cuenta **menciones** (`COUNT(*)`) o **evaluaciones distintas** (`COUNT(DISTINCT evaluation_id)`). Afecta la magnitud del ranking y del radar (si dos ejes de la misma evaluación mencionan el mismo sabor, menciones lo cuenta dos veces). Para la búsqueda por existencia da igual; para la gráfica/ranking importa. `offering_tastes` es agregado y hoy no guarda `evaluation_id`, así que contar evaluaciones distintas exige resolverlo en `updateConsensus()` al construir el agregado. Resolver antes de implementar `updateConsensus()`.

### `evaluations` (contenedor; descriptive y affective como JSON homólogo)

| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `ulid` | CHAR(26) | UNIQUE, NN (identificador público; trait `HasPublicUlid`, `getRouteKeyName`→'ulid'). PK sigue siendo `id` BIGINT |
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE RESTRICT), NN |
| `evaluator_id` | BIGINT UNSIGNED | FK→users (ON DELETE RESTRICT), NN |
| `evaluation_type` | ENUM('specialist','baseline') | NN (sin default) |
| `extraction_method` | VARCHAR(60) | NULL |
| `status` | ENUM('open','closed') | NN, DEFAULT 'open' |
| `cupping_score` | DECIMAL(4,2) | NULL, derivado del JSON de entrada al insertar (filtrable — filtro `score`) |
| `is_defective` | BOOLEAN | NN, DEFAULT 0, calculado por el controlador |
| `descriptive` | JSON | NN |
| `affective` | JSON | NULL |
| `extrinsics` | JSON | NULL |

`evaluator_id` lo deriva el backend del usuario autenticado; no es input. **`evaluation_type` describe la evaluación, no el rol Spatie del usuario** — se deriva del rol real del usuario autenticado al crear (coffeeshop→`baseline`, specialist→`specialist`), verificado en backend, nunca tomado del front. Es un snapshot **inmutable** en la creación: el rol del usuario puede cambiar, pero el tipo de la evaluación no. Dos valores por ahora; `consumer` queda en backlog (tendrá otra estructura y probablemente entidad separada). Modelado como PHP enum `EvaluationType` (cast, como `RoastLevel`), NOT NULL **sin default** (campo derivado que siempre se asigna; un default enmascararía un olvido, grabando un tipo falso). `cupping_score` e `is_defective` se computan a partir del JSON de entrada al insertar (no viajan en el body) y se guardan como columnas por ser filtrables/contables.

**`descriptive` / `affective` — estructura homóloga (`{eje: {score, note}}`).** `descriptive`: 7 ejes, escala 0-15, sin `overall`. `affective`: 8 ejes, escala 1-9, con `overall`. La nota de `affective.overall.note` **es** la nota general de la evaluación (único eje cuya nota es la general). Orden de ejes según CVA: `fragrance` (olor seco) precede a `aroma` (olor húmedo). Cómo se popula `fragrance` es responsabilidad del front; el cálculo del back es aparte.

```json
// DESCRIPTIVE — mapa por eje (escala 0-15), 7 ejes, sin overall
{
  "fragrance":  { "score": 11, "note": null },
  "aroma":      { "score": 12, "note": null },
  "flavor":     { "score": 12, "note": null },
  "acidity":    { "score": 12, "note": null },
  "sweetness":  { "score": 12, "note": null },
  "mouthfeel":  { "score": 12, "note": null },
  "aftertaste": { "score": 12, "note": null }
}
```

```json
// AFFECTIVE — misma forma (escala 1-9), 8 ejes con overall; overall.note = nota general
{
  "fragrance":  { "score": 7, "note": null },
  "aroma":      { "score": 8, "note": null },
  "flavor":     { "score": 7, "note": null },
  "acidity":    { "score": 7, "note": null },
  "sweetness":  { "score": 8, "note": null },
  "mouthfeel":  { "score": null, "note": null },
  "aftertaste": { "score": 8, "note": null },
  "overall":    { "score": 8, "note": "cuerpo redondo, cierre dulce" }
}
```

```json
// EXTRINSICS — 5 campos de texto (ex note_extrinsics)
{
  "farming": null,
  "processing": null,
  "trading": null,
  "certifications": null,
  "general_observation": null
}
```

---

### `evaluation_tastes` (catas crudas de la evaluación; selección plana del evaluador)

| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `evaluation_id` | BIGINT UNSIGNED | FK→evaluations (ON DELETE CASCADE), NN |
| `taxonomy_ref` | BIGINT UNSIGNED | FK→olfactory_taxonomies (ON DELETE RESTRICT), NN — mismo tipo que `offering_tastes.taxonomy_ref` |
| `type` | ENUM('main_tastes','defects','fragrance','aroma','flavor','aftertaste','mouthfeel') | NN |

UNIQUE(`evaluation_id`, `taxonomy_ref`, `type`). Sin `level`, `parent_id`, `count` ni `ulid` — no se expone individualmente (mismo patrón que `offering_tastes`).

Guarda la **selección plana** del evaluador: el payload trae ULIDs de taxonomía sin jerarquía (el front la resuelve), y el controlador los reparte aquí con su `type` al insertar. `level`/`parent_id`/`count` son artefactos del árbol de consenso que se reconstruyen **al agregar** hacia `offering_tastes`, no datos de la selección cruda; la jerarquía, cuando se necesita, se recupera por JOIN a `olfactory_taxonomies`, no se almacena.

El `type` usa **el mismo ENUM que `offering_tastes`** a propósito: así `updateConsensus` es un `GROUP BY taxonomy_ref, type → count` que inserta directo en `offering_tastes`, sin parsear JSON. (Sin `acidity`/`sweetness`/`overall`: esos ejes no llevan cata.)

```json
// filas resultantes de un payload (ULIDs ya resueltos a taxonomy_ref)
[
  { "taxonomy_ref": 15, "type": "aroma" },
  { "taxonomy_ref": 29, "type": "flavor" },
  { "taxonomy_ref": 24, "type": "main_tastes" },
  { "taxonomy_ref": 52, "type": "defects" }
]
```
---

## Taxonomía (Presentation Layer)

### `olfactory_taxonomies` (árbol auto-referencial 3 niveles; alimenta rueda y CATA)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `parent_id` | BIGINT UNSIGNED | FK→olfactory_taxonomies (ON DELETE RESTRICT), NULL |
| `level` | TINYINT | NN (0 raíz / 1 subcategoría / 2 hoja) |
| `name_en` | VARCHAR(60) | NN |
| `name_es` | VARCHAR(60) | NN |
| `description_en` | VARCHAR(250) | NULL |
| `description_es` | VARCHAR(250) | NULL |
| `color_base` | CHAR(7) | NULL (hex, solo raíces) |
| `color` | CHAR(7) | NULL, der (hex; raíz=color_base, hijos=HSL) |
| `categories` | JSON | NULL (`aromatics`, `main_tastes`, `defects`, `mouthfeel`) |

Semilla: `taxonomia-olfativa-semilla.csv`. La vista `cata_attributes` deriva de aquí los atributos del formulario por dimensión (existe en BD, no usada por el formulario actual — usa scopes de Eloquent directos). Endpoint `GET /taxonomies`: árbol completo anidado (raíces con `children` recursivos, 3 niveles), resource recursivo con `whenLoaded('children')`.

---

## Entidades no implementadas / backlog

- `specialist_profiles` (extensión 1-1 de users)
- `products`, `catalog` (catálogo maestro de productos del negocio)
- Contacto polimórfico para `roasteries` (la entidad `contacts` ya lo soporta; falta la relación)
- Pipeline ETL para aplanar los JSON de `evaluations` a tablas analíticas (análisis intensivo de consumo)
- Actualizar `computeCuppingScore` para usar el eje `fragrance` real en vez de duplicar `aroma`
- Modelo pivote para `certifications` (`belongsToMany(...)->using(Certification::class)`): las columnas `issued_at`/`expires_at` ya existen y se manejan con `attach` + `withPivot` + `wherePivot` (filtrado de vigencia) sin modelo. El modelo pivote solo se añadiría para castear las fechas a Carbon o encapsular lógica de vigencia (`isExpired()`, scopes `active()`/`expired()`) si el casting manual se vuelve recurrente.
- Purga/vigencia de certificaciones (comando + scheduler): comando Artisan `certifications:purge` (`PurgeExpiredCertifications`) que actúa sobre las filas con `expires_at < now()` (NULL = perpetua, nunca se purga). Programado con `Schedule::command('certifications:purge')->daily()` en `routes/console.php`. Requiere disparador del SO (cron en Linux, Programador de tareas en Windows, `schedule:work` en local). Decisión abierta: **borrar** (destructivo, pierde histórico) vs **filtrar** por `wherePivot('expires_at','>',now())` en consultas (no destructivo, sin infraestructura) — el filtrado es preferible salvo que volumen o requisitos legales exijan borrar. Ver `deployment-notes.md`.
- Vista/árbol de representación gráfica sobre `offering_tastes` (perfil de consenso por eje / radar): árbol anidado reconstruido por `parent_id` (children recursivos), para consumo del front. La búsqueda NO lo usa (corre como query directa sobre la tabla base indexada).

## Notas de estado (delta con el esquema real)

Este documento es el diseño **objetivo**. Estado de implementación en el API:
- **Migrado (sesión actual):** `users`, `contacts`, `locations`, `olfactory_taxonomies`, `roasteries`, `certification_types`, `coffees` (con `producer`), `certifications` (con `issued_at`/`expires_at`), `coffee_inventory` (con `ulid`, `$table` explícito), `offerings`, `offering_tastes` (parent_id auto-referencial, level ENUM+cast, unique). Modelos y relaciones de la capa de evaluación cableados (Offering↔Location/CoffeeInventory, OfferingTaste con offering/taxonomy/parent/children).
- **Pendiente de migrar:** `evaluations`.
- **Endpoints implementados:** `GET /locations` (coffeeshop, scoped), `GET /coffeeInventory` (coffeeshop, roastery+coffee anidados, filtros), `GET /taxonomies` (árbol), `POST /offerings` (batch con ownership vía middleware `owns.location:campo` + policy, skip de duplicados). CRUD de users (register/login/logout/user/update/password/delete/force).
- `roasteries` (objetivo futuro): implementar como un tipo de usuario con su respectivo permiso.
- `evaluations` real (cuando se migre): `descriptive`/`affective` con `fragrance` añadido, columnas extraídas (cupping_score, is_defective, defects, main_tastes).