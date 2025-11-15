# Personal Finance Ledger (MVP)

This is a personal finance application built on a **real double‑entry ledger**.  
Every economic event is recorded as a transaction with **at least two entries** and the sum of all entry amounts per transaction is always zero.

Key ideas:

- **Per‑user data**: all accounts, transactions, categories, budgets, and aggregates are strictly scoped by `user_id`.
- **Derived balances**: account balances, reports, and budget status are always computed from `ledger_entries` + `ledger_transactions` (no stored running balances).
- **Integrity rules**: each transaction must have at least two entries, `SUM(amount) = 0`, non‑zero amounts, consistent currencies, and a mandatory `effective_at`.

---

## Quick Start

### Requirements

- PHP 8.4+
- Composer
- Docker & Docker Compose
- Node.js + pnpm (for frontend dev)

### Local Setup

```bash
# 1) Install PHP dependencies
composer install

# 2) Copy and configure environment
cp .env.example .env
php artisan key:generate

# 3) Start services (database, etc.)
docker compose up -d

# 4) Run migrations and seed sample data
php artisan migrate --seed

# 5) (Optional) Install and run frontend dev server
pnpm install
pnpm dev
```

The app should now be ready for local development.
