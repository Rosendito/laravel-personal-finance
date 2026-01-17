<?php

declare(strict_types=1);

namespace App\Filament\Clusters\PricesAndMerchants\Resources\Merchants\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class MerchantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del comercio')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('merchant_type')
                            ->label('Tipo')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('base_url')
                            ->label('URL base')
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
