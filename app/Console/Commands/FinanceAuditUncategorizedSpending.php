<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LedgerAccountType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

final class FinanceAuditUncategorizedSpending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:audit-uncategorized-spending
        {--start_at= : Start date (YYYY-MM-DD)}
        {--end_at= : End date (YYYY-MM-DD)}
        {--user_id= : User ID to audit}
        {--limit=200 : Max rows to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit transactions that contribute to uncategorized spending totals.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->resolveUserId();
        [$startAt, $endAt] = $this->resolveDateRange();

        $limit = (int) ($this->option('limit') ?? 200);
        if ($limit < 1) {
            $limit = 200;
        }

        $rows = DB::query()
            ->from('ledger_transactions as t')
            ->selectRaw('t.id as transaction_id')
            ->selectRaw('DATE(t.effective_at) as effective_date')
            ->selectRaw('t.category_id as category_id')
            ->selectRaw('COALESCE(c.name, \'Sin categoría\') as category_name')
            ->selectRaw(
                'SUM(CASE WHEN COALESCE(e.amount_base, e.amount) > 0 THEN COALESCE(e.amount_base, e.amount) ELSE 0 END) as spent_amount'
            )
            ->selectSub(
                DB::query()
                    ->from('ledger_entries as e_from')
                    ->select('a_from.name')
                    ->join('ledger_accounts as a_from', 'a_from.id', '=', 'e_from.account_id')
                    ->whereColumn('e_from.transaction_id', 't.id')
                    ->whereRaw('COALESCE(e_from.amount_base, e_from.amount) < 0')
                    ->orderByRaw('ABS(COALESCE(e_from.amount_base, e_from.amount)) DESC')
                    ->limit(1),
                'from_account_name'
            )
            ->selectSub(
                DB::query()
                    ->from('ledger_entries as e_to')
                    ->select('a_to.name')
                    ->join('ledger_accounts as a_to', 'a_to.id', '=', 'e_to.account_id')
                    ->whereColumn('e_to.transaction_id', 't.id')
                    ->where('a_to.type', LedgerAccountType::EXPENSE->value)
                    ->whereRaw('COALESCE(e_to.amount_base, e_to.amount) > 0')
                    ->orderByRaw('COALESCE(e_to.amount_base, e_to.amount) DESC')
                    ->limit(1),
                'to_account_name'
            )
            ->join('ledger_entries as e', 'e.transaction_id', '=', 't.id')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.user_id', $userId)
            ->whereNull('t.category_id')
            ->where('a.type', LedgerAccountType::EXPENSE->value)
            ->whereBetween('t.effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->groupBy('t.id')
            ->groupBy('effective_date')
            ->groupBy('t.category_id')
            ->groupBy('category_name')
            ->groupBy('from_account_name')
            ->groupBy('to_account_name')
            ->havingRaw('spent_amount > 0')
            ->orderByDesc('spent_amount')
            ->limit($limit)
            ->get();

        $totalSpent = '0';
        foreach ($rows as $row) {
            $totalSpent = bcadd($totalSpent, (string) ($row->spent_amount ?? '0'), 6);
        }

        $tableRows = $rows->map(static function (object $row): array {
            $amount = number_format((float) $row->spent_amount, 2, '.', '');

            return [
                $amount,
                (string) ($row->from_account_name ?? ''),
                (string) ($row->to_account_name ?? ''),
                (string) ($row->category_name ?? 'Sin categoría'),
                (string) ($row->effective_date ?? ''),
            ];
        })->all();

        table(
            headers: ['Amount', 'From', 'To', 'Category', 'Date'],
            rows: $tableRows,
        );

        $currency = (string) config('finance.currency.default', 'USD');
        $this->newLine();
        $this->info(sprintf('Rows: %d', count($tableRows)));
        $this->info(sprintf('Total uncategorized spent (base): %s %s', number_format((float) $totalSpent, 2, '.', ''), $currency));

        return self::SUCCESS;
    }

    private function resolveUserId(): int
    {
        $userId = $this->option('user_id');

        if (is_string($userId) && $userId !== '') {
            return (int) $userId;
        }

        $count = User::query()->count();

        if ($count < 1) {
            throw new InvalidArgumentException('No users found.');
        }

        if ($count === 1) {
            return (int) User::query()->value('id');
        }

        $input = text(
            label: 'User id',
            placeholder: '1',
            required: true,
        );

        return (int) $input;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveDateRange(): array
    {
        $startAt = $this->option('start_at');
        $endAt = $this->option('end_at');

        $startInput = is_string($startAt) && $startAt !== ''
            ? $startAt
            : text(
                label: 'start_at (YYYY-MM-DD)',
                default: CarbonImmutable::today()->subDays(30)->toDateString(),
                required: true,
            );

        $endInput = is_string($endAt) && $endAt !== ''
            ? $endAt
            : text(
                label: 'end_at (YYYY-MM-DD)',
                default: CarbonImmutable::today()->toDateString(),
                required: true,
            );

        $start = CarbonImmutable::parse($startInput)->startOfDay();
        $end = CarbonImmutable::parse($endInput)->endOfDay();

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('end_at must be greater than or equal to start_at.');
        }

        return [$start, $end];
    }
}
