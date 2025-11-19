<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LedgerTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    /**
     * @return array<int, Section>
     */
    public static function getComponents(): array
    {
        return [
            Section::make('Información General')
                ->description('Detalles básicos de la transacción')
                ->schema([
                    TextInput::make('description')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(255),
                    DateTimePicker::make('effective_at')
                        ->label('Fecha efectiva')
                        ->required()
                        ->native(false),
                    DatePicker::make('posted_at')
                        ->label('Fecha publicación')
                        ->native(false),
                    TextInput::make('reference')
                        ->label('Referencia')
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }
}
