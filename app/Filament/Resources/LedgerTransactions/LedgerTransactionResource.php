<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions;

use App\Filament\Resources\LedgerTransactions\Pages\EditLedgerTransaction;
use App\Filament\Resources\LedgerTransactions\Pages\ListLedgerTransactions;
use App\Filament\Resources\LedgerTransactions\Pages\ViewLedgerTransaction;
use App\Filament\Resources\LedgerTransactions\Schemas\LedgerTransactionForm;
use App\Filament\Resources\LedgerTransactions\Schemas\LedgerTransactionInfolist;
use App\Filament\Resources\LedgerTransactions\Tables\LedgerTransactionsTable;
use App\Models\LedgerTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class LedgerTransactionResource extends Resource
{
    protected static ?string $model = LedgerTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'Transacción';

    protected static ?string $pluralModelLabel = 'Transacciones';

    protected static ?string $navigationLabel = 'Transacciones';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return LedgerTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LedgerTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedgerTransactionsTable::configure($table);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerTransactions::route('/'),
            'edit' => EditLedgerTransaction::route('/{record}/edit'),
            'view' => ViewLedgerTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userId = Auth::id();

        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $userId);
    }
}
