# Backlog — tazavera-api

Pending API changes. Each one is done locally from `deploy`, merged back into `deploy` and pushed; when it changes the contract, `public/docs/openapi.yaml` is copied to the front (`contracts/openapi.yaml`) and the front types are regenerated.

## A1 — Coffee municipality of origin

- **Today:** a coffee's origin is `country` and `region` only (`coffees` table). The seeded coffees put the municipality inside `region` ("Inzá, Cauca", "Acevedo, Huila"). The inventory filter `city` is the roastery's contact city, not the coffee's origin.
- **Change:** a nullable `municipality` (string, max 90) on `coffees`: migration, `Coffee` fillable, `CoffeeResource`, store/update requests and their Scribe docs; a `municipality` filter on `GET /coffeeInventory` and on the offering filters (`whereHas('coffee', …)`, like `originRegion`); seeders and factory split it from the region (Enlu: Inzá / Cauca; Opita Café: Acevedo / Huila); Pest tests for the column, the resource and the filter; docs regenerated.
- **Front after it:** contract copied and types regenerated; the inventory and offering facets gain the Country → Region → Municipality cascade; the coffee details show the municipality.
- **Asked by:** Product owner, 2026-10-13
- **Status:** proposed

## A2 — Each sensory axis accepts only its own descriptors

- **Today:** `StoreEvaluationRequest` and `UpdateEvaluationRequest` check each `descriptive.<axis>.cata.*` only with `exists:olfactory_taxonomies,ulid`, so the mouthfeel axis accepts aromatic notes, and the aromatic axes, main tastes and defects accept descriptors of other categories.
- **Change:** each axis accepts only the descriptors of its category (mouthfeel level 1 for the body; aromatic leaves for fragrance, aroma, flavor and aftertaste; `main_tastes` level 0; `defects`), with a translated 422 per field and Pest tests. The seeders already follow this (`ab6c1d5`).
- **Asked by:** Product owner, 2026-10-13 (front backlog R83)
- **Status:** proposed
