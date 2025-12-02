# Módulo de Préstamos y Deudas

## Contexto

Estamos expandiendo el sistema de finanzas personales para manejar **Préstamos (Loans)** y **Deudas (Debts)**. Actualmente, el sistema ya soporta cuentas, transacciones básicas (ingresos, gastos, transferencias) y balances.

Este módulo permitirá gestionar:

- **Préstamos por cobrar (`LOAN_RECEIVABLE`)**: Dinero que hemos prestado y esperamos recuperar.
- **Deudas por pagar (`LOAN_PAYABLE`)**: Dinero que nos han prestado y debemos pagar.

## Objetivo Final

Implementar un flujo completo en Filament que incluya:

1.  **Lógica de Negocio**: Acciones (Actions) del dominio para registrar el inicio y el pago de préstamos/deudas.
2.  **Interfaz de Usuario**: Una página dedicada (`LiabilitiesTransactionsPage`) con una tabla de transacciones filtrada.
3.  **Widgets**: Indicadores de balance para cuentas de préstamo y deuda, con acciones rápidas para "Pagar" o "Cobrar".
4.  **Acciones de Filament**: Formularios modales para registrar nuevos préstamos o deudas, reutilizando componentes existentes.

---

## Plan de Ejecución (3 Tareas)

### Tarea 1: Lógica de Negocio (Domain Actions & DTOs)

El objetivo es encapsular la lógica financiera en clases `Action` agnósticas del framework UI (Filament), usando DTOs para la transferencia de datos.

**Reglas de Contabilidad (Accounting Logic):**

| Acción de Negocio                | Tipo de Contraparte                     | Cuenta DEBE (Entra/Aumenta)               | Cuenta HABER (Sale/Disminuye)            | Efecto en Balance             |
| :------------------------------- | :-------------------------------------- | :---------------------------------------- | :--------------------------------------- | :---------------------------- |
| **Pedir Prestado (Borrowing)**   | LIABILITY (Pasivo)<br>Sub: LOAN_PAYABLE | Liquid (Entra dinero a mi caja)           | Contraparte (Aumenta mi deuda)           | Activo (+)<br>Pasivo (+)      |
| **Pagar Deuda (Repayment)**      | LIABILITY (Pasivo)<br>Sub: LOAN_PAYABLE | Contraparte (Disminuye mi deuda)          | Liquid (Sale dinero de mi caja)          | Activo (-)<br>Pasivo (-)      |
| **Prestar Dinero (Lending)**     | ASSET (Activo)<br>Sub: LOAN_RECEIVABLE  | Contraparte (Aumenta mi derecho de cobro) | Liquid (Sale dinero de mi caja)          | Activo (+/-)<br>(Intercambio) |
| **Cobrar Préstamo (Collection)** | ASSET (Activo)<br>Sub: LOAN_RECEIVABLE  | Liquid (Entra dinero recuperado)          | Contraparte (Disminuye derecho de cobro) | Activo (+/-)<br>(Intercambio) |

**Archivos de Referencia:**

- `app/Actions/RegisterIncomeAction.php` (Ejemplo de estructura)
- `app/Actions/RegisterExpenseAction.php`
- `app/Data/LedgerTransactionData.php` (DTO base)

**Requerimientos:**
Crear 4 acciones de negocio en `app/Actions/Debts`:

1.  **`RegisterLendingAction`** (Prestar dinero):
    - Origen: Cuenta `ASSET` (Liquid: Cash/Bank/Wallet).
    - Destino: Cuenta `LOAN_RECEIVABLE`.
2.  **`RegisterBorrowingAction`** (Pedir prestado):
    - Origen: Cuenta `LOAN_PAYABLE`.
    - Destino: Cuenta `ASSET` (Liquid).
3.  **`RegisterLendingRepaymentAction`** (Cobrar préstamo):
    - Origen: Cuenta `LOAN_RECEIVABLE`.
    - Destino: Cuenta `ASSET` (Liquid).
