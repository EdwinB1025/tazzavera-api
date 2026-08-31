# Tazavera — Diseño de Endpoints REST

Roles: `specialist`, `coffeeshop` (tabla `roles` separada; no existe rol `admin`, cada usuario gestiona su propio perfil).

**Specialist:** catador/cupper profesional autenticado. Su función es crear, editar y cerrar evaluaciones sobre offerings existentes — es quien aporta el juicio experto que alimenta el consenso de un offering. No crea ni edita offerings.

**Coffeeshop:** cafetería autenticada, dueña de sus propias offerings. Crea y edita sus offerings (asociando un café y una ubicación), y crea/edita la evaluación técnica provisional (máximo una por offering) necesaria para publicarlo. No cierra evaluaciones ni evalúa offerings ajenas.

**Usuario (público):** visitante sin autenticación (o autenticado sin rol elevado) que solo consulta información — accede a los endpoints públicos de index/show de offerings y evaluations. No crea, edita, cierra ni elimina nada; es consumo de solo lectura.

## Offerings

| Método | Endpoint | Rol | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/offerings` | — | Público | Query: `name`, `city`, `origin`, `process`, `score`, `main_tastes[]`, `specific_tastes[]` |
| GET | `/offerings/{id}` | — | Público | — |
| POST | `/offerings` | coffeeshop | Autenticado | Body: `location_id`, `coffee_id` |
| PUT | `/offerings/{id}` | coffeeshop (dueño) | Autenticado | Body: `location_id`, `coffee_id` |
| DELETE | `/offerings/{id}` | coffeeshop (dueño) | Autenticado | — |

`location_id` y `coffee_id` referencian registros ya existentes (no se crean anidados en el mismo request). La pareja `(location_id, coffee_id)` es única.

## Evaluations

| Método | Endpoint | Rol | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| GET | `/evaluations` | — | Público | Query: `id` (evaluador), `coffee_id`, `city`, `location_id`, `process`, `score`, `status` |
| GET | `/evaluations/{id}` | — | Público | — |
| POST | `/evaluations` | specialist, coffeeshop | Autenticado | Body: `offering_id`, `extraction_method`, `status`, `descriptive`, `affective`, `note` |
| PUT | `/evaluations/{id}` | specialist (propia, incluye cierre con `status: closed`), coffeeshop (propia, máx. 1 por offering) | Autenticado | Body: `extraction_method`, `status`, `descriptive`, `affective`, `note` |
| DELETE | `/evaluations/{id}` | specialist (propia), coffeeshop (propia) | Autenticado | — |

`evaluator_id` y `evaluator_role` **no** son inputs del request: el backend los determina a partir del usuario autenticado (`evaluator_id` = usuario logueado; `evaluator_role` = rol de ese usuario). El cierre de una evaluación no tiene ruta propia — es el mismo `PATCH` cambiando `status` a `closed`.

## Users

| Método | Endpoint | Rol | Auth | Query Parameters / Inputs |
|---|---|---|---|---|
| POST | `/register` | — | Público | Body: `role`,`name`, `surname`, `email`, `password`, `password_confirmation` |
| POST | `/login` | — | Público (sesión web) | Body: `email`, `password` — crea sesión web (Fortify, guard stateful); respuesta `{"two_factor": false}`. Es el paso *authenticate* previo a `/oauth/authorize`, NO emite token |
| GET | `/oauth/authorize` | — | Requiere sesión web activa | Query: `client_id`, `redirect_uri`, `response_type=code`, `scope`, `state`, `code_challenge`, `code_challenge_method=S256`. Con `skipsAuthorization` (first-party) responde `302` con `code` en el `Location` (redirect al `redirect_uri`) |
| POST | `/oauth/token` | — | Público (cliente PKCE) | Body (form-urlencoded): `grant_type=authorization_code`, `client_id`, `redirect_uri`, `code`, `code_verifier`. Sin `client_secret` (cliente público). Devuelve `access_token` + `refresh_token` |
| POST | `/oauth/token` | — | Público (cliente confidencial) | Body (form-urlencoded): `grant_type=password`, `client_id`, `client_secret`, `username` (email), `password`, `scope`. First-party de confianza. Devuelve `access_token` + `refresh_token` |
| POST | `/logout` | specialist, coffeeshop | Autenticado | — |
| GET | `/users/{id}` | specialist, coffeeshop (propio) | Autenticado | — (restringido a que `{id}` sea el propio usuario) |
| PUT | `/users/{id}` | specialist, coffeeshop (propio) | Autenticado | Body: `name`, `surname`, `email`, `current_password`, `password`,  |
| DELETE | `/users/{id}` | specialist, coffeeshop (propio) | Autenticado | — (restringido a que `{id}` sea el propio usuario) |

El login es un flujo PKCE de tres pasos: `POST /login` (crea la sesión web de Fortify) → `GET /oauth/authorize` (con esa sesión, devuelve el `code`) → `POST /oauth/token` (intercambia `code` + `code_verifier` por el `access_token`). El `access_token` es el que autentica las rutas `auth:api` vía `Authorization: Bearer`. El `code_verifier` es del cliente y solo viaja en el paso token; en `/oauth/authorize` viaja únicamente su hash (`code_challenge`).