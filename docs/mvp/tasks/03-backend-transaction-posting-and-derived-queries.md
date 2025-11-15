## Backend Transaction Posting & Derived Query Services

- [ ] **Transaction posting service**
  - [ ] Create a dedicated service class (e.g. `PostLedgerTransactionService`) to encapsulate the posting flow.
  - [ ] Define an input DTO/structure that includes:
    - [ ] `user_id`, transaction meta (`description`, `effective_at`, `posted_at`, `reference`, `source`, `idempotency_key`).
    - [ ] A collection of entries: `account_id`, `category_id` (optional), `amount`, `currency_code`, `amount_base` (optional), `memo` (optional).
  - [ ] Implement the posting flow inside a database transaction:
    - [ ] Create the `ledger_transactions` record.
    - [ ] Insert at least two `ledger_entries` linked to the transaction.
    - [ ] Validate double-entry rule: `SUM(amount) == 0`.
    - [ ] Validate non‑zero amounts and currency/user consistency.
    - [ ] Persist and return the created transaction and entries.
  - [ ] Ensure the operation is atomic and rolls back on any validation failure.

- [ ] **Idempotency handling**
  - [ ] Use `idempotency_key` per user to prevent duplicate posting of the same transaction (e.g. imports).
  - [ ] Define behavior when a duplicate `idempotency_key` is received (return existing transaction vs. error).

- [ ] **Query services for derived data**
  - [ ] Implement a query service or repository for account balances:
    - [ ] Use the SQL pattern from `docs/mvp.md` to compute balances from `ledger_entries` + `ledger_transactions`.
  - [ ] Implement a query service for income statement:
    - [ ] Use account `type` logic to compute `total_income`, `total_expense`, and `net_income`.
  - [ ] Implement a query service for budget status per period:
    - [ ] Use the budget SQL example (joining budgets, budget_allocations, categories, ledger_entries, ledger_transactions).
    - [ ] Ensure month filtering uses `b.period` (`YYYY-MM`) and `effective_at`.
    - [ ] Add reusable subselects (or `with*` aggregates) that compute the accumulated spent amount per budget so that lists of budgets can be eager‑loaded with this derived property.


