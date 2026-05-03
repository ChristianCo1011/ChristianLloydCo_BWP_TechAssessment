# Part B — Legacy code review

> Snippet under review: a CodeIgniter 2-style controller `Assessment_properties` with two methods, `list_for_partner` and `quick_update_status`. Source is in the assessment brief.

The snippet is short but packs a lot of issues across **security, correctness, performance, and maintainability**. Below I (1) list the issues, (2) provide a refactored version, and (3) map each issue to the fix.

---

## 1. Issues found

**`#`** restarts at 1 in each group below — same order and labels as **§3** (`list_for_partner` → `quick_update_status` → both).

### `list_for_partner`

1. **SQL injection in inner `project_id` lookup.** The inner query `"SELECT * FROM projects WHERE id = " . $row->project_id` interpolates a value that came from the database. It looks safe today, but if any code path ever lets that column be user-influenced it becomes injectable. Using a placeholder removes the foot-gun entirely.
2. **Reflected XSS.** `$partner_id` from `$_GET` is echoed directly into the `<h1>`, and `$row->label` and `$p->name` are echoed into `<li>` without `htmlspecialchars`. A request like `?partner_id=<script>...</script>` runs in the browser.
3. **No authentication / authorisation check.** The controller trusts whatever `partner_id` arrives. A logged-in partner A can pass `partner_id=B` and read another partner's portfolio.
4. **Logging PII / request data.** `error_log("Partner list: " . print_r($_GET, true))` writes the full query string (which can contain partner identifiers, search terms, eventually session tokens) to the application log on every call. This is debug code that has no place in production and likely violates the org's data-handling policy.
5. **Bug: `$p` leaks out of the gather loop and is reused in the render loop.** The first loop assigns `$p = …project lookup…` per iteration. After the loop, `$p` references the **last** project examined. The second loop then echoes `$p->name` for every row — every list item shows the same project name regardless of which property it is. Classic variable-scope leak.
6. **Missing `isset` / type checks on superglobals.** If the request omits `partner_id` or `q`, PHP raises a notice/warning and the SQL/string ops behave on `null`. With strict modes this becomes a fatal.
7. **Match logic does not match the apparent intent.** `stripos($row->label, $search)` filters on label only, but the variable is named `$search` and a partner-list endpoint usually searches across multiple fields. At minimum the intent is unclear; document or generalise.
8. **N+1 queries.** For every property row we run a separate `SELECT ... FROM projects WHERE id = ?`. On 10k properties that is 10,001 round-trips. A single `JOIN` returns the same data in one query.
9. **Loading the entire `properties` table into PHP and filtering there.** The snippet comments that the dataset is "small"; that may be true today but often becomes less true as data grows. Applying filters in SQL (`WHERE deleted = 0 AND partner_id = ? AND label LIKE ?`) is usually more efficient and scalable, and lets the database use indexes where appropriate.
10. **No `LIMIT` / pagination.** Even with the right `WHERE`, an unbounded list endpoint can return millions of rows and hang the request.
11. **Missing index on `(deleted, partner_id, label)` or similar.**
   - **Not applied in this submission:** there is no migration or `CREATE INDEX` in this repo.
   - **What was wrong:** as row counts grow, `WHERE` / `LIKE` on `deleted`, `partner_id`, and `label` can degrade without indexes that match those predicates.
   - **How it is addressed here vs. in production:** the refactor moves those filters into SQL (issue 9), so the database *can* use an index once one exists. Actually adding the index remains a separate schema task: validate with `EXPLAIN`, then ship via migration or DBA review.
12. **`echo` of HTML directly from a controller** mixed with backslash-escaped entities (`&mdash;` works in HTML but is brittle if the response ever ships as JSON).

### `quick_update_status`

