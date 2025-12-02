## Backend Integrity Rules & Validation DONE

- [ ] **Database-level integrity rules**
    - [ ] Ensure `amount <> 0` at the database or application level for `ledger_entries`.
    - [ ] Enforce that `currency_code` in `ledger_entries` matches the related `ledger_account` currency (via constraints or application validation).
    - [ ] Ensure all related accounts and transactions for a user share the same `user_id`.
    - [ ] Consider database constraints (FKs, checks) where possible to align with the MVP integrity rules.

- [ ] **Application-level validation**
    - [ ] Create Form Request classes for creating/updating transactions and accounts.
    - [ ] Validate:
        - [ ] At least two entries per transaction.
        - [ ] `SUM(amount) == 0` on the submitted entries.
        - [ ] Non‑zero amount per entry.
        - [ ] All accounts and categories belong to the target user (`user_id`).
        - [ ] Currency consistency with related account.
