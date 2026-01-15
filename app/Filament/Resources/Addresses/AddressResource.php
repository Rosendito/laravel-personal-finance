<?php

declare(strict_types=1);

namespace App\Filament\Resources\Addresses;

use App\Filament\Resources\Addresses\Pages\ManageAddresses;
use App\Filament\Resources\Addresses\Schemas\AddressForm;
use App\Filament\Resources\Addresses\Tables\AddressesTable;
use App\Models\Address;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

final class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $modelLabel = 'Dirección';

    protected static ?string $pluralModelLabel = 'Direcciones';

    protected static string|BackedEnum|null $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAddresses::route('/'),
        ];
    }
}
