## Frontend CRUDs & Management Screens

- [ ] **Accounts CRUD**
    - [ ] Build an accounts list page showing name, type, currency, and current balance (derived, not stored).
    - [ ] Implement create/edit account forms using shadcn form components.
    - [ ] Allow archiving/unarchiving accounts.

- [ ] **Categories CRUD**
    - [ ] Build a categories list (with hierarchy for parent/child categories).
    - [ ] Implement create/edit forms for income and expense categories.
    - [ ] Allow archiving categories.

- [ ] **Transactions CRUD (beyond quick expense)**
    - [ ] Implement a full transactions list with:
        - [ ] Date (`effective_at`), description, amount summary, and related accounts/categories.
        - [ ] Filters by date range, account, and category.
    - [ ] Build a transaction creation/edit form:
        - [ ] Allow adding multiple entries (rows) with account, category (optional), amount, and memo.
        - [ ] Display validation aligned with double-entry rules (at least two lines, `SUM(amount) == 0`, non‑zero amounts).

- [ ] **Income registration flow**
    - [ ] Provide a simple UI to register income transactions (similar to the quick expense, but for income).
    - [ ] Ensure income is categorized properly and uses the correct account types.

- [ ] **Budgets CRUD**
    - [ ] Build a budgets management screen listing monthly recurring budgets (`period`, `name`, and `is_active`).
    - [ ] Implement create/edit budget forms:
        - [ ] Manage allocations per category (amount and currency).
        - [ ] Allow toggling `is_active` to enable or disable a recurring budget without deleting it.
    - [ ] Link from budget rows to the budget detail view used in the dashboard widget (spent and remaining based on backend subselects).
