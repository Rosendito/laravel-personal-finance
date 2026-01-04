<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use App\Helpers\MoneyFormatter;
use App\Models\Category;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(static fn(?CategoryType $state): string => match ($state) {
                                CategoryType::Income => 'Ingreso',
                                CategoryType::Expense => 'Gasto',
                                default => 'Desconocido',
                            })
                            ->color(static fn(?CategoryType $state): string => match ($state) {
                                CategoryType::Income => 'success',
                                CategoryType::Expense => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('parent.name')
                            ->label('Padre')
                            ->placeholder('—'),
                        TextEntry::make('budget.name')
                            ->label('Presupuesto')
                            ->placeholder('—'),
                        TextEntry::make('balance')
                            ->label('Balance')
                            ->state(static function (Category $record): string {
                                $balance = (string) ($record->balance ?? '0');

                                if ($record->type === CategoryType::Income && str_starts_with($balance, '-')) {
                                    $balance = mb_ltrim($balance, '-');
                                }

                                return MoneyFormatter::format($balance, config('finance.currency.default'));
                            }),
                        TextEntry::make('is_archived')
                            ->label('Archivada')
                            ->badge()
                            ->formatStateUsing(static fn(?bool $state): string => ($state ?? false) ? 'Sí' : 'No')
                            ->color(static fn(?bool $state): string => ($state ?? false) ? 'gray' : 'success'),
                        TextEntry::make('is_reportable')
                            ->label('Reportable')
                            ->badge()
                            ->formatStateUsing(static fn(?bool $state): string => ($state ?? false) ? 'Sí' : 'No')
                            ->color(static fn(?bool $state): string => ($state ?? false) ? 'success' : 'danger'),
                        TextEntry::make('updated_at')
                            ->label('Actualizada')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
