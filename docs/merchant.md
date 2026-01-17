## Merchant pricing module (prompt)

Implement the Merchant/Pricing module described below.

### Non-negotiable requirements

- Generate **Eloquent models + migrations + factories** for the tables described here.
- Add **Pest unit tests** for the models under **`tests/Unit/Models/`** (relationship definitions, casts, and key invariants).
    - Each test file must contain exactly **one** `describe()`.
- Use **PHP Enums** for domain concepts (e.g. `entry_method`, `merchant_type`) but:
    - **Do NOT use database ENUM columns.** In migrations, store them as **`string` with an explicit max length**.
    - Enums must live under **`app/Enums`** (namespace `App\Enums\...`).
    - In models, cast those string columns to the corresponding Enum (via `casts()`).
- **You must reuse the existing Address model** at `app/Models/Address.php`.
    - Do not create a new address model/table for merchants.
    - For merchant locations, use a **polymorphic one-to-one** address relationship (`morphOne`), not `morphMany`.
    - The domain rule is: **one address per location** (do not allow multiple `addresses` rows for the same location).

### Enums (code-only; stored as strings in DB)

- `merchant_type` values: `store`, `marketplace`, `other`
- `entry_method` values: `manual`, `scraped`

### Data model (tables)

#### `merchant_merchants`

Represents a merchant (store / marketplace / other).

- `id`
- `name` (string)
- `merchant_type` (string, max 20; cast to `App\Enums\...`)
- `base_url` (nullable, string)
- `notes` (nullable, text)
- `created_at`, `updated_at`

Indexes / constraints:

- Consider a unique index if your domain requires it (e.g. unique `name`), otherwise keep it non-unique.

#### `merchant_locations`

Represents a single merchant location (e.g. a branch). A location has **exactly one** address via `addresses.addressable`.

- `id`
- `merchant_id` (FK → `merchant_merchants`)
- `name` (string)
- `created_at`, `updated_at`

Addressing requirements:

- Use `app/Models/Address.php` as the address model.
- `MerchantLocation` must expose `address(): MorphOne` (one address per location).
- Application logic must ensure you never end up with more than one `addresses` row per location.

#### `merchant_product_listings`

Represents how a product appears in a merchant catalog (adapter between external data and your canonical product).

- `id`
- `merchant_id` (FK → `merchant_merchants`)
- `merchant_location_id` (nullable FK → `merchant_locations`)
- `product_id` (nullable FK → `products`)
- `external_id` (nullable, string; the merchant's identifier)
- `external_url` (nullable, string)
- `title` (string)
- `brand_raw` (nullable, string)
- `size_value` (nullable, decimal/string as you deem appropriate for parsing)
- `size_unit` (nullable, string)
- `pack_quantity` (nullable, integer)
- `is_active` (boolean, default true)
- `last_seen_at` (nullable, datetime)
- `metadata` (nullable, json)
- `created_at`, `updated_at`

Indexes / constraints:

- Add the obvious indexes for lookups (e.g. `merchant_id`, `merchant_location_id`, `product_id`, `external_id`).

#### `merchant_product_prices`

Append-only price history for a listing.

- `id`
- `merchant_product_listing_id` (FK → `merchant_product_listings`)
- `observed_at` (datetime)
- `entry_method` (string, max 20; cast to `App\Enums\...`)
- `currency` (string, length 3; ISO-4217)
- `price_regular` (nullable, integer/decimal; pick one and be consistent across the app)
- `price_current` (integer/decimal; pick one and be consistent across the app)
- `is_promo` (boolean, default false)
- `promo_type` (nullable, string)
- `promo_description` (nullable, string)
- `tax_included` (nullable, boolean)
- `stock_status` (nullable, string)
- `raw_payload` (nullable, json)
- `created_at`

Indexes / constraints:

- Add indexes for time-based queries (e.g. `merchant_product_listing_id`, `observed_at`).

Append-only requirement:

- Do not update existing price rows during normal flows. Insert a new row per observation.
