¡De una, Simón! Aquí tienes un **roadmap 100% orientado a features** y la **estructura de datos** que los soporta. Sin hablar de tecnologías; tú eliges eso. Yo te doy el “qué” (features) y el “cómo” (modelo de datos y reglas).

# Núcleo de datos (base estable)

**Principios:** doble entrada real, fechas efectivas, multimoneda, inmutabilidad por reversos, idempotencia.

## Entidades imprescindibles

* **users**: dueños de los datos.
* **currencies**(code, precision).
* **institutions**(banco, exchange, tarjeta).
* **ledger_accounts**(user_id, name, type: ASSET/LIABILITY/INCOME/EXPENSE/EQUITY, currency_code, institution_id?).
* **categories**(user_id, name, type: INCOME/EXPENSE, parent_id?).
* **payees**(user_id, name) y **tags**(user_id, name) opcionales.
* **ledger_transactions**(user_id, description, payee_id?, reference?, effective_at, posted_at?, source, idempotency_key?, external_id?).
* **ledger_entries**(transaction_id, account_id, category_id?, amount, currency_code, fx_rate?, amount_base, memo?).
* **fx_rates**(base_currency, quote_currency, at_date, rate).
* **statements**(account_id, statement_start, statement_end, opening_balance, closing_balance).
* **reconciliations**(statement_id, transaction_id, matched_at).
* **attachments**(transaction_id, path, mime).
* **audit_logs**(user_id, entity, entity_id, action, before_json, after_json, occurred_at).

**Reglas clave (consistencia):**

* Por `transaction_id`: **SUM(amount_base) = 0** (o `SUM(amount)=0` si una sola moneda).
* `amount <> 0`.
* `effective_at` puede ser ≤ `posted_at`.
* Idempotencia: `
idempotency_key` único cuando no es nulo; `UNIQUE(user_id, external_id)` si el proveedor la garantiza.
* Índices:

  * `ledger_entries(account_id, effective_at)`
  * `ledger_entries(category_id, effective_at)`
  * `ledger_transactions(user_id, effective_at DESC)`

---

# Roadmap de features (por etapas)

## Etapa 1 — **MVP Contable**

**Qué obtiene el usuario**

* Crear cuentas (corriente, efectivo, tarjeta, préstamos).
* Registrar **gastos/ingresos** y **transferencias** (entre cuentas).
* **Splits** dentro de una transacción (ej: compra + propina).
* Listado de movimientos con filtros por cuenta, rango de fechas, categoría, payee, tag.
* **Balance por cuenta** y **balance neto** al día.

**Datos necesarios**

* Núcleo completo anterior.
* Opcionales: `payees`, `tags` para enriquecer búsqueda/analítica.

**Éxito**

* Balances cuajan al centavo; transferencias se reflejan como 2 asientos.

---

## Etapa 2 — **Multimoneda y FX**

**Qué obtiene el usuario**

* Cuentas en distintas monedas.
* Registrar gastos/ingresos con **tasa de cambio aplicada al momento**.
* Dashboard en **moneda base** del usuario con equivalencias consistentes.

**Datos necesarios**

* `fx_rates` y persistencia de `fx_rate` + `amount_base` en cada asiento.

**Reglas**

* Nunca recalcules retroactivamente `amount_base` de asientos ya asentados; si cambia la fuente FX, crea revaluaciones aparte (etapa avanzada).

---

## Etapa 3 — **Presupuestos y Metas**

**Qué obtiene el usuario**

* Presupuestos por **categoría** (mensual/semanal/custom).
* Seguimiento: gastado vs. límite; alertas de sobrepaso.
* **Metas** (ahorro para viaje, fondo de emergencia) con aportes programados.

**Datos**

* **budgets**(user_id, period, start, end, currency_code).
* **budget_lines**(budget_id, category_id, limit_amount).
* **goals**(user_id, name, target_amount, target_date, linked_account_id?).

---

## Etapa 4 — **Conciliación bancaria**

**Qué obtiene el usuario**

* Importar extractos/feeds; pareo semiautomático de transacciones.
* Marcar períodos reconciliados y detectar diferencias.
* Duplicados: merge inteligente.

**Datos**

* **statements**, **reconciliations**.
* **import_jobs**(user_id, provider, status, started_at, finished_at, raw_file?).
* **import_items**(job_id, external_id, raw_json, mapped_transaction_id?, status).

**Reglas**

* Matching por heurísticas: monto ± tolerancia, fecha, referencia, payee; luego aprendizaje de reglas (etapa 6).

---

## Etapa 5 — **Recurrencias y Automatización básica**

**Qué obtiene el usuario**

* Transacciones **recurrentes** (rentas, suscripciones).
* Plantillas de transacción.
* Recordatorios previos a vencimiento.

**Datos**

* **recurring_rules**(user_id, template_json, frequency, next_run_at, is_active).
* **alerts**(user_id, type, threshold?, channel, is_active).

---

## Etapa 6 — **Categorización inteligente y Reglas**

**Qué obtiene el usuario**

