# Personal Finance App — Agent Quick Guide

## Overview
Personal finance app built on double-entry bookkeeping.
Core flow: **transactions** (`ledger_transactions`) contain multiple **entries** (`ledger_entries`) that affect **accounts** (`ledger_accounts`).
Users organize spend via **categories** and **budgets** with periods.

---

## Entities / Models (role)

### User
Authenticated owner of all financial data.

### Currency
Currency catalog (`code`, `precision`).
Referenced by accounts and entries.

### LedgerAccount
Chart-of-accounts entry.
Key fields: `type` (ASSET/LIABILITY/INCOME/EXPENSE/EQUITY), `subtype` (for assets/liabilities), `currency_code`.
Represents cash, bank, debts, income/expense, etc.

### LedgerTransaction
Transaction header.
Fields: `description`, `effective_at`, `posted_at`, `source`, `idempotency_key`, `category_id`, `budget_period_id`.
Groups multiple entries to balance (double-entry).

### LedgerEntry
Individual line item.
Belongs to a `LedgerTransaction` and a `LedgerAccount`.
Fields: `amount`, `amount_base`, `currency_code`, `memo`.
Validates non-zero amount and currency matches the account.

### Category
Income/expense categories with hierarchy (parent/children).
Optional link to a `budget`, and a `type` (INCOME/EXPENSE).
Used to classify transactions.

### Budget
User budget.
Has multiple `BudgetPeriod` and a current one.

### BudgetPeriod
Budget window with `start_at`, `end_at`, `amount`.
Derived fields: `spent_amount`, `remaining_amount`, `usage_percent` via aggregates.

### CachedAggregate
Precomputed metrics (e.g., current balance, spent).
Polymorphic (`aggregatable`).

---

## Finance Enums

### CategoryType
- `INCOME`
- `EXPENSE`

### LedgerAccountType
- `ASSET`, `LIABILITY`, `INCOME`, `EXPENSE`, `EQUITY`
Defines account nature.

### LedgerAccountSubType
Subtypes for `ASSET` and `LIABILITY`:
- Liquid assets: `CASH`, `BANK`, `WALLET`
- Non-liquid assets: `LOAN_RECEIVABLE`, `INVESTMENT`
- Liabilities: `LOAN_PAYABLE`, `CREDIT_CARD`

### CachedAggregateKey
- `current_balance`
- `spent`

### CachedAggregateScope
- `monthly`
- `daily`

---

## Database (tables)

> Laravel infrastructure tables (users, cache, jobs) exist; finance domain focuses on:

### currencies
- `code` (PK), `precision`, timestamps
Currency catalog.

### ledger_accounts
- `id`, `user_id`, `name`, `type`, `subtype`, `currency_code`, `is_archived`, `is_fundamental`, timestamps
User accounts.

### budgets
- `id`, `user_id`, `name`, `is_active`, timestamps
User budgets.

### budget_periods
- `id`, `budget_id`, `start_at`, `end_at`, `amount`, timestamps
Budget periods.

### categories
- `id`, `user_id`, `parent_id`, `budget_id`, `name`, `type`, `is_archived`, timestamps
Income/expense categories, hierarchical.

### ledger_transactions
- `id`, `user_id`, `budget_period_id`, `category_id`, `description`, `effective_at`, `posted_at`, `reference`, `source`, `idempotency_key`, timestamps
Transaction headers.

### ledger_entries
- `id`, `transaction_id`, `account_id`, `amount`, `amount_base`, `currency_code`, `memo`, timestamps
Transaction entries.

### cached_aggregates
- `id`, `aggregatable_type`, `aggregatable_id`, `key`, `scope`, `value_decimal`, `value_int`, `value_json`, timestamps
Cached computed metrics.

---

## CRUDs / Panels (Filament)

### CRUDs
- **Accounts** (`LedgerAccountResource`)
- **Budgets** (`BudgetResource`)
- **Categories** (`CategoryResource`)
- **Transactions** (`LedgerTransactionResource`)

### Panel: Income / Expense / Transactions
Main `LedgerTransactionResource` view with tabs:
- **Expenses**: transactions tied to `EXPENSE` accounts.
- **Income**: transactions tied to `INCOME` accounts.
- **Transactions**: transfers (no INCOME/EXPENSE accounts).
- **All**: full list.

Widgets:
- **Account balances** (liquid accounts).
- **Active budgets** (current period stats).

Actions:
- Register income, register expense, transfer funds.

### Panel: Loans / Debts
Dedicated `liabilities` page:
- Filters by `LOAN_PAYABLE` / `LOAN_RECEIVABLE`.
- Widgets for loan/debt balances.
- Actions: register loan / register debt (collect/pay).