1. **SQL injection in `UPDATE`.** Both `$id` and `$status` are concatenated straight into the `UPDATE` string from `$_POST` with zero validation or escaping. A caller can pass `id = 1; DROP TABLE properties; --` (or, more realistically, smuggle `'` and rewrite the `WHERE`).
2. **No CSRF protection on the state-changing endpoint.** `quick_update_status` mutates data on a `POST` with no token check — a third-party page can submit the form silently.
3. **Magic boolean column `deleted = 0`.** Acceptable in a legacy schema, but a soft-delete should usually be combined with a `deleted_at` timestamp and a model scope so it can't be forgotten in joins; the update path should not revive or ignore deleted rows casually.

### Both methods

1. **No 404 / empty handling.** If no rows match, the page still renders an empty `<ul>` with the user-controlled partner id baked in; the update path gives no clear signal when nothing matched.
2. **Mixing concerns.** A controller method opens connections, runs SQL, and emits HTML. Move the query to a model/repository and the rendering to a view (or just `echo json_encode` for an API endpoint).
3. **Wrong / inconsistent `Content-Type`.** The companion `quick_update_status` returns JSON; `list_for_partner` returns HTML. Pick one transport for the endpoint family.
4. **No prepared statements anywhere.** Even for the "safe" lookups, prepared statements should be the default.
5. **No PHP types / strict types.** Modern PHP supports `declare(strict_types=1)`, parameter and return types; the snippet has none.

---

## 2. Refactored code

Single PHP file. I have kept the CodeIgniter-2 *shape* (a controller with `$this->db`) so the refactor is recognisable: the **list** uses the **query builder** (no hand-written SQL string); the **update** still uses the builder as before.

