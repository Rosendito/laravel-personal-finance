## Backend API, Routing & Inertia Endpoints

- [ ] **Set up Inertia in Laravel**
    - [ ] Configure Inertia middleware and root view according to Laravel 12 conventions.

- [ ] **Define routes for core resources**
    - [ ] Add routes for managing `ledger_accounts` (index, create, store, edit, update, archive).
    - [ ] Add routes for managing `categories` (index, create, store, edit, update, archive).
    - [ ] Add routes for listing and creating `ledger_transactions` and their entries.
    - [ ] Add routes for the budget module:
        - [ ] List budgets for a user.
        - [ ] Create/update a monthly budget and its allocations.

- [ ] **Controllers for domain operations**
  - [ ] Create controllers for accounts, categories, transactions, and budgets using Form Requests for validation.
    - [ ] Inject the transaction posting service into transaction controller actions.
  - [ ] Ensure all queries are scoped by the configured `user_id` (tenancy rule, even with a single seeded user).

- [ ] **Inertia responses**
    - [ ] For each core screen, return an Inertia view with:
    - [ ] Current user context (for the single seeded user).
        - [ ] Required domain data (accounts, categories, balances, budgets) via dedicated query services.
    - [ ] Implement pagination where appropriate for transactions.

- [ ] **Reporting endpoints**
    - [ ] Implement endpoints that expose:
        - [ ] Account balances as of a given date.
        - [ ] Income statement for a given period (date range).
        - [ ] Budget status for a given `period` (`YYYY-MM`).
    - [ ] Decide which reports are rendered server‑side versus consumed by React components via Inertia props.
