# 🎯 **Objetivo del MVP Contable**

Construir un sistema de finanzas personales con **doble partida real** y rastreo completo de todas las operaciones.

## **Principios base**

- Cada evento económico se registra como **una transacción con al menos dos partidas (entries)**.
    - **Nunca** debe existir una transacción con una sola partida.
    - Siempre debe cumplirse: `SUM(amount) = 0`.

- Todo monto registrado debe poder **rastrearse de extremo a extremo**:
    - Desde la transacción → hasta cada cuenta afectada → hasta los saldos y reportes.

- Ninguna cuenta guarda su saldo directamente; **los saldos se derivan** de las partidas.

- No se deben crear tablas ni columnas de saldos acumulados; todos los saldos, totales y métricas se derivan de `ledger_entries` + `ledger_transactions`.

---

# 🧱 **Núcleo de datos (MVP)**

---

### **Tenancy y alcance de datos**

- Estas son **finanzas personales**, por lo que todo el modelo de datos está orientado a un usuario individual.
- El **tenancy se define por `user_id`**: toda entidad funcional (cuentas, transacciones, partidas, categorías, presupuestos, etc.) pertenece siempre a un único usuario a través de su `user_id`.
- No hay recursos compartidos entre usuarios; todas las consultas de dominio deben filtrar explícitamente por `user_id`.

---

### **currencies**

| Campo     | Tipo | Notas     |
| --------- | ---- | --------- |
| code      | PK   | Ej. “USD” |
| precision | int  | Ej. 2     |

---

### **ledger_accounts**

| Campo         | Tipo                                                  | Notas |
| ------------- | ----------------------------------------------------- | ----- |
| id            | PK                                                    |       |
| user_id       | FK → users                                            |       |
| name          | string                                                |       |
| type          | ENUM(`ASSET`,`LIABILITY`,`INCOME`,`EXPENSE`,`EQUITY`) |       |
| currency_code | FK → currencies                                       |       |
| is_archived   | bool default false                                    |       |
| Índices       | `(user_id, type)`, `(user_id, name) UNIQUE`           |       |

---

### **ledger_transactions**

| Campo           | Tipo                                                         | Notas                  |
| --------------- | ------------------------------------------------------------ | ---------------------- |
| id              | PK                                                           |                        |
| user_id         | FK → users                                                   |                        |
| description     | string                                                       |                        |
| effective_at    | date/datetime                                                | manda en reportes      |
| posted_at       | nullable date                                                | fecha real de banco    |
| reference       | nullable string                                              |                        |
| source          | nullable string                                              | Ej. “manual”, “import” |
| idempotency_key | nullable string UNIQUE por usuario                           | evita duplicados       |
| Índices         | `(user_id, effective_at DESC)`, `(user_id, idempotency_key)` |                        |

---

### **ledger_entries**

| Campo          | Tipo                                                            | Notas                             |
| -------------- | --------------------------------------------------------------- | --------------------------------- |
| id             | PK                                                              |                                   |
| transaction_id | FK → ledger_transactions                                        |                                   |
| account_id     | FK → ledger_accounts                                            |                                   |
| category_id    | FK → categories (nullable)                                      | Solo para ingresos/gastos         |
| amount         | decimal                                                         | con signo; en moneda de la cuenta |
| currency_code  | FK → currencies                                                 | debe coincidir con la cuenta      |
| amount_base    | decimal (opcional)                                              | para multimoneda                  |
| memo           | nullable string                                                 |                                   |
| Índices        | `(account_id, transaction_id)`, `(category_id, transaction_id)` |                                   |

---

### **categories**

| Campo       | Tipo                       | Notas               |
| ----------- | -------------------------- | ------------------- |
| id          | PK                         |                     |
| user_id     | FK → users                 |                     |
| parent_id   | FK → categories (nullable) | Para sub-categorías |
| name        | string                     |                     |
| type        | ENUM(`INCOME`, `EXPENSE`)  |                     |
| is_archived | bool default false         |                     |
| Índices     | `(user_id, name) UNIQUE`   |                     |

---

# 💰 Módulo de Presupuestos

Este módulo permite asignar montos esperados de gasto o ingreso a las categorías para un período determinado. El seguimiento es un cálculo derivado de las transacciones, no un valor almacenado.

Los presupuestos deben interpretarse siempre como **mensuales** y con **reset completo cada mes**: los saldos presupuestarios no se arrastran entre períodos.

### **budgets**

| Campo   | Tipo                | Notas                               |
| ------- | ------------------- | ----------------------------------- |
| id      | PK                  |                                     |
| user_id | FK → users          |                                     |
| name    | string              | Ej: "Presupuesto Mensual"           |
| period  | `YYYY-MM` (varchar) | Período de vigencia (ej. "2025-11") |
| Índices | `(user_id, period)` | UNIQUE                              |

Reglas de diseño para presupuestos:

- Todos los presupuestos son **mensuales** (`period = 'YYYY-MM'`).
- El estado del presupuesto (presupuestado, gastado, restante) se calcula siempre a partir de `ledger_entries` + `ledger_transactions` para ese período; no se guardan saldos ni acumulados.
- Un “presupuesto recurrente mensual” se implementa copiando el presupuesto de un mes a otro (mismo `user_id` y `name`, distinto `period`). La recurrencia se maneja en la lógica de la aplicación, no en el esquema de la base de datos.

