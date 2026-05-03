-- =============================================================================
-- BWP Software Engineer Technical Assessment - Part C answers
-- =============================================================================
-- Target: MySQL 8.x (uses CTEs and window functions in Q7).
-- Run after the bwp_assessment schema exists with the five Part C tables and the
-- reference seed loaded (DDL/seed from your assessment materials — not shipped in this repo).
--
-- Each block below is delimited by `-- ====== Qn ======` and ends with a
-- line of dashes. Highlight a block and press Ctrl+Shift+Enter in MySQL
-- Workbench to execute just that statement.
-- Question 3 is prose-only; see q03_indexes.md.
-- =============================================================================

USE bwp_assessment;


-- ====== Q1 ======
-- JOIN: each EOI in status 'active' or 'pending', joined up to project.
-- Expected (4 rows on the seed, ordered by eoi.created_at DESC):
--   eoi 2 (Bob Tan, pending, 102, Tower A, Sunset Residences, SUNSET)
--   eoi 5 (Dana Fox, active, L8, Stage 1, Ridge Estate, RIDGE)
--   eoi 1 (Alice Lee, active, 101, Tower A, Sunset Residences, SUNSET)
--   eoi 3 (Alice Lee, active, L7, Stage 1, Ridge Estate, RIDGE)
SELECT
    e.id AS eoi_id,
    e.client_name AS client_name,
    e.status AS eoi_status,
    e.commission_amount AS commission_amount,
    a.unit_number AS unit_number,
    pr.name AS precinct_name,
    p.name AS project_name,
    p.code AS project_code
FROM eois e
INNER JOIN apartments a ON a.id = e.apartment_id
INNER JOIN precincts pr ON pr.id = a.precinct_id
INNER JOIN projects p ON p.id = pr.project_id
WHERE e.status IN ('active', 'pending')
ORDER BY e.created_at DESC;
-- ----------------------------------------------------------------------------


-- ====== Q2 ======
-- Total commission per project for EOIs created in calendar year 2026.
-- Projects with zero commission in 2026 are included (LEFT JOIN + COALESCE).
-- Assumption: zero-commission projects are shown as 0.00 (not NULL) to make
-- the column easy to sort and aggregate downstream.
-- Expected (seed):
--   SUNSET = 8000.00 (eois 1 + 2)
--   RIDGE  = 3000.00 (eois 4 + 5)
--   HBR1   = 0.00    (no EOIs at all)
SELECT
    p.code AS project_code,
    COALESCE(SUM(
        CASE
            WHEN e.created_at >= '2026-01-01' AND e.created_at < '2027-01-01'
            THEN e.commission_amount
        END
    ), 0) AS total_commission
FROM projects p
LEFT JOIN precincts pr ON pr.project_id = p.id
LEFT JOIN apartments a ON a.precinct_id = pr.id
LEFT JOIN eois e ON e.apartment_id = a.id
GROUP BY p.id, p.code;
-- Note: filtering created_at inside CASE (not WHERE) preserves projects with
-- no matching EOIs; using a half-open range avoids YEAR(e.created_at) which
-- would prevent index use on a real-world dataset.
-- ----------------------------------------------------------------------------


-- ====== Q3 ======
-- See q03_indexes.md (prose answer; no screenshot required by the brief).
-- ----------------------------------------------------------------------------


-- ====== Q4 ======
-- Anti-join: apartments that have NEVER appeared in eois.
-- Expected (seed): apartments 6 (P1, Podium, Harbour One)
--                  and 3 (201, Tower B, Sunset Residences),
-- ordered by project name then unit_number.
SELECT
    a.id,
    a.unit_number AS unit_number,
    pr.name AS precinct_name,
    p.name AS project_name
FROM apartments a
INNER JOIN precincts pr ON pr.id = a.precinct_id
INNER JOIN projects p ON p.id = pr.project_id
WHERE NOT EXISTS (
    SELECT 1
    FROM eois e
    WHERE e.apartment_id = a.id
)
ORDER BY p.name, a.unit_number;
-- ----------------------------------------------------------------------------


