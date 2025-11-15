## Cached Aggregates

- [ ] **Define cached aggregates schema**
    - [ ] Create migration for `cached_aggregates` with fields:
        - [ ] `id`
        - [ ] `aggregatable_type` (string)
        - [ ] `aggregatable_id` (unsigned big integer)
        - [ ] `key` (string) — metric key such as `current_balance`, `spent_this_month`
        - [ ] `scope` (string, nullable) — optional scope/period such as `2025-11`, `daily`, `category:food`
        - [ ] `value_decimal` (decimal(20,4), nullable)
        - [ ] `value_int` (bigInteger, nullable)
        - [ ] `value_json` (json, nullable)
        - [ ] Timestamps
    - [ ] Add composite index `cached_agg_lookup` on `aggregatable_type`, `aggregatable_id`, `key`, `scope`.

- [ ] **Create CachedAggregate model and relationships**
    - [ ] Implement `CachedAggregate` Eloquent model with a `morphTo('aggregatable')` relationship.
    - [ ] Add `aggregates()` morphMany relationship on `Budget` (and any other models that should have aggregates).
    - [ ] Add a convenience relationship on `Budget` for the precomputed current balance, for example:
        - [ ] `currentBalanceAggregate()` using `morphOne` filtered by `key = 'current_balance'` and `scope = null`.

- [ ] **Enums for aggregate keys and scopes (string-backed)**
    - [ ] Create a PHP enum for cached aggregate keys (e.g. `CachedAggregateKey`) including values like `current_balance`, `spent_this_month`.
    - [ ] (Optional) Create a PHP enum for common scopes (e.g. `CachedAggregateScope`) for patterns like `monthly`, `daily`.
    - [ ] Ensure the database columns (`key`, `scope`) remain `string` types, not native database enums, so extending the enums does not require schema changes.

- [ ] **Usage notes**
    - [ ] Document how cached aggregates are written/refreshed (e.g. via jobs or domain services) and that they are **derived data**, not the source of truth (which remains `ledger_entries` + `ledger_transactions`).
