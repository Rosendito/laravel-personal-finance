# Sistema de Extracción de Tasas de Cambio (Exchange Rates)

## Objetivo Principal

Implementar un sistema robusto, escalable y testable para la extracción automatizada de tasas de cambio desde diversas fuentes externas (APIs, Scrapers). El sistema debe unificar la interfaz de consulta para permitir la fácil adición de nuevas fuentes en el futuro.

## Arquitectura del Sistema

### A. Contrato (Interface)

**`App\Contracts\ExchangeRates\ExchangeRateFetcher`**

- Método principal: `fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection`
- Retorno: `Illuminate\Support\Collection` de `App\Data\ExchangeRates\FetchedExchangeRateData`.
- Regla: el fetcher **puede** devolver más tasas (por eficiencia), pero **debe** cubrir al menos los pares solicitados **si los soporta**.

### B. Data Transfer Object (DTO)

**`App\Data\ExchangeRates\FetchedExchangeRateData`**

- `baseCurrency` (string), `quoteCurrency` (string), `rate` (float), `retrievedAt` (Carbon), `metadata` (array).

**`App\Data\ExchangeRates\RequestedPairsData`**

- DTO tipado para expresar “qué pares quiero”.
- Contiene una colección de `ExchangeCurrencyPair` (modelo) o `null` para indicar “todos los pares del source”.

### C. Orquestación

**`App\Actions\ExchangeRates\SyncExchangeRatesAction`**

- Resuelve el fetcher adecuado, ejecuta `fetch`, filtra a lo solicitado (si aplica) y persiste los resultados en `ExchangeRate`.
- Valida que los pares solicitados pertenezcan al `ExchangeSource` y existan en DB (`exchange_currency_pairs`). Si se pide un par no soportado: **lanza excepción**.

**`App\Jobs\ExchangeRates\SyncExchangeRatesJob`**

- Job en cola que ejecuta la acción.

### D. Resolver/Registry (Mapper fuente → fetcher)

**`App\Services\ExchangeRates\ExchangeRateFetcherResolver`** (o similar)

- Responsabilidad: mapear `ExchangeSource->key` a una implementación de `ExchangeRateFetcher`.
- No guarda ni filtra; solo resuelve.
- Evitar `switch`/`match` dentro de `SyncExchangeRatesAction`. El mapping vive en un Provider/Registry.

### E. Política de cálculo de tasa (Binance y fuentes con “múltiples quotes”)

Algunas fuentes (ej. Binance P2P) retornan múltiples “quotes/anuncios” para un mismo par y la regla para convertir eso en un único `rate` puede variar con el tiempo e incluso por `ExchangeCurrencyPair`.

**Objetivo**: mantener el contrato del fetcher devolviendo `FetchedExchangeRateData` (un rate final por par), pero permitir extender/cambiar la regla de cálculo sin duplicar lógica en el fetcher.

**`App\Contracts\ExchangeRates\RateCalculator`** (o similar)

- Responsabilidad: dado un conjunto de quotes normalizados y el par, calcular un único `rate`.
- Implementaciones ejemplo: `BestPriceRateCalculator`, `MedianRateCalculator`, `TrimmedMeanRateCalculator`, etc.

**`App\Services\ExchangeRates\RateCalculatorResolver`** (o similar)

- Responsabilidad: resolver el `RateCalculator` para un `ExchangeSource` + `ExchangeCurrencyPair`.
- La “capability” de pares soportados se define en DB (`exchange_currency_pairs`). La “política” de cálculo también se define en DB (por ejemplo, en una tabla o columnas/config asociada al par y fuente).
- Si no existe política explícita, se usa una default razonable por fuente (ej. Binance: best price).

**Evidencia en `metadata` (recomendado para trazabilidad/auditoría)**

- Incluir campos mínimos:
    - `strategy` (string): nombre/clave de la política usada (ej. `best_price`).
    - `sample_size` (int): cantidad de quotes consideradas tras filtros.
    - `min` (float), `max` (float), `median` (float|null) según aplique.
    - `filters` (array): filtros relevantes aplicados (ej. `min_available`, `max_spread`, etc.).
    - `source_payload_version` (string|int|null): útil si Binance cambia el schema.

