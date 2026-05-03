# Part A — Laravel REST API (BWP technical assessment)

- **PHP:** `^8.3` (see root `composer.json`)
- **Laravel:** `^13` (see `composer.json` / `composer.lock`)

## Reviewer quick reference (submission checklist)

| Item | What we use |
|------|----------------|
| **PHP / Laravel** | `^8.3` / `^13` (see `composer.json`) |
| **Migrations + seed** | From `laravel/`: `php artisan migrate:fresh --seed` (or `migrate` then `db:seed` if you keep data). Requires **MySQL** in `.env` and the Docker steps below (or any MySQL instance with the same credentials/host/port). |
| **Start the app** | `php artisan serve` (default [http://127.0.0.1:8000](http://127.0.0.1:8000)) |
| **API base URL** | **`http://127.0.0.1:8000/api`** (same host/port as `serve`; change host/port if you use another server or port) |
| **Verify endpoints** | Table in [Endpoints](#endpoints). **curl:** at least one **GET** and one **POST** (or **PATCH**/**PUT**) in [Verify with curl](#verify-with-curl-replace-hostport-if-needed), including the **empty JSON body** example under [Verify with curl](#verify-with-curl-replace-hostport-if-needed). **Postman:** [Testing with Postman](#testing-with-postman). |

## Run locally

From this directory (`laravel/`):

```bash
composer install
cp .env.example .env   # if you do not already have .env
php artisan key:generate
```

### Database: Docker MySQL (recommended on WSL if you do not run MySQL on the host)

Requires [Docker](https://docs.docker.com/get-docker/) (Docker Desktop on Windows/WSL, or Engine on Linux).

1. Start MySQL:

   ```bash
   cd laravel
   docker compose up -d
   ```

2. In **`.env`**, set **MySQL** (defaults match `docker-compose.yml`). MySQL listens on **host port 3307** (mapped to **3306** inside the container) so it does not clash with another service on 3306.

   **Important:** Docker is configured with **`root`** and **`MYSQL_ROOT_PASSWORD`** (default **`1011`** in `docker-compose.yml`). Laravel’s **`.env`** must use the same **`DB_USERNAME`**, **`DB_PASSWORD`**, and **`DB_PORT`** as that container. After edits, run `php artisan config:clear`.

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=bwp_laravel
   DB_USERNAME=root
   DB_PASSWORD=1011
   ```

   Override the root password by setting **`MYSQL_ROOT_PASSWORD`** before `docker compose up` (must match **`DB_PASSWORD`** in Laravel). Override **`MYSQL_DATABASE`** if you change **`DB_DATABASE`**. For a different host port, set **`MYSQL_PUBLISH_PORT`** (e.g. `3308`) and the same value in **`DB_PORT`**.

   **Existing Docker volume:** MySQL only applies `MYSQL_ROOT_PASSWORD` on **first** data init. If you already created the volume with a different root password, run `docker compose down -v` then `docker compose up -d` to re-init (this **wipes** container DB data), then migrate again.

3. Migrate and seed, then start the HTTP server:

   ```bash
   php artisan config:clear
   php artisan migrate:fresh --seed
   php artisan serve
   ```

   With default `serve`, the app is at **http://127.0.0.1:8000** and the JSON API at **http://127.0.0.1:8000/api** (see [Verify with curl](#verify-with-curl-replace-hostport-if-needed)).

   This app only ships migrations for **`users`** (Laravel default) plus **`projects`** / **`properties`** (Part A). It does **not** migrate `cache`, `cache_locks`, `jobs`, `job_batches`, or `failed_jobs`; **`.env`** uses `SESSION_DRIVER=file`, `CACHE_STORE=file`, and `QUEUE_CONNECTION=sync` so those tables are not required. If your database still has those tables from an older run, use `migrate:fresh` once to rebuild from the current migration set.

### MySQL Workbench (connect to this Docker MySQL)

You do **not** need to run `CREATE DATABASE` by hand: the container creates **`bwp_laravel`** on first start (`MYSQL_DATABASE` in `docker-compose.yml`).

1. In Workbench home, click **+** next to **MySQL Connections** (new connection).
2. **Connection name:** e.g. `BWP Docker (laravel)` — use a **new** name so it is not confused with **Local instance MySQL80** (that is port **3306**, a different server).
3. **Connection method:** `Standard (TCP/IP)`.
4. **Hostname:** `127.0.0.1`  
5. **Port:** **`3307`** — must match Docker’s host mapping (`3307:3306` in Docker Desktop).
6. **Username:** **`root`**
7. **Password:** **Store in Keychain / Vault…** → **`1011`** (same as **`MYSQL_ROOT_PASSWORD`** default in `docker-compose.yml`).
8. **Default schema:** `bwp_laravel` (optional; you can pick it after connecting).
9. Click **Test Connection**, then **OK**.

Open the new connection, then in **SCHEMAS** right‑click **`bwp_laravel`** → **Refresh All**. You should see tables such as `migrations`, `users`, `projects`, `properties` after you have run `php artisan migrate` (or `migrate:fresh --seed`).

**Port clash:** If another app already uses **3307**, change **`MYSQL_PUBLISH_PORT`** in the environment and **`DB_PORT`** / Workbench port to match.

4. Stop MySQL when finished:

   ```bash
   docker compose down
   ```

   Add `-v` to remove the named volume and wipe data: `docker compose down -v`.

## Architecture (Part A)

HTTP controllers under `app/Http/Controllers/Api/` use concrete **services** (`app/Services/`). Services depend on **`ProjectInterface`** and **`PropertyInterface`** (`app/Interfaces/`); Eloquent **repositories** in `app/Repositories/` implement those contracts. Bindings are in `app/Providers/AppServiceProvider.php`. Mutating actions wrap the service in `DB::transaction()` with `Log::error` on failure. Form requests stay on the controller boundary for validation.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/projects` | List projects (includes `properties_count`) |
| `GET` | `/api/properties` | List properties (optional `?project_id=`) |
| `GET` | `/api/properties/{id}` | One property with `project` loaded |
| `POST` | `/api/properties` | Create (422 + validation errors on failure) |
| `PUT` / `PATCH` | `/api/properties/{id}` | Update |
| `DELETE` | `/api/properties/{id}` | Soft-delete (`200` + `message`, `data`: `id`, `deleted_at`) |

Successful JSON bodies use: `{ "data": ... }`.

**Postman / Insomnia:** Step-by-step guide in [Testing with Postman](#testing-with-postman). This app prepends **`ForceJsonResponse`** on the `api` middleware group so `/api/*` negotiates JSON and validation failures return **422** with an `errors` object (even if the client omits `Accept: application/json`).

## Reference seed

`php artisan db:seed` runs `Database\Seeders\BwpAssessmentReferenceSeeder`, which loads **two projects** (`SUNSET`, `RIDGE`) and **five properties** aligned with Part D’s sample JSON (labels, statuses, prices, project association).

## Verify with curl (replace host/port if needed)

**Read — list projects**

```bash
curl -sS -H "Accept: application/json" "http://127.0.0.1:8000/api/projects"
```

**Read — list properties for project 1**

```bash
curl -sS -H "Accept: application/json" "http://127.0.0.1:8000/api/properties?project_id=1"
```

**Read — single property**

```bash
curl -sS -H "Accept: application/json" "http://127.0.0.1:8000/api/properties/1"
```

**Write — create a property**

```bash
curl -sS -X POST "http://127.0.0.1:8000/api/properties" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"project_id":1,"label":"Test Unit","status":"available","price":399000}'
```

#### Write — validation failure (empty JSON body)

Send `{}` (or omit required keys). Expect **422** and JSON with `message` plus `errors` for `project_id`, `label`, and `status`.

```bash
curl -sS -X POST "http://127.0.0.1:8000/api/properties" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{}'
```

HTTP status should be **422**. Same idea with **no** body (still set `Content-Type: application/json`):

```bash
curl -sS -X POST "http://127.0.0.1:8000/api/properties" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d ''
```

**Write — update a property (PATCH)**

```bash
curl -sS -X PATCH "http://127.0.0.1:8000/api/properties/1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status":"reserved"}'
```

**Write — delete (soft-delete; use an id you created, or re-seed after)**

```bash
curl -sS -X DELETE "http://127.0.0.1:8000/api/properties/99"
```

Expect **200** and JSON like `{ "message": "Property deleted successfully.", "data": { "id": 99, "deleted_at": "..." } }`. Unknown id → **404**.

Expect **404** for unknown ids on read/update/delete; **422** for invalid create/update payloads.

## Testing with Postman

**Setup**

1. From `laravel/`, run **`php artisan migrate:fresh --seed`** (once) and **`php artisan serve`**.
2. Use base URL **`http://127.0.0.1:8000`** (or your port). All paths below are prefixed with **`/api`**.
3. Optional: create a Postman **Environment** with variable **`base_url`** = `http://127.0.0.1:8000`, then use `{{base_url}}/api/...` in each request.
4. **GET** requests: no body. For **POST** / **PUT** / **PATCH**: **Body** → **raw** → **JSON**, and set **`Content-Type: application/json`** (Postman usually sets it when you pick JSON). This app uses **`ForceJsonResponse`** on `/api/*`, so validation errors are **422** JSON even without **`Accept: application/json`**.
5. Replace **`{id}`** with a real property `id` from **`GET /api/properties`** (after seeding, **`1`** is often valid).

---

### 1. `GET /api/projects` — list projects

| Field | Value |
|--------|--------|
| **Method** | `GET` |
| **URL** | `http://127.0.0.1:8000/api/projects` |
| **Params** | — |
| **Body** | none |

**Expect:** **200** and JSON `{ "data": [ ... ] }` where each project includes **`properties_count`**.

---

### 2. `GET /api/properties` — list all properties

| Field | Value |
|--------|--------|
| **Method** | `GET` |
| **URL** | `http://127.0.0.1:8000/api/properties` |
| **Params** | — |
| **Body** | none |

**Expect:** **200** and `{ "data": [ ... ] }` with nested **`project`** on each row.

---

### 3. `GET /api/properties` — list properties for one project

| Field | Value |
|--------|--------|
| **Method** | `GET` |
| **URL** | `http://127.0.0.1:8000/api/properties` |
| **Params** (tab **Params**) | Key **`project_id`**, value **`1`** (or another project id) |
| **Body** | none |

**Expect:** **200** and only properties for that **`project_id`**.

---

### 4. `GET /api/properties/{id}` — show one property

| Field | Value |
|--------|--------|
| **Method** | `GET` |
| **URL** | `http://127.0.0.1:8000/api/properties/1` (use a real id) |
| **Params** | — |
| **Body** | none |

**Expect:** **200** and `{ "data": { ... "project": { ... } } }`. Wrong id → **404**.

---

### 5. `POST /api/properties` — create property

| Field | Value |
|--------|--------|
| **Method** | `POST` |
| **URL** | `http://127.0.0.1:8000/api/properties` |
| **Body** (raw JSON) | See below |

**Valid example (201):**

```json
{
  "project_id": 1,
  "label": "Postman test unit",
  "status": "available",
  "price": 399000
}
```

**`status`** must be one of: **`available`**, **`reserved`**, **`sold`**. **`price`** may be omitted or `null`.

**Validation (422):** body `{}` or missing required fields → **422** with **`errors`** for **`project_id`**, **`label`**, **`status`** as applicable.

**Expect:** **201** and `{ "data": { ... } }` on success.

---

### 6. `PUT /api/properties/{id}` — full update

| Field | Value |
|--------|--------|
| **Method** | `PUT` |
| **URL** | `http://127.0.0.1:8000/api/properties/1` |
| **Body** (raw JSON) | Send the fields you want to persist (same shape as create; rules use `sometimes` so you can send a subset, but include everything you intend to keep). |

**Example:**

```json
{
  "project_id": 1,
  "label": "Updated via PUT",
  "status": "reserved",
  "price": 410000
}
```

**Expect:** **200** and `{ "data": { ... } }`. Invalid payload → **422**.

---

### 7. `PATCH /api/properties/{id}` — partial update

| Field | Value |
|--------|--------|
| **Method** | `PATCH` |
| **URL** | `http://127.0.0.1:8000/api/properties/1` |
| **Body** (raw JSON) | Only fields to change. |

**Example:**

```json
{
  "status": "sold"
}
```

**Expect:** **200** and `{ "data": { ... } }`. Invalid values → **422**.

---

### 8. `DELETE /api/properties/{id}` — soft-delete property

| Field | Value |
|--------|--------|
| **Method** | `DELETE` |
| **URL** | `http://127.0.0.1:8000/api/properties/99` (use an id you are allowed to delete) |
| **Body** | none |

**Expect:** **200** and `{ "message": "…", "data": { "id": …, "deleted_at": "…" } }` (row is soft-deleted; it no longer appears in **GET** list/show). Unknown **`{id}`** → **404** (route model binding). If the delete logic throws, you may see **500** with JSON **`message`**.

---

**Troubleshooting**

- **200 HTML (Laravel welcome):** wrong URL (e.g. missing **`/api`**), or **GET** instead of **POST**, or **Body** not **raw → JSON** for writes.
- **`project_id` / foreign key errors:** use an id that exists in **`projects`** (see **`GET /api/projects`**).
