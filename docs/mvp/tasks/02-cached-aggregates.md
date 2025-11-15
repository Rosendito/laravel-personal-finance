## Cached Aggregates

- [x] **Define cached aggregates schema**
    - [x] Create migration for `cached_aggregates` with fields:
        - [x] `id`
        - [x] `aggregatable_type` (string)
        - [x] `aggregatable_id` (unsigned big integer)
        - [x] `key` (string) — metric key such as `current_balance`, `spent`
        - [x] `scope` (string, nullable) — optional scope/period such as `2025-11`, `daily`, `category:food`
        - [x] `value_decimal` (decimal(20,4), nullable)
        - [x] `value_int` (bigInteger, nullable)
        - [x] `value_json` (json, nullable)
        - [x] Timestamps
    - [x] Add composite index `cached_agg_lookup` on `aggregatable_type`, `aggregatable_id`, `key`, `scope`.

- [x] **Create CachedAggregate model and relationships**
    - [x] Implement `CachedAggregate` Eloquent model with a `morphTo('aggregatable')` relationship.
    - [x] Add `aggregates()` morphMany relationship on `Budget` (and any other models that should have aggregates).
    - [x] Add a convenience relationship on `Budget` for the precomputed current balance, for example:
        - [x] `currentBalanceAggregate()` using `morphOne` filtered by `key = 'current_balance'` and `scope = null`.

- [x] **Enums for aggregate keys and scopes (string-backed)**
    - [x] Create a PHP enum for cached aggregate keys (e.g. `CachedAggregateKey`) including values like `current_balance`, `spent`.
    - [x] (Optional) Create a PHP enum for common scopes (e.g. `CachedAggregateScope`) for patterns like `monthly`, `daily`.
    - [x] Ensure the database columns (`key`, `scope`) remain `string` types, not native database enums, so extending the enums does not require schema changes.

- [x] **Usage notes**
    - [x] Document how cached aggregates are written/refreshed (e.g. via jobs or domain services) and that they are **derived data**, not the source of truth (which remains `ledger_entries` + `ledger_transactions`).

> Cached aggregates are populated exclusively by domain services or queued jobs that recompute metrics from `ledger_transactions` and `ledger_entries`. When a ledger event changes (new transaction, update, or delete), enqueue a refresh job per affected aggregatable model (for example, a `BudgetAggregateRefresher`). Each job should recalculate the desired metric, upsert the matching row (identified by `aggregatable_type`, `aggregatable_id`, `key`, `scope`), and never trust cached values as authoritative data. Views and reports must always treat the aggregates as read-only accelerators—if a metric is missing or stale, fall back to live calculations sourced from the ledger tables.