4.  **`RegisterBorrowingRepaymentAction`** (Pagar deuda):
    - Origen: Cuenta `ASSET` (Liquid).
    - Destino: Cuenta `LOAN_PAYABLE`.

> **Nota:** Asegurar que se validen los tipos de cuenta correctos usando el Enum `App\Enums\LedgerAccountSubType`.

---

### Tarea 2: Fundamentos de UI (Página, Tabla y Widgets)

Configurar la página de Filament y los widgets visuales. No implementar la lógica de los botones de acción todavía, solo la visualización.

**Archivos de Referencia:**

- `app/Filament/Resources/LedgerTransactions/Pages/ListLedgerTransactions.php` (Referencia principal)
- `app/Filament/Resources/LedgerTransactions/Widgets/AccountBalancesWidget.php` (Referencia para widgets)
- `app/Enums/LedgerAccountSubType.php` (Constantes `LOAN_RECEIVABLE`, `LOAN_PAYABLE`)

**Requerimientos:**

1.  **Página Filament**: Completar `app/Filament/Resources/LedgerTransactions/Pages/LiabilitiesTransactionsPage.php`.
2.  **Tabla**:
    - Debe listar transacciones filtradas donde participen cuentas de tipo `LOAN_RECEIVABLE` o `LOAN_PAYABLE`.
    - Usar las columnas estándar de transacciones (Fecha, Descripción, Monto, Cuentas).
3.  **Widgets**:
    - Crear `DebtLoanBalancesWidget` (similar a `AccountBalancesWidget`).
    - Mostrar dos secciones o grupos: "Por Cobrar" (Receivables) y "Por Pagar" (Payables).
    - Cada cuenta listada debe tener un botón (mockup por ahora) de acción: "Cobrar" (para receivables) o "Pagar" (para payables).

---

### Tarea 3: Interacciones de Filament (Actions & Forms)

Conectar la UI con la lógica de negocio mediante Filament Actions.

**Archivos de Referencia:**

- `app/Concerns/HasTransactionFormComponents.php` (Trait para reutilizar campos de formulario)
- `app/Filament/Resources/LedgerTransactions/Actions/RegisterIncomeFilamentAction.php` (Ejemplo de implementación)

**Requerimientos:**

1.  **Header Actions** (Botones principales en la página):
    - `RegisterLendingFilamentAction`: Abre modal para registrar un nuevo préstamo otorgado.
        - Formulario: Seleccionar cuenta `LOAN_RECEIVABLE`, cuenta `ASSET` origen, monto, descripción.
        - **Creación Dinámica**: El campo de selección de cuenta `LOAN_RECEIVABLE` debe permitir crear una nueva cuenta (`createOptionForm`).
            - _Regla_: Al crear desde aquí, forzar tipo `ASSET` y subtipo `LOAN_RECEIVABLE`.
    - `RegisterBorrowingFilamentAction`: Abre modal para registrar una nueva deuda adquirida.
        - Formulario: Seleccionar cuenta `LOAN_PAYABLE`, cuenta `ASSET` destino, monto, descripción.
        - **Creación Dinámica**: El campo de selección de cuenta `LOAN_PAYABLE` debe permitir crear una nueva cuenta (`createOptionForm`).
            - _Regla_: Al crear desde aquí, forzar tipo `LIABILITY` y subtipo `LOAN_PAYABLE`.

2.  **Widget Actions** (Botones en las tarjetas de cuenta):
    - Implementar la lógica en los botones "Cobrar" y "Pagar" del widget creado en la Tarea 2.
    - Estos botones deben pre-llenar la cuenta seleccionada y pedir el monto y la contrapartida (`ASSET`).

---

## Guía para el Agente

- **Atomicidad**: Realiza una tarea a la vez. Verifica el funcionamiento (tests o manual) antes de pasar a la siguiente.
- **Consistencia**: Mantén la estructura de carpetas (`app/Actions`, `app/Filament/.../Actions`).
- **Reutilización**: Usa `HasTransactionFormComponents` para mantener consistencia en los formularios de montos y fechas.
- **Type Safety**: Usa estrictamente los Enums de PHP para los tipos de cuenta.