```php
<?php
declare(strict_types=1);

/**
 * Assessment_properties — refactored.
 *
 * Assumptions:
 *  - $this->db->query($sql, $bindings) returns a result object whose
 *    result()/row() honour parameter binding (CI2 syntax).
 *  - There is a session/auth helper available (`$this->auth`) that
 *    exposes the current user; in real CI2 this is usually `$this->session`
 *    or a custom library. Replace with the project's actual helper.
 *  - Output is JSON. If HTML is genuinely required, render through a view
 *    file with auto-escaping rather than echoing strings here.
 */
final class Assessment_properties extends CI_Controller
{
    private const ALLOWED_STATUSES = ['available', 'reserved', 'sold'];
    private const DEFAULT_LIMIT = 100;
    private const MAX_LIMIT = 500;

    private const HTTP_OK = 200;
    private const HTTP_BAD_REQUEST = 400;
    private const HTTP_FORBIDDEN = 403;
    private const HTTP_NOT_FOUND = 404;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;
    private const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Return properties for one partner as JSON (soft-deleted rows excluded).
     *
     * Expected GET input: `partner_id` (int, required), `q` (optional substring match on `p.label`).
     * The authenticated partner must match `partner_id` (see `$this->auth->partnerId()`).
     *
     * @return void
     */
    public function list_for_partner(): void
    {
        $partnerId = filter_input(INPUT_GET, 'partner_id', FILTER_VALIDATE_INT);
        $searchRaw = filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?? '';
        $search = trim((string) $searchRaw);

        if ($partnerId === false || $partnerId === null || $partnerId <= 0) {
            $this->jsonResponse(
                array('error' => 'partner_id is required'),
                self::HTTP_BAD_REQUEST
            );

            return;
        }

        // Authorisation: a partner can only list their own properties.
        // Replace `$this->auth->partnerId()` with the project's accessor.
        $currentPartnerId = $this->auth->partnerId();
        if ($currentPartnerId === null || $currentPartnerId !== $partnerId) {
            $this->jsonResponse(
                array('error' => 'forbidden'),
                self::HTTP_FORBIDDEN
            );

            return;
        }

        $limit = self::DEFAULT_LIMIT;

        // Same behaviour as the previous raw SQL: partner + not deleted, optional
        // substring match on label only when $search is non-empty (equivalent to
        // `AND (? = "" OR p.label LIKE CONCAT("%", ?, "%"))` with bound params).
        $this->db
            ->select(array(
                'p.id',
                'p.label',
                'p.status',
                'p.price',
                'pr.id AS project_id',
                'pr.name AS project_name',
                'pr.code AS project_code'
            ), false)
            ->from('properties p')
            ->join('projects pr', 'pr.id = p.project_id', 'inner')
            ->where('p.deleted', 0)
            ->where('p.partner_id', $partnerId);

        if ($search !== '') {
            $this->db->like('p.label', $search, 'both');
        }

        $this->db->order_by('p.id', 'DESC');
        $this->db->limit((int) $limit);

        $rows = $this->db->get()->result();

        $this->jsonResponse(
            array(
                'data' => $rows,
                'meta' => array(
                    'partner_id' => $partnerId,
                    'q' => $search,
                    'count' => count($rows),
                    'limit' => $limit,
                ),
            ),
            self::HTTP_OK
        );
    }

    /**
     * Update `properties.status` for one row (not soft-deleted).
     *
     * Expected POST input: `id` (property PK), `status` (must match `self::ALLOWED_STATUSES`).
     * Add CSRF and fine-grained authorisation checks in a real app (comments in body).
     *
     * @return void
     */
    public function quick_update_status(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);

        if ($id === false || $id === null || $id <= 0) {
            $this->jsonResponse(
                array('error' => 'id is required'),
                self::HTTP_BAD_REQUEST
            );

            return;
        }
        if (!is_string($status) || !in_array($status, self::ALLOWED_STATUSES, true)) {
            $this->jsonResponse(
                array(
                    'error' => 'invalid status',
                    'allowed' => self::ALLOWED_STATUSES,
                ),
                self::HTTP_UNPROCESSABLE_ENTITY
            );

            return;
        }

        // Authorisation + CSRF check belong here. Pseudocode:
        // if (!$this->security->csrf_verify()) { 403 ... }
        // if (!$this->auth->canEditProperty($id)) { 403 ... }

        $updated = $this->db
            ->where('id', $id)
            ->where('deleted', 0)
            ->update('properties', array(
                'status' => $status,
            ));

        if ($updated !== true) {
            $this->jsonResponse(
                array('error' => 'update failed'),
                self::HTTP_INTERNAL_SERVER_ERROR
            );

            return;
        }

        $affected = $this->db->affected_rows();
        if ($affected === 0) {
            $this->jsonResponse(
                array('error' => 'not found'),
                self::HTTP_NOT_FOUND
            );

            return;
        }

        $this->jsonResponse(
            array(
                'ok' => true,
                'id' => $id,
                'status' => $status,
            ),
            self::HTTP_OK
        );
    }

    /**
     * Write JSON to the output stream with status code and JSON content type.
     *
     * @param array<string, mixed> $payload Serializable body (objects in `$payload` must json_encode cleanly).
     * @param int $statusCode HTTP status; prefer `self::HTTP_*` constants (e.g. `self::HTTP_OK`).
     * @return void
     * @throws JsonException When {@see json_encode} fails (`JSON_THROW_ON_ERROR`).
     */
    private function jsonResponse(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
```

A few things worth pointing out about the refactor:

- The two N+1 queries collapse into a single parameterised `JOIN`.
- The `LIKE` is anchored with `%` only on the SQL side; the empty-string case short-circuits so an empty filter doesn't degrade to `LIKE '%%'` over an unindexed column. (For very large tables, prefer a dedicated full-text or trigram index instead of `LIKE '%x%'`.)
- The list query uses the **query builder** (`select` / `join` / `where` / optional `like` / `order_by` / `limit`): no string-built SQL; `partner_id` and the search term are escaped by the driver. `$limit` is cast to `int` before `limit()`. The status update still uses the builder (`where` + `update` with an array).
- All values reaching the response go through `json_encode` (which escapes safely for `application/json`). If HTML output is genuinely required, render through a CI view with `html_escape()` / a templating engine instead of `echo`.
- HTTP status codes use named `private const` values (e.g. `HTTP_BAD_REQUEST`) instead of bare integers in `jsonResponse` calls.

