# Modelo de entidades — esquema MySQL

> Definición de campos por entidad (MySQL 8).
>
> **Convención:** todas las tablas tienen `id` BIGINT UNSIGNED PK AI + `created_at`/`updated_at`. Se omiten abajo. Propiedades: PK · FK · UQ · NN · NULL · DEFAULT · der (derivado en backend).
> **Roles:** gestionados por Spatie (tablas propias del paquete), NO como columna en `users`.
> **Criterio relacional vs JSON:** relacional lo que se filtra / agrupa / ordena; JSON los datos crudos anidados que solo se leen enteros (para agregar o mostrar). El filtrado por sabores y ejes vive en el **agregado** (`offerings`), no en las evaluaciones individuales.

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
| `altitude` | INT UNSIGNED | NULL |
| `lot` | VARCHAR(60) | NULL (lote de origen) |

Sin JSON `extrinsics`: todos sus campos son columnas planas. Sin `roastery` (el tostador vive en `coffee_inventory`). `medium` = línea base de cupping.

### `certification_types` (catálogo de certificaciones)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `description` | VARCHAR(150) | NN (orgánico, Fairtrade, …) |

### `certifications` (asociativa coffee ↔ certification_type, N-N)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `coffee_id` | BIGINT UNSIGNED | FK→coffees (ON DELETE CASCADE), NN |
| `certification_type_id` | BIGINT UNSIGNED | FK→certification_types (ON DELETE CASCADE), NN |

UNIQUE (`coffee_id`,`certification_type_id`) — un café no repite tipo. Solo conecta, sin datos propios. Según CVA (Standard 105), la certificación es un atributo del café/origen, por eso cuelga de `coffee`, no del lote de tostado. **ON DELETE CASCADE en ambas FK:** es una tabla puramente asociativa; una fila sin su café o sin su tipo no significa nada, así que al borrar cualquiera de los dos extremos la fila de vínculo muere (la entidad del otro lado sobrevive).

### `coffee_inventory` (asociativa roastery ↔ coffee; el lote de tostado)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `roastery_id` | BIGINT UNSIGNED | FK→roasteries (ON DELETE RESTRICT), NN |
| `coffee_id` | BIGINT UNSIGNED | FK→coffees (ON DELETE RESTRICT), NN |
| `roast_lot` | VARCHAR(60) | NULL (lote de tostador; dato informativo) |
| `production_date` | DATE | NN |

UNIQUE (`roastery_id`,`coffee_id`,`production_date`) — una producción por café por día. El café genérico (`coffees`) tiene su propio lote de origen (`coffees.lot`), distinto del lote de tostado (`roast_lot`). **ON DELETE RESTRICT en ambas FK:** un lote sí tiene datos propios y lo referencia `offerings` con RESTRICT; RESTRICT aquí impide que borrar un café o una tostadora arrastre lotes por debajo y puentee ese escudo. Coffees y roasteries hoy son datos de seeder sin endpoint de borrado, así que el RESTRICT no se dispara en producción — solo protege ante borrados manuales.

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

UNIQUE (`location_id`,`coffee_inventory_id`). El `consensus` JSON del diseño anterior se descompuso: `cupping_avg` + los 8 ejes (`*_avg`, incl. `fragrance`) son columnas filtrables; los sabores (main_tastes + cata_freq) pasaron a `offering_tastes`. Ya no hay JSON en offerings. Derivados: `updateConsensus()` recalcula columnas + reescribe `offering_tastes` cuando la offering tiene >5 evaluaciones `closed` + `specialist`.

### `offering_tastes` (sabores agregados del consenso; main_tastes + cata unificados)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE CASCADE), NN |
| `taxonomy_ref` | BIGINT UNSIGNED | FK→olfactory_taxonomies, NN (id del nodo) |
| `type` | ENUM('main_taste','cata') | NN (discrimina origen) |
| `level` | TINYINT | NN |
| `parent_id` | BIGINT UNSIGNED | NULL (solo cata; main_taste sin jerarquía) |
| `count` | INT UNSIGNED | NN (frecuencia entre evaluaciones cerradas) |

