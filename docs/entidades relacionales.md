# Modelo de entidades — esquema MySQL

> Definición de campos por entidad (MySQL 8).
>
> **Convención:** todas las tablas tienen `id` BIGINT UNSIGNED PK AI + `created_at`/`updated_at`. Se omiten abajo. Propiedades: PK · FK · UQ · NN · NULL · DEFAULT · der (derivado en backend).
> **Roles:** gestionados por Spatie (tablas propias del paquete), NO como columna en `users`.

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

### `contacts` (polimórfica — reutilizable por cualquier entidad con contacto)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `contactable_type` | VARCHAR(255) | NN |
| `contactable_id` | BIGINT UNSIGNED | NN |
| `phone` | VARCHAR(25) | NULL |
| `email` | VARCHAR(255) | NULL |
| `web` | VARCHAR(255) | NULL |
| `social` | VARCHAR(255) | NULL |
| `address` | VARCHAR(255) | NULL |
| `country` | VARCHAR(60) | NULL |
| `city` | VARCHAR(90) | NULL |
| `postal_code` | VARCHAR(12) | NULL |

UNIQUE (`contactable_type`,`contactable_id`) — un contacto por entidad. Relación `morphOne`; cada contacto pertenece a un único dueño (User, Location, …), impidiendo que dos entidades compartan registro.

### `locations` (punto de venta; pertenece a un user-negocio)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `user_id` | BIGINT UNSIGNED | FK→users, NN |
| `name` | VARCHAR(150) | NN |
| `description` | VARCHAR(255) | NN |
| `latitud` | DECIMAL(10,8) | NN |
| `longitud` | DECIMAL(11,8) | NN |

Contacto vía `contacts` polimórfica (`morphOne`). Los campos de contacto (phone, email, web, social, address, country, city, postal_code) se movieron a `contacts`.

---

## Catálogo (Product Layer)

### `roasteries` (tostador)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `name` | VARCHAR(150) | NN |
| `description` | TEXT | NULL |

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

Sin `roastery` (el tostador vive en `coffee_inventory`). Sin JSON `extrinsics` — todos sus campos son columnas planas ahora.

### `certification_types` (catálogo de certificaciones)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `description` | VARCHAR(150) | NN (orgánico, Fairtrade, …) |

### `certifications` (asociativa coffee ↔ certification_type, N-N)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `coffee_id` | BIGINT UNSIGNED | FK→coffees, NN |
| `certification_type_id` | BIGINT UNSIGNED | FK→certification_types, NN |

UNIQUE (`coffee_id`,`certification_type_id`) — un café no repite tipo. Solo conecta, sin datos propios.

### `coffee_inventory` (asociativa roastery ↔ coffee; el lote de tostado)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `roastery_id` | BIGINT UNSIGNED | FK→roasteries, NN |
| `coffee_id` | BIGINT UNSIGNED | FK→coffees, NN |
| `roast_lot` | VARCHAR(60) | NULL (lote de tostador) |
| `production_date` | DATE | NN |

UNIQUE (`roastery_id`,`coffee_id`,`production_date`) — una producción por café por día. `roast_lot` es dato informativo, no identifica.

### `offerings` (locations ↔ coffee_inventory + derivados)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `location_id` | BIGINT UNSIGNED | FK→locations (ON DELETE RESTRICT), NN |
| `coffee_inventory_id` | BIGINT UNSIGNED | FK→coffee_inventory (ON DELETE RESTRICT), NN |
| `evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `defective_evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `consensus` | JSON | NULL, der |
| `concordance` | DECIMAL(4,3) | NULL, der |
| `verification_status` | ENUM('provisional','verified') | NN, DEFAULT 'provisional', der |

UNIQUE (`location_id`,`coffee_inventory_id`) — un offering por dupla. **Cambio:** antes era `coffee_id`; ahora apunta a `coffee_inventory` (el lote concreto, no el café genérico).

## Evaluación

