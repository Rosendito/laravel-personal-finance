## Backend Domain & Migrations (done)

- [ ] **Review MVP domain specification**
    - [ ] Read `docs/mvp.md` and extract all required tables, fields, and constraints.
    - [ ] Confirm that every domain entity is tenant‑scoped by `user_id` as described.

- [ ] **Define database schema for core tables**
    - [ ] Create migration for `currencies` with fields: `code` (PK), `precision` (int).
    - [ ] Create migration for `ledger_accounts` with fields and indexes:
        - [ ] `id`, `user_id`, `name`, `type` (`ASSET`,`LIABILITY`,`INCOME`,`EXPENSE`,`EQUITY`), `currency_code`, `is_archived`.
        - [ ] Indexes: `(user_id, type)`, `(user_id, name)` unique.
    - [ ] Create migration for `ledger_transactions`:
        - [ ] `id`, `user_id`, `description`, `effective_at`, `posted_at` (nullable), `reference` (nullable), `source` (nullable), `idempotency_key` (nullable).
        - [ ] Indexes: `(user_id, effective_at DESC)`, `(user_id, idempotency_key)` (unique per user).
    - [ ] Create migration for `ledger_entries`:
        - [ ] `id`, `transaction_id`, `account_id`, `category_id` (nullable), `amount`, `currency_code`, `amount_base` (nullable), `memo` (nullable).
        - [ ] Indexes: `(account_id, transaction_id)`, `(category_id, transaction_id)`.
    - [ ] Create migration for `categories`:
        - [ ] `id`, `user_id`, `parent_id` (nullable), `name`, `type` (`INCOME`,`EXPENSE`), `is_archived`.
        - [ ] Index: `(user_id, name)` unique.
    - [ ] Create migration for `budgets`:
    - [ ] `id`, `user_id`, `name`, `period` (`YYYY-MM` string), `is_active` (boolean, default true).
    - [ ] Unique index: `(user_id, period, name)` or `(user_id, period)` according to final design.
    - [ ] Create migration for `budget_allocations`:
        - [ ] `id`, `budget_id`, `category_id`, `amount`, `currency_code`.

- [ ] **Create Eloquent models and relationships**
    - [ ] Implement `Currency` model with relationship(s) if needed.
    - [ ] Implement `LedgerAccount` model:
        - [ ] Belongs to `User`.
        - [ ] Has many `LedgerEntry`.
    - [ ] Implement `LedgerTransaction` model:
        - [ ] Belongs to `User`.
        - [ ] Has many `LedgerEntry`.
    - [ ] Implement `LedgerEntry` model:
        - [ ] Belongs to `LedgerTransaction`, `LedgerAccount`, and optional `Category`.
    - [ ] Implement `Category` model:
        - [ ] Belongs to `User`.
        - [ ] Self‑referencing parent/children relationship.
        - [ ] Has many `LedgerEntry`.
    - [ ] Implement `Budget` model:
        - [ ] Belongs to `User`.
        - [ ] Has many `BudgetAllocation`.
    - [ ] Implement `BudgetAllocation` model:
        - [ ] Belongs to `Budget`.
        - [ ] Belongs to `Category`.

- [ ] **Configure model casts and enums**
    - [ ] Create PHP enums for account `type` and category `type`.
    - [ ] Configure models to cast `type`, `effective_at`, `posted_at`, and any other relevant fields.

- [ ] **Seeders and factories**
    - [ ] Add factories for all new models with sensible defaults.
    - [ ] Create seeders for base `currencies` and an example user dataset (accounts, categories, budgets).