-- ====== Q5 ======
-- COUNT(DISTINCT) of client_name across active EOIs per project.
-- Projects with zero active EOIs are included with 0 (LEFT JOIN; the
-- status='active' filter is applied in the JOIN's ON clause so the
-- LEFT JOIN is preserved for projects with no matches).
-- Expected (seed):
--   SUNSET (Sunset Residences): 1  -- {Alice}
--   RIDGE  (Ridge Estate):      2  -- {Alice, Dana}
--   HBR1   (Harbour One):       0
SELECT
    p.code AS project_code,
    p.name AS project_name,
    COUNT(DISTINCT e.client_name) AS distinct_active_clients
FROM projects p
LEFT JOIN precincts pr ON pr.project_id = p.id
LEFT JOIN apartments a ON a.precinct_id = pr.id
LEFT JOIN eois e ON e.apartment_id = a.id AND e.status = 'active'
GROUP BY p.id, p.code, p.name
ORDER BY p.code;
-- ----------------------------------------------------------------------------


-- ====== Q6 ======
-- Open milestones (completed_at IS NULL) per EOI; only EOIs with at least one.
-- Expected (seed):
--   eoi 2 (Bob Tan):  2 open  (Deposit received + Cooling-off end)
--   eoi 1 (Alice Lee): 1 open (Finance approval)
--   eoi 4 (Chen Wei):  1 open (Final inspection)
SELECT
    e.id AS eoi_id,
    e.client_name AS client_name,
    COUNT(*) AS open_milestones
FROM eois e
INNER JOIN milestones m ON m.eoi_id = e.id AND m.completed_at IS NULL
GROUP BY e.id, e.client_name
ORDER BY open_milestones DESC, eoi_id;
-- ----------------------------------------------------------------------------


-- ====== Q7 ======
-- Window function: per active EOI, show project_total_commission, which is
-- SUM(commission_amount) over ALL EOIs (any status) in the same project.
-- Expected (seed, totals computed across all EOIs in the project):
--   SUNSET total = 5000 + 3000           = 8000
--   RIDGE  total = 7500 + 2000 + 1000    = 10500
--   HBR1   total = 0
-- Active EOI rows:
--   eoi 1 (5000, project 1): project_total_commission = 8000
--   eoi 3 (7500, project 2): project_total_commission = 10500
--   eoi 5 (1000, project 2): project_total_commission = 10500
WITH eoi_with_project AS (
    SELECT
        e.id AS eoi_id,
        e.status AS eoi_status,
        e.commission_amount AS commission_amount,
        p.id AS project_id,
        SUM(e.commission_amount) OVER (PARTITION BY p.id) AS project_total_commission
    FROM eois e
    INNER JOIN apartments a ON a.id = e.apartment_id
    INNER JOIN precincts pr ON pr.id = a.precinct_id
    INNER JOIN projects p ON p.id = pr.project_id
)
SELECT
    eoi_id,
    commission_amount,
    project_id,
    project_total_commission
FROM eoi_with_project
WHERE eoi_status = 'active'
ORDER BY project_id, eoi_id;
-- The CTE includes every EOI so the window's PARTITION BY project_id sees
-- all of them; we then filter active rows in the outer SELECT. Filtering
-- inside the CTE would have shrunk the partition and given wrong totals.
-- ----------------------------------------------------------------------------


-- ====== Q8 ======
-- Set apartments.status = 'reserved' for any apartment with at least one
-- pending EOI, except apartments that are already 'sold'.
-- Assumption: multiple pending EOIs per apartment are possible; the
-- DISTINCT in the subquery ensures the JOIN doesn't duplicate the update
-- target. Apartments already 'reserved' are still touched (idempotent
-- write); apartments already 'sold' are explicitly skipped.
-- Expected (seed): apartment 2 ('102') changes from 'available' to 'reserved'
--                  (it has eoi 2 pending). No other apartments are affected.
UPDATE apartments a
INNER JOIN (
    SELECT DISTINCT apartment_id
    FROM eois
    WHERE status = 'pending'
) pending_apts ON pending_apts.apartment_id = a.id
SET a.status = 'reserved'
WHERE a.status <> 'sold';

-- Verification query (run after the UPDATE above):
SELECT id, precinct_id, unit_number, status
FROM apartments
ORDER BY id;
-- ----------------------------------------------------------------------------