Índice sugerido en `taxonomy_ref` (filtro de sabores del buscador). Derivada: `updateConsensus()` borra y reinserta las filas de cada offering en el recálculo. `taxonomy_ref` como FK real da integridad (ref inválido rechazado por la BD).

### `evaluations` (contenedor; descriptive y affective como JSON homólogo)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE RESTRICT), NN |
| `evaluator_id` | BIGINT UNSIGNED | FK→users (ON DELETE RESTRICT), NN |
| `evaluator_role` | ENUM('specialist','consumer','coffeeshop') | NN, DEFAULT 'specialist' |
| `extraction_method` | VARCHAR(60) | NULL |
| `status` | ENUM('open','closed') | NN, DEFAULT 'open' |
| `cupping_score` | DECIMAL(4,2) | NULL, der (0-100; filtrable — filtro `score`) |
| `is_defective` | BOOLEAN | NN, DEFAULT 0 (conteo de defectuosos) |
| `defects` | JSON | NULL (array de refs de defectos; sale de `affective`, fuera del mapa de ejes) |
| `main_tastes` | JSON | NULL (array de refs de sabores principales; sale de `descriptive`, fuera del mapa de ejes) |
| `descriptive` | JSON | NN |
| `affective` | JSON | NULL |
| `note` | TEXT | NULL (nota general / eje overall) |
| `note_extrinsics` | TEXT | NULL |

`evaluator_id`/`evaluator_role` los deriva el backend del usuario autenticado, no son inputs. `cupping_score` e `is_defective` se extrajeron de `affective` a columnas (filtrables/contables). `defects` (de `affective`) y `main_tastes` (de `descriptive`) se sacaron a columnas JSON aparte: son arrays de refs que no son ejes, y sacarlos deja **ambos JSON como mapas de ejes puros e idénticos en estructura** (`{eje: {score, cata, note}}`). El resto de ambos JSON son datos crudos anidados que solo se leen enteros para agregar (consenso) y mostrar — no se filtran por evaluación individual. La estructura JSON uniforme permite aplanarlos a tablas analíticas por ETL en el futuro sin rediseñar la BD operacional.

**`descriptive` / `affective` — estructura homóloga (mapa por eje `{score, cata, note}`).** Cada eje agrupa su puntuación, sus sabores CATA (cadena jerárquica completa `{ref, level, parent_id}`, niveles 0-1-2) y su nota de texto. `descriptive`: escala 0-15. `affective`: escala 1-9. `cata` guarda la jerarquía completa (no solo la hoja) para reconstruir la rueda sin re-consultar la taxonomía. Orden de ejes según CVA: `fragrance` (olor seco) precede a `aroma` (olor húmedo). Cómo se popula `fragrance` es responsabilidad del **front**; el cálculo del back es aparte.

```json
// DESCRIPTIVE — mapa homólogo por eje (escala 0-15)
{
  "fragrance":  { "score": 11, "cata": [], "note": null },
  "aroma": {
    "score": 12,
    "cata": [
      { "ref": 1,  "level": 0, "parent_id": null },
      { "ref": 10, "level": 1, "parent_id": 1 },
      { "ref": 15, "level": 2, "parent_id": 10 }
    ],
    "note": null
  },
  "flavor":     { "score": 12, "cata": [ { "ref": 24, "level": 0, "parent_id": null }, { "ref": 25, "level": 1, "parent_id": 24 }, { "ref": 29, "level": 2, "parent_id": 25 } ], "note": null },
  "acidity":    { "score": 12, "cata": [], "note": null },
  "sweetness":  { "score": 12, "cata": [], "note": null },
  "mouthfeel":  { "score": 12, "cata": [], "note": null },
  "aftertaste": { "score": 12, "cata": [], "note": null },
  "overall":    { "score": null, "cata": [], "note": null }
}
```

