<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Tables;

use App\Filament\Resources\LedgerTransactions\Actions\EditLedgerTransactionFilamentAction;
use App\Helpers\MoneyFormatter;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class LiabilitiesTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table;
    }
}
