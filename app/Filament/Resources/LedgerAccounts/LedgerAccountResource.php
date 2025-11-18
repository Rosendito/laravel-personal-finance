<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerAccounts;

use App\Filament\Resources\LedgerAccounts\Pages\CreateLedgerAccount;
use App\Filament\Resources\LedgerAccounts\Pages\EditLedgerAccount;
use App\Filament\Resources\LedgerAccounts\Pages\ListLedgerAccounts;
use App\Filament\Resources\LedgerAccounts\Schemas\LedgerAccountForm;
use App\Filament\Resources\LedgerAccounts\Tables\LedgerAccountsTable;
use App\Models\LedgerAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class LedgerAccountResource extends Resource
{
    protected static ?string $model = LedgerAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'Cuenta';

    protected static ?string $pluralModelLabel = 'Cuentas';

    protected static ?string $navigationLabel = 'Cuentas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LedgerAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedgerAccountsTable::configure($table);
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
            'index' => ListLedgerAccounts::route('/'),
            'create' => CreateLedgerAccount::route('/create'),
            'edit' => EditLedgerAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userId = Auth::id();

        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $userId)
            ->where('is_fundamental', false);
    }
}