* Motor de **reglas**: “si payee contiene *Uber* → categoría Transporte”.
* Auto-etiquetado por patrones (texto, importe, cuenta origen).
* Aprendizaje incremental (aceptar/sugerir).

**Datos**

* **rules**(user_id, match_json, actions_json, priority, is_active).
* Log de aplicación de reglas (en `audit_logs` o tabla `rule_hits`).

---

## Etapa 7 — **Reportes y Analítica**

**Qué obtiene el usuario**

* Reportes por categoría, payee, tags, cuentas; comparativos YoY/MoM.
* **Cashflow** entrante/saliente por período.
* Distribución de gastos (pareto, top N).
* **Net worth** (patrimonio) por fecha.

**Datos**

* **account_daily_balances**(account_id, date, balance_amount) — tabla derivada para velocidad.
* **net_worth_snapshots**(user_id, date, amount_base).
* Vistas/materializaciones para KPI.

**Consultas típicas**

* Gasto por categoría período: `SUM(amount_base)` donde `categories.type='EXPENSE'`.
* Cash-in/out: agrupar `INCOME` vs `EXPENSE` por mes usando `effective_at`.

---

## Etapa 8 — **Pronóstico y Envelopes**

**Qué obtiene el usuario**

* **Proyección** de saldo por cuenta considerando recurrencias y presupuestos.
* **Envelope budgeting** (sobres): asignar montos a sobres y gastar contra ellos.
* Alertas de **desvío** (forecast vs real).

**Datos**

* **envelopes**(user_id, name, currency_code).
* **envelope_allocations**(envelope_id, period, amount).
* **projections**(user_id, as_of, horizon_days, method, result_json).

---

## Etapa 9 — **Inversiones y Valuación**

**Qué obtiene el usuario**

* Soporte para **activos** (acciones, cripto, fondos) y **lotes** (FIFO).
* Valorización diaria y **ganancia/pérdida** realizada/no realizada.
* Dividendos, staking, fees, splits.

**Datos**

* **securities**(symbol, type).
* **lots**(security_id, account_id, qty, cost_basis, opened_at, closed_at?).
* **trades**(security_id, account_id, qty, price, fee, effective_at).
* **prices**(security_id, date, price).

---

## Etapa 10 — **Multiusuario y Compartido**

**Qué obtiene el usuario**

* **Hogares**/grupos: compartir cuentas o solo reportes.
* Roles: owner, editor, viewer.
* Consolidación de vista familiar vs personal.

**Datos**

* **households**(owner_user_id, name).
* **household_members**(household_id, user_id, role).
* **account_shares**(account_id, user_id, permissions).

---

## Etapa 11 — **Auditoría, Privacidad y Evidencia**

**Qué obtiene el usuario**

* Trazabilidad completa de cambios.
* Adjuntar **recibos** (PDF/JPG) a transacciones; OCR opcional.
* Exportaciones (CSV/JSON) y “borrado/anonimización” bajo pedido.

**Datos**

* Ya contemplados: **attachments**, **audit_logs**.

---

# Reglas de dominio finas (recomendadas)

* **Inmutabilidad**: editar una transacción genera **reverso** + nueva transacción (preserva historia).
* **Transferencias**: siempre 2 asientos, nunca uses “tipo transferencia” especial en datos; es semántica.
* **`effective_at`** manda para reportes; `posted_at` es informativa (banco).
* **Persistir `amount_base`**: acelera reportes y evita drift FX.
* **Categorización** en `ledger_entries` (no en `ledger_transactions`) para soportar **splits** flexibles.
* **Conciliación** solo une `transaction_id` con statement; **no** altera importes.

---

# Métricas/KPIs listos para el producto

* Gasto por categoría (mensual, trimestral, anual).
* Ahorro/ahorro neto = ingresos − gastos.
* Runway (meses que cubres con saldo actual).
* Ratio de deuda (debt-to-income).
* Tasa de asignación a metas (% del ingreso).
* Porcentaje “fijo vs variable”.
* Forecast error (real vs proyectado).

---

# Backlog rápido (forma de tarjetas)

* **MVP-001**: Crear cuentas, categorías, transacciones con splits y transferencias.
* **MVP-002**: Listados + filtros + balances por cuenta/fecha.
* **FX-003**: Multimoneda con `fx_rate` y `amount_base`.
* **BUD-004**: Presupuestos y seguimiento.
* **REC-005**: Importar extractos + conciliación + duplicados.
* **AUT-006**: Reglas de categorización y recurrencias.
* **REP-007**: Reportes y dashboards (cashflow, net worth, comparativos).
* **ENV-008**: Envelopes y proyecciones.
* **INV-009**: Inversiones, lotes y valuación.
* **SHR-010**: Hogares, roles, cuentas compartidas.
* **AUD-011**: Adjuntos, export, auditoría avanzada.

---

Si quieres, preparo **un esquema de tablas detallado** (nombres de columnas, claves y *checks*) exactamente con los campos arriba — listo para migrar cuando decidas la tecnología.
