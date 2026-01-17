<?php

declare(strict_types=1);

namespace App\Filament\Resources\Addresses\RelationManagers;

use App\Filament\Resources\Addresses\Schemas\AddressForm;
use App\Filament\Resources\Addresses\Tables\AddressesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

final class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return AddressForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return AddressesTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva dirección')
                    ->modalHeading('Crear dirección'),
            ]);
    }
}