### `offerings` (locations ↔ coffee_inventory; agregación del consenso descompuesta)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `location_id` | BIGINT UNSIGNED | FK→locations (ON DELETE RESTRICT), NN |
| `coffee_inventory_id` | BIGINT UNSIGNED | FK→coffee_inventory (ON DELETE RESTRICT), NN |
| `evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `defective_evaluation_count` | INT UNSIGNED | NN, DEFAULT 0, der |
| `cupping_avg` | DECIMAL(4,2) | NULL, der (0-100; filtrable por score) |
| `aroma_avg` | DECIMAL(3,1) | NULL, der |
| `flavor_avg` | DECIMAL(3,1) | NULL, der |
| `aftertaste_avg` | DECIMAL(3,1) | NULL, der |
| `acidity_avg` | DECIMAL(3,1) | NULL, der |
| `sweetness_avg` | DECIMAL(3,1) | NULL, der |
| `mouthfeel_avg` | DECIMAL(3,1) | NULL, der |
| `overall_avg` | DECIMAL(3,1) | NULL, der |
| `concordance` | DECIMAL(4,3) | NULL, der (Kendall's W 0-1) |
| `verification_status` | ENUM('provisional','verified') | NN, DEFAULT 'provisional', der |

UNIQUE (`location_id`,`coffee_inventory_id`). El `consensus` JSON del diseño anterior se descompuso: `cupping_avg` + los 7 ejes (`*_avg`) son columnas filtrables; los sabores (`main_tastes`+`cata_freq`) pasaron a `offering_tastes`. Ya no hay JSON en offerings.

### `offering_tastes` (sabores agregados del consenso; main_tastes + cata unificados)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE CASCADE), NN |
| `taxonomy_ref` | BIGINT UNSIGNED | FK→olfactory_taxonomies, NN (id del nodo) |
| `type` | ENUM('main_taste','cata') | NN (discrimina origen) |
| `level` | TINYINT | NN |
| `parent_id` | BIGINT UNSIGNED | NULL (solo cata; main_taste sin jerarquía) |
| `count` | INT UNSIGNED | NN (frecuencia entre evaluaciones cerradas) |

Índice sugerido en `taxonomy_ref` (filtro de sabores del buscador). Derivada: `updateConsensus()` borra y reinserta las filas de este offering en cada recálculo.

### `evaluations` (contenedor; descriptive y affective como JSON)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `offering_id` | BIGINT UNSIGNED | FK→offerings (ON DELETE RESTRICT), NN |
| `evaluator_id` | BIGINT UNSIGNED | FK→users (ON DELETE RESTRICT), NN |
| `evaluator_role` | ENUM('specialist','consumer','coffeeshop') | NN, DEFAULT 'specialist' |
| `extraction_method` | VARCHAR(60) | NULL |
| `status` | ENUM('open','closed') | NN, DEFAULT 'open' |
| `descriptive` | JSON | NN |
| `affective` | JSON | NULL |
| `note` | TEXT | NULL |

(Estructura JSON de `descriptive`/`affective`/`consensus` sin cambios — ver detalle en la versión previa del md; no la repito aquí.)

---

## Taxonomía (Presentation Layer)

### `olfactory_taxonomies` (árbol auto-referencial 3 niveles)
| Columna | Tipo MySQL | Propiedades |
|---|---|---|
| `parent_id` | BIGINT UNSIGNED | FK→olfactory_taxonomies (ON DELETE RESTRICT), NULL |
| `level` | TINYINT | NN (0/1/2) |
| `name_en` | VARCHAR(60) | NN |
| `name_es` | VARCHAR(60) | NN |
| `description_en` | VARCHAR(250) | NULL |
| `description_es` | VARCHAR(250) | NULL |
| `color_base` | CHAR(7) | NULL |
| `color` | CHAR(7) | NULL, der |
| `categories` | JSON | NULL |

Vista `cata_attributes` deriva de aquí (existe, no usada por el formulario actual).