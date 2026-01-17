## Inventory System — MVP Brief / Spec

### Goal

Home inventory system with real consumption tracking, batch rotation (FIFO/FEFO), multi-location support, and basic price insights.

---

## Core Concepts

- **Ledger-based inventory** (no mutable stock counters)
- **Batches** for rotation and expiration
- **Observed consumption** derived from real usage
- **Locations** (home, apartment, etc.)

---

## Tables & Relationships

### locations

- `id`
- `name`
- `is_default`
- `notes`

---

### products

- `id`
- `name`
- `brand`
- `category`
- `unit`
- `typical_size`
- `consumption_type`
- `created_at`

---

### batches

- `id`
- `product_id` → products
- `location_id` → locations
- `quantity`
- `unit_size`
- `purchased_at`
- `expires_at` (nullable)
- `price_total`
- `price_per_unit`
- `store`
- `is_emergency_stock`
- `notes`

---

### inventory_movements (ledger)

- `id`
- `occurred_at`
- `product_id` → products
- `location_id` → locations
- `direction` (`IN` | `OUT`)
- `quantity`
- `reason` (`PURCHASE`, `CONSUMPTION`, `WASTE`, `DONATION`, `ADJUSTMENT`, `TRANSFER`)
- `notes`

---

### movement_allocations

- `id`
- `movement_id` → inventory_movements
- `batch_id` → batches
- `quantity`

---

### consumption_profiles

- `id`
- `product_id` → products
- `estimated_duration_days`
- `confidence_level`
- `based_on` (`estimation` | `observed`)
- `last_reviewed_at`

---

## Derived Data (not stored)

- **Stock** = Σ(IN) − Σ(OUT) per product / batch / location
- **Coverage (days)** = stock × effective_duration_days
- **Effective duration** = observed avg if ≥ N samples, else estimated
- **Expiration risk** = batch.expires_at < today + coverage
- **Price trends** from batch history

---

## Core Features (MVP)

- Register purchases (IN + batch)
- Register consumption (OUT), auto-allocated via FIFO/FEFO
- Multi-location inventory
- Batch expiration tracking
- Observed consumption calculation (rolling average)
- Coverage estimation per product/location
- Emergency stock isolation
- Basic price trend insights
- Transfer between locations via paired movements

---

## Design Principles

- Append-only ledger
- No mutable stock counters
- Deterministic allocation
- Low-friction daily usage
- Accurate enough for decisions, simple enough to maintain