```json
// AFFECTIVE — misma estructura (escala 1-9); cupping_score/is_defective/defects fuera (columnas)
{
  "fragrance":  { "score": 7, "cata": [], "note": null },
  "aroma":      { "score": 8, "cata": [], "note": null },
  "flavor":     { "score": 7, "cata": [], "note": null },
  "acidity":    { "score": 7, "cata": [], "note": null },
  "sweetness":  { "score": 8, "cata": [], "note": null },
  "mouthfeel":  { "score": null, "cata": [ { "ref": 115, "level": 0, "parent_id": null }, { "ref": 117, "level": 1, "parent_id": 115 } ], "note": null },
  "aftertaste": { "score": 8, "cata": [], "note": null },
  "overall":    { "score": 8, "cata": [], "note": null }
}
```

```json
// defects (columna JSON aparte, de affective) — mismo formato que cata
[
  { "ref": 52, "level": 2, "parent_id": 47 }
]
```

```json
// main_tastes (columna JSON aparte, de descriptive) — mismo formato que cata
[
  { "ref": 24, "level": 0, "parent_id": null }
]
```

> **Notas de migración desde el modelo anterior:** `roast_level` eliminado de `descriptive` (venía null, redundante con `coffees.roast_level`). `cupping_score` (DECIMAL, ej. 88.25) e `is_defective` (boolean) extraídos de `affective` a columnas. `main_tastes` conserva `parent_id` (null en raíz). `note.overall` sigue unificado con la columna `evaluations.note`. La reestructuración a mapa-por-eje unifica descriptive y affective bajo la misma forma (`{eje: {score, cata, note}}`), permitiendo código de lectura/escritura/agregación compartido.
>
> **Eje `fragrance` (nuevo):** añadido como eje propio a ambos JSON y como `fragrance_avg` en offerings. Cómo se popula es responsabilidad del front. **Pendiente:** actualizar el cálculo de `cupping_score` en el back — hoy duplica `aroma` para hacer de fragrance (`Σh_i` = 7 ejes + aroma duplicado); con `fragrance` como eje real, la fórmula debe usar el valor propio en vez de duplicar aroma.

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
| `categories` | JSON | NULL |

Semilla: `taxonomia-olfativa-semilla.csv`. La vista `cata_attributes` deriva de aquí los atributos del formulario por dimensión (existe en BD, no usada por el formulario actual — usa scopes de Eloquent directos).

---

## Entidades no implementadas / backlog

- `specialist_profiles` (extensión 1-1 de users)
- `products`, `catalog` (catálogo maestro de productos del negocio)
- Contacto polimórfico para `roasteries` (la entidad `contacts` ya lo soporta; falta la relación)
- Pipeline ETL para aplanar los JSON de `evaluations` a tablas analíticas (análisis intensivo de consumo)
- Actualizar `computeCuppingScore` para usar el eje `fragrance` real en vez de duplicar `aroma`

## Notas de estado (delta con el esquema real)

El SQL real actual (forward-engineering) aún refleja el diseño **anterior** en varias tablas — este documento es el diseño **objetivo**, a aplicar por migraciones cuando se trabaje cada capa:
- `coffees` real: `roastery` VARCHAR + `extrinsics` JSON (aquí: columnas planas + sin roastery).
- `locations` real: contacto en columnas planas propias (aquí: vía `contacts` polimórfica).
- `offerings` real: `coffee_id` + `consensus` JSON (aquí: `coffee_inventory_id` + columnas descompuestas + `offering_tastes`).
- `evaluations` real: `descriptive`/`affective` con mapas separados `axis`/`cata`/`note`, sin `fragrance` (aquí: mapa homólogo por eje + `fragrance` + columnas extraídas).
- No existen aún: `contacts`, `roasteries` (como FK), `certification_types`, `certifications`, `coffee_inventory`, `offering_tastes`.