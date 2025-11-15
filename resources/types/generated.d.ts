declare namespace App.Data {
    export type AccountBalanceData = {
        account_id: number;
        name: string;
        currency_code: string;
        balance: string | number;
    };
    export type BudgetAllocationStatusData = {
        budget_id: number;
        budget_name: string;
        period: string;
        allocation_id: number;
        category_id: number;
        category_name: string;
        currency_code: string;
        budgeted: string | number;
        spent: string | number;
        remaining: string | number;
    };
    export type IncomeStatementSummaryData = {
        total_income: string | number;
        total_expense: string | number;
        net_income: string | number;
    };
}

declare namespace App.Enums {
    export type CachedAggregateKey = "current_balance" | "spent";
    export type CachedAggregateScope = "monthly" | "daily";
    export type CategoryType = "INCOME" | "EXPENSE";
    export type LedgerAccountType =
        | "ASSET"
        | "LIABILITY"
        | "INCOME"
        | "EXPENSE"
        | "EQUITY";
}
