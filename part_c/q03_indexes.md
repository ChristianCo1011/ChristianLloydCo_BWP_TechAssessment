# Part C — Q3: Indexing the slow query

```sql
SELECT e.*, a.unit_number
FROM eois e
INNER JOIN apartments a ON a.id = e.apartment_id
WHERE e.status = 'active'
  AND e.created_at >= '2026-01-01'
ORDER BY e.created_at DESC
LIMIT 50;
```

**Add a composite index on `eois (status, created_at)`** (in that order).

`status` is an equality filter, so it belongs first (leftmost-prefix). `created_at` is both the range (`>= '2026-01-01'`) and the sort key; inside the `status = 'active'` slice the index is ordered by `created_at`, so MySQL 8 can often satisfy `ORDER BY created_at DESC` from the index (e.g. backward scan) and stop after `LIMIT 50` instead of scanning the whole table and filesorting. The join to `apartments` stays cheap because `a.id` is the primary key.