---

## Plan de Ejecución Dividido

### Tarea 1: Scaffolding, Orquestación y Datos Base

**Objetivo**: Establecer los cimientos del sistema y asegurar que la base de datos tenga los registros necesarios (Sources y Pairs) mediante migraciones.

1.  **Contratos y DTOs**:
    - Crear `App\Data\ExchangeRates\FetchedExchangeRateData`.
    - Crear `App\Data\ExchangeRates\RequestedPairsData` (usa `ExchangeCurrencyPair` como identificador de par solicitado).
    - Crear `App\Contracts\ExchangeRates\ExchangeRateFetcher`.
    - Crear `App\Contracts\ExchangeRates\RateCalculator` y `App\Services\ExchangeRates\RateCalculatorResolver` (para fuentes con múltiples quotes).
2.  **Orquestación**:
    - Crear `App\Services\ExchangeRates\ExchangeRateFetcherResolver` (registry/mapper).
    - Crear `App\Actions\ExchangeRates\SyncExchangeRatesAction`:
        - Resuelve el fetcher con el resolver (sin `switch`).
        - Pasa `RequestedPairsData` al fetcher (o `null` para “todos”).
        - Filtra los resultados al set solicitado (si aplica).
        - Persiste únicamente lo solicitado (si aplica).
        - Lanza excepción si:
            - No existe fetcher para el source.
            - Se solicitan pares no soportados por el source (según DB).
    - Crear `App\Jobs\ExchangeRates\SyncExchangeRatesJob`.
3.  **Datos Base (Migraciones)**:
    - Crear una nueva migración para insertar los datos iniciales en `exchange_sources` y `exchange_currency_pairs` (NO usar Seeders).
    - Nota: las migraciones se consideran de ejecución única; no se busca idempotencia en esta inserción inicial.
    - **Sources**:
        - `BCV` (Type: `scraper`, Key: `bcv`, Name: `Banco Central de Venezuela`)
        - `Binance_P2P` (Type: `api`, Key: `binance_p2p`, Name: `Binance P2P`)
    - **Pairs**:
        - `USD/VES`, `EUR/VES` (Asociados a BCV).
        - `USDT/VES` (Asociado a Binance).
    - _Referencia_: Ver estructura en `database/migrations/2025_11_15_200507_create_currencies_table.php`.

### Tarea 2: Implementación Fetcher Banco Central (BCV)

**Objetivo**: Implementar la extracción de tasas del BCV mediante scraping.

1.  **Fetcher**:
    - Crear `App\Services\ExchangeRates\Fetchers\BcvRateFetcher`.
    - Implementar scraping de `bcv.org.ve` para obtener tasas USD y EUR en una sola petición.
2.  **Integración**:
    - Registrar `bcv` → `BcvRateFetcher` en el `ExchangeRateFetcherResolver` (vía Provider/Registry).
3.  **Testing**:
    - Crear `tests/Unit/Services/ExchangeRates/BcvRateFetcherTest.php`.
    - Usar `Http::fake()` con el fixture `tests/Fixtures/Html/venezuela-banco-central-rates.html`.

### Tarea 3: Implementación Fetcher Binance

**Objetivo**: Implementar la extracción de tasas de Binance reutilizando lógica existente pero adaptada al nuevo contrato.

1.  **Fetcher**:
    - Crear `App\Services\ExchangeRates\Fetchers\BinanceRateFetcher`.
    - Adaptar lógica de payload de `FetchBinanceRateAction` para cumplir con `ExchangeRateFetcher`.
    - El fetcher normaliza quotes/anuncios y delega el cálculo del `rate` a `RateCalculatorResolver` (por `source + pair`), devolviendo el rate final como `FetchedExchangeRateData` con evidencia en `metadata`.
2.  **Integración**:
    - Registrar `binance_p2p` → `BinanceRateFetcher` en el `ExchangeRateFetcherResolver`.
3.  **Testing**:
    - Crear `tests/Unit/Services/ExchangeRates/BinanceRateFetcherTest.php`.
    - Mockear respuesta JSON de API de Binance.

---

_Nota: Este documento sirve como especificación técnica para la implementación de la tarea._
