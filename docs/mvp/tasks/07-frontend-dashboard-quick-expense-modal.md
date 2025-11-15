## Frontend Dashboard Quick Expense Modal

- [ ] **Entry point and flow**
    - [ ] Add a prominent “Add expense” action on the dashboard (e.g. primary button in the header or floating button).
    - [ ] Ensure the quick expense modal can also be opened from the recent expenses widget.

- [ ] **Modal UX principles**
    - [ ] Optimize for minimal clicks and friction:
        - [ ] Focus the first input automatically (amount or category, based on preference).
        - [ ] Support keyboard navigation (tab order, enter to submit, escape to close).
    - [ ] Keep the form to the bare essentials for an expense:
        - [ ] Amount.
        - [ ] Category (required).
        - [ ] Account (defaults to a sensible last-used or primary account).
        - [ ] Effective date (defaults to today).
        - [ ] Optional short description.

- [ ] **Component design**
    - [ ] Use shadcn modal/dialog + form components with clear labels and error states.
    - [ ] Provide a compact layout that works well on both desktop and mobile widths.

- [ ] **Smart defaults and shortcuts**
    - [ ] Allow quick category selection (e.g. searchable select, recent categories, or shortcuts).
    - [ ] Remember last used account and category per session to reduce repeated choices.
    - [ ] Optionally support “duplicate last expense” flow to speed up repeated entries.

- [ ] **Integration with backend**
    - [ ] Wire the modal to the backend transaction posting endpoint for expenses.
    - [ ] On success:
        - [ ] Close the modal.
        - [ ] Optimistically update dashboard widgets (recent expenses, budgets, account balances) via Inertia partial reload or props refresh.