### **budget_allocations**

Detalla el monto asignado a cada categoría dentro de un presupuesto.

| Campo         | Tipo            | Notas                                 |
| ------------- | --------------- | ------------------------------------- |
| id            | PK              |                                       |
| budget_id     | FK → budgets    |                                       |
| category_id   | FK → categories |                                       |
| amount        | decimal         | Monto presupuestado para la categoría |
| currency_code | FK → currencies |                                       |

---

### **cached_aggregates**

This table stores pre-computed aggregate metrics for any model using a polymorphic relationship (for example, budgets, accounts, users).

| Campo             | Tipo      | Notas                                                            |
| ----------------- | --------- | ---------------------------------------------------------------- |
| id                | PK        |                                                                  |
| aggregatable_type | string    | Morph type to the related model (e.g. `App\Models\Budget`)       |
| aggregatable_id   | bigint    | Morph id to the related model                                    |
| key               | string    | Metric key (e.g. `current_balance`, `spent`)                     |
| scope             | string    | Optional scope/period (e.g. `2025-11`, `daily`, `category:food`) |
| value_decimal     | decimal   | Optional decimal value (e.g. money amounts)                      |
| value_int         | bigint    | Optional integer value                                           |
| value_json        | json      | Optional structured aggregate payload                            |
| created_at        | datetime  |                                                                  |
| updated_at        | datetime  |                                                                  |
| Índices           | composite | Index on `(aggregatable_type, aggregatable_id, key, scope)`      |

Design note:

- In PHP we will use enums to represent allowed `key` (and potentially `scope`) values, but the database column type will remain string, so that extending the enum does not require a schema change.

---

# ⚖️ **Reglas duras de integridad**

1. **Doble partida garantizada**
    - Por `transaction_id`: `SUM(amount) = 0`
    - Cada `ledger_transaction` debe tener **mínimo 2 ledger_entries**.

2. **Validaciones lógicas**
    - `amount <> 0`
    - `ledger_entries.currency_code = ledger_accounts.currency_code`
    - Todas las cuentas deben pertenecer al mismo `user_id`

3. **Integridad temporal**
    - `effective_at` obligatorio
    - `posted_at` opcional

---

# 🔁 **Flujo para postear una transacción (atomicidad)**

1. Crear registro en `ledger_transactions`
2. Insertar **al menos 2** `ledger_entries`
3. Validar:
    - Doble partida: `SUM(amount) == 0`
    - Mismo usuario y moneda
    - Monto no nulo

4. Confirmar (commit)
5. Registrar `idempotency_key` (opcional)
6. Devolver IDs de transacción y partidas

> ⚠️ Toda transacción que no tenga al menos 2 partidas o no cumpla `SUM(amount)=0` debe **fallar y revertirse**.

---

# 📊 **Consultas clave**

### **Saldo por cuenta**

```sql
SELECT a.id, a.name, SUM(e.amount) AS balance
FROM ledger_accounts a
JOIN ledger_entries e ON e.account_id = a.id
JOIN ledger_transactions t ON t.id = e.transaction_id
WHERE a.user_id = :user
  AND t.effective_at <= :as_of
GROUP BY a.id, a.name;
```

### **Estado de resultados**

```sql
SELECT
  SUM(CASE WHEN a.type='INCOME' THEN -e.amount ELSE 0 END) AS total_income,
  SUM(CASE WHEN a.type='EXPENSE' THEN  e.amount ELSE 0 END) AS total_expense,
  SUM(CASE
        WHEN a.type='INCOME' THEN -e.amount
        WHEN a.type='EXPENSE' THEN  e.amount
        ELSE 0 END) AS net_income
FROM ledger_entries e
JOIN ledger_accounts a ON a.id = e.account_id
JOIN ledger_transactions t ON t.id = e.transaction_id
WHERE a.user_id = :user
  AND t.effective_at BETWEEN :start AND :end;
```

### **Estado de Presupuesto por Período**

```sql
SELECT
  c.name AS category,
  ba.amount AS budgeted,
  COALESCE(SUM(e.amount), 0) AS spent,
  (ba.amount - COALESCE(SUM(e.amount), 0)) AS remaining
FROM budgets b
JOIN budget_allocations ba ON ba.budget_id = b.id
JOIN categories c ON c.id = ba.category_id
LEFT JOIN ledger_entries e ON e.category_id = c.id
LEFT JOIN ledger_transactions t ON t.id = e.transaction_id AND
    -- Filtra transacciones dentro del mes del presupuesto
    DATE_FORMAT(t.effective_at, '%Y-%m') = b.period
WHERE b.user_id = :user
  AND b.period = :period -- Ej: '2025-11'
  AND c.type = 'EXPENSE'
GROUP BY c.name, ba.amount;
```

---

# 🧩 **Regla de oro del diseño**

> “**Cada transacción tiene dos lados y dos registros.**
> Si algo no genera al menos dos líneas, no es una transacción contable válida.”

Esto asegura:

- **Simetría total** (todas las operaciones cuadran).
- **Trazabilidad completa** (todo se puede auditar hacia atrás).
- **Consistencia histórica** (no hay saldos fantasmas).

---
