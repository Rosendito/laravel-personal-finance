## Project Setup & Single-User Seeding

- [ ] **Local environment**
  - [ ] Install PHP, Composer, Node, and database server according to project requirements.
  - [ ] Install PHP dependencies with `composer install`.
  - [ ] Install JS dependencies with `npm install` or `pnpm install`.
  - [ ] Configure `.env` for local database and app URL.

- [ ] **Database bootstrap**
  - [ ] Run initial migrations for existing Laravel tables (`users`, jobs, cache, etc.).
  - [ ] Add migrations for the MVP ledger schema (see `01-backend-domain-and-migrations.md`).
  - [ ] Run all migrations to create the schema.

- [ ] **Single-user seeding**
  - [ ] Create a seeder that inserts a single main user (you).
  - [ ] Seed base `currencies` and a minimal set of accounts and categories for that user.
  - [ ] Optionally seed one or two example transactions and budgets to quickly see data in the UI.

- [ ] **App boot check**
  - [ ] Run the development server.
  - [ ] Verify that the app boots correctly and that the seeded data is present in the database.