---

## 3. Issue-to-fix map

### `list_for_partner`

| # | Issue | How the refactor addresses it |
|---|-------|-------------------------------|
| 1 | SQL injection in inner `project_id` lookup | Eliminated by replacing the per-row lookup with a single `JOIN`; no string interpolation of IDs into SQL. |
| 2 | Reflected XSS | Response is `application/json` via `json_encode`; no raw HTML concatenation. If HTML is required later, render through a view with `html_escape()`. |
| 3 | No authentication / authorisation check | Explicit `partner_id` ownership check (`$this->auth->partnerId()`) before the list query runs. |
| 4 | Logging PII via `error_log($_GET)` | Removed. If observability is needed, log a structured event with non-sensitive fields only. |
| 5 | `$p` leak / stale variable in render | Project columns come from the same row via the `JOIN`, so each row has the correct `project_name` / `project_code`. |
| 6 | Missing `isset` / type checks on superglobals | `filter_input` / validation for `partner_id` and `q`; invalid `partner_id` returns `400`. |
| 7 | Match logic does not match the apparent intent | `q` is applied as a label substring filter in SQL (`LIKE` with bound value); empty `q` short-circuits in the `WHERE` clause. |
| 8 | N+1 queries | Single `JOIN`, one round-trip. |
| 9 | Loading entire `properties` table into PHP and filtering there | Filters live in SQL (`deleted`, `partner_id`, optional `LIKE`). |
| 10 | No `LIMIT` / pagination | List is capped with `DEFAULT_LIMIT` (constant `MAX_LIMIT` is available if you add query-param pagination later). |
| 11 | Missing index | **Index DDL not applied in this submission** (no `CREATE INDEX` or migration in-repo). **What was wrong:** at scale, those predicates can be slow without matching indexes. **What the refactor does:** filters run in SQL (see row 9), so an index can be used once it exists. **Production follow-up:** `EXPLAIN` the real workload, then add indexes (often a composite on `deleted`, `partner_id`, `label`, tuned to your data) via migration or DBA. |
| 12 | `echo` of HTML from controller | Replaced with JSON via `jsonResponse`; HTML is a separate concern (use a view). |

### `quick_update_status`

| # | Issue | How the refactor addresses it |
|---|-------|-------------------------------|
| 1 | SQL injection in `UPDATE` | Query builder `where` / `update` (escaped values) instead of concatenation; `id` validated with `filter_input`; `status` allow-listed. |
| 2 | No CSRF on state-changing `POST` | Comment / pseudocode for `csrf_verify()`; in CI2 enable `$config['csrf_protection']` where appropriate. |
| 3 | Magic boolean column `deleted = 0` | `->where('deleted', 0)` on update so soft-deleted rows are not changed; list query already filters `p.deleted = 0`. |

### Both methods

| # | Issue | How the refactor addresses it |
|---|-------|-------------------------------|
| 1 | No 404 / empty handling | List: empty result is a valid `data: []` with `count: 0`. Update: `404` when `affected_rows() === 0` after an otherwise successful query. |
| 2 | Mixing concerns | DB access still in the controller (CI2-style), but output is unified JSON via `jsonResponse`; SQL could move to a model in a larger refactor. |
| 3 | Wrong / inconsistent `Content-Type` | Both endpoints return JSON with an explicit JSON content type. |
| 4 | No prepared statements / binding | List: query builder (`where` / optional `like` / `join`) so values are escaped, not concatenated into SQL. Update: builder `update` / `where` as before. |
| 5 | No PHP types / strict types | `declare(strict_types=1)`, `void` return types, `final` class, typed `jsonResponse` payload. |
