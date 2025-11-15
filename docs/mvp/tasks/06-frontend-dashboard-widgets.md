## Frontend Dashboard Widgets (Stats, Budgets, Accounts, Recent Expenses)

- [ ] **Dashboard shell**
    - [ ] Create a dedicated `Dashboard` page component wired via Inertia.
    - [ ] Place it inside the main layout so it reuses navigation and header components.

- [ ] **Key stats widgets**
    - [ ] Add a summary strip with key KPIs (e.g. net worth, total balances, month-to-date spending).
    - [ ] Design widgets as shadcn cards with clear typography and color cues.

- [ ] **Budgets overview widget**
    - [ ] Show a compact list of active monthly budgets with:
        - [ ] Name, period, and is_active indicator.
        - [ ] Budgeted, spent, and remaining amounts (using backend-derived values from subselects).
    - [ ] Use visual progress (bars or radial charts) to show budget consumption at a glance.

- [ ] **Accounts overview widget**
    - [ ] Display key accounts with their current balance (derived, not stored).
    - [ ] Highlight total assets vs liabilities if possible.

- [ ] **Recent expenses widget**
    - [ ] Show a list of the most recent expense transactions:
        - [ ] Date, category, account, short description, and amount.
    - [ ] Add a quick link or button to open the “quick expense” modal from this widget.
