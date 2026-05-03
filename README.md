# BWP Software Engineer — Technical Assessment Submission

- **Candidate:** Christian Lloyd Co
- **Date:** May 2026
- **Time spent:** ~4 hours (Parts A–D)
- **Environment:** Windows, PHP 8.5.3, Composer, Laravel 13 (Part A), MySQL 8.x, MySQL Workbench (Part C)

## Clone this repository

```bash
git clone https://github.com/ChristianCo1011/ChristianLloydCo_BWP_TechAssessment.git
cd ChristianLloydCo_BWP_TechAssessment
```

**Part A** is a runnable Laravel JSON API; **Parts B–D** are the legacy review write-up, MySQL workbook, and a static HTML/CSS/JS page, as required by the brief.

> **For reviewers:** **Part A** → [`laravel/`](laravel/) (migrations, Eloquent, JSON routes, Form Requests, reference seeder; commands in [`laravel/README.md`](laravel/README.md)). **Part B** → [`part_b/legacy_review.md`](part_b/legacy_review.md). **Part C** → [`part_c/`](part_c/) ([`queries.sql`](part_c/queries.sql), [`q03_indexes.md`](part_c/q03_indexes.md), [`screenshots/`](part_c/screenshots/)). **Part D** → [`part_d/`](part_d/) (open `index.html` via a local server—see below). The directory tree matches the repo on disk.

## Repository layout

```
ChristianLloydCo_BWP_TechAssessment/
├── README.md                                                 ← overview (this file)
├── laravel/                                                  ← Part A: API (`composer.json` here)
│   └── README.md                                             ← install, migrate, seed, curl checks
│
├── part_b/
│   └── legacy_review.md                                      ← Part B: issues, refactor, fix map
│
├── part_c/
│   ├── queries.sql                                           ← Q1, Q2, Q4–Q8 SQL
│   ├── q03_indexes.md                                        ← Q3 indexing (prose)
│   └── screenshots/                                          ← Workbench / result captures
│       ├── .gitkeep
│       ├── q01.png
│       ├── q02.png
│       ├── q04.png
│       ├── q05.png
│       ├── q06.png
│       ├── q07.png
│       └── q08.png
│
└── part_d/
    ├── index.html                                            ← Part D markup
    ├── styles.css                                            ← Part D styles
    ├── app.js                                                ← jQuery, filters, table
    └── BWP_Software_Engineer_Technical_Assessment_properties.json  ← Part D data (HR filename)
```

## Part A — Laravel REST API

Reviewers should **`cd laravel`** before `composer install`, migrations, seed, and `serve` (see [`laravel/README.md`](laravel/README.md)). On Windows, if `php artisan …` fails with **could not find driver** for MySQL, run **`.\artisan.ps1`** or **`artisan.cmd`** instead (loads `pdo_mysql` without editing `php.ini`).

- **Reference seed:** [`laravel/database/seeders/BwpAssessmentReferenceSeeder.php`](laravel/database/seeders/BwpAssessmentReferenceSeeder.php) — two projects (`SUNSET`, `RIDGE`) and five properties aligned with Part D’s [`part_d/BWP_Software_Engineer_Technical_Assessment_properties.json`](part_d/BWP_Software_Engineer_Technical_Assessment_properties.json).

### Part A — improvements

Remove unneeded Composer/npm packages in [`laravel/composer.json`](laravel/composer.json) and [`laravel/package.json`](laravel/package.json), then `composer install` and the checks in [`laravel/README.md`](laravel/README.md).

## Part B — Legacy PHP review

Open [`part_b/legacy_review.md`](part_b/legacy_review.md). It contains:

1. A numbered list of issues across security, correctness, performance, and maintainability.
2. A refactored controller with strict types, prepared statements, allow-listed status, JSON responses, and an auth/CSRF check.
3. A table mapping each issue to the fix.

## Part C — MySQL

### Setup (one-time, in MySQL Workbench)

```sql
CREATE SCHEMA `bwp_assessment` DEFAULT CHARACTER SET utf8mb4;
USE bwp_assessment;
```

This repo snapshot does **not** ship `part_c/schema.sql`, the HR reference seed SQL, or `part_c/README.md`. Create the five Part C tables and load the deterministic reference data **from your copy of the BWP assessment DDL/seed** (same definitions the queries were written against), then run [`part_c/queries.sql`](part_c/queries.sql) (`USE bwp_assessment;` is already at the top of that file).

### Answers

- SQL for Q1, Q2, Q4–Q8: [`part_c/queries.sql`](part_c/queries.sql) (each block is delimited by `-- ====== Qn ======`).
- Q3 prose answer (indexing strategy): [`part_c/q03_indexes.md`](part_c/q03_indexes.md) — there is **no** `q03.png`; Q3 is markdown-only.
- Screenshots: [`part_c/screenshots/`](part_c/screenshots/) — `q01.png`, `q02.png`, `q04.png`, `q05.png`, `q06.png`, `q07.png`, `q08.png` (expected row counts are documented inline in `queries.sql` as `-- Expected …` comments).

## Part D — JavaScript filter table

**jQuery** (3.7.1 from the official CDN in [`part_d/index.html`](part_d/index.html); no build step): [`part_d/styles.css`](part_d/styles.css) and [`part_d/app.js`](part_d/app.js). `app.js` loads [`part_d/BWP_Software_Engineer_Technical_Assessment_properties.json`](part_d/BWP_Software_Engineer_Technical_Assessment_properties.json) with `$.ajax` (`cache: false`), renders a table, and provides:

- Case-insensitive text filter against `label` or `project_code`
- Status dropdown (All / available / reserved / sold)
- Inline error banner if the JSON fails to load

### How to run

Loading the JSON over `file://` is unreliable in modern browsers, so serve `part_d/` over HTTP. From a PowerShell terminal at the repo root:

```powershell
cd part_d
php -S localhost:8000
```

Then open <http://localhost:8000/> in your browser.

(`python -m http.server 8000` also works if Python is preferred.)

## Assumptions and notes

- Part A: on Windows, enable PHP **`ext-zip`** and **`ext-fileinfo`** in `php.ini` so Composer can install from dist packages; otherwise use `composer install --ignore-platform-req=ext-fileinfo` (see `laravel/README.md`). For the database, enable **`pdo_mysql`** (MySQL, default in `laravel/.env.example`) or **`pdo_sqlite`** if you use SQLite (this environment’s CLI PHP had no PDO DB driver; migrations were not executed here).
- Part A (`php artisan serve`) and Part D’s sample `php -S localhost:8000` both default to port **8000**; run one on another port if you need both at once (e.g. `php artisan serve --port=8001`).
- Part C Q2 returns `0` (not `NULL`) for projects with zero 2026 commission, via `COALESCE`. The choice is documented inline in `queries.sql`.
- Part C Q5 includes projects with zero active EOIs and shows `0` for the count via a `LEFT JOIN` plus `COUNT(DISTINCT)`.
- Part C Q7 uses a CTE so the window function's partition includes EOIs of all statuses; the active filter is applied in the outer query.
- Part C Q8 uses `JOIN ... DISTINCT` to handle the multiple-pending-EOIs-per-apartment case safely; apartments already in `'sold'` are explicitly skipped.
- Part D price is rendered with `Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })` in `part_d/app.js` (`priceDisplayFormatter`); change locale/currency there if a different display is preferred.
