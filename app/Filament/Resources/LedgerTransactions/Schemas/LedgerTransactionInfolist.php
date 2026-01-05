<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Schemas;

use App\Helpers\MoneyFormatter;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LedgerTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('effective_at')
                            ->label('Fecha efectiva')
                            ->dateTime(),
                        TextEntry::make('posted_at')
                            ->label('Fecha publicación')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('reference')
                            ->label('Referencia')
                            ->placeholder('—'),
                        TextEntry::make('source')
                            ->label('Origen')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('budgetPeriod.budget.name')
                            ->label('Presupuesto')
                            ->placeholder('—'),
                        TextEntry::make('category.name')
                            ->label('Categoría')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Movimientos')
                    ->schema([
                        TextEntry::make('entries')
                            ->label('')
                            ->state(static fn (LedgerTransaction $record): array => $record->entries->all())
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->formatStateUsing(static function (?LedgerEntry $entry): string {
                                if (! $entry instanceof LedgerEntry) {
                                    return '';
                                }

                                $accountName = $entry->account->name ?? 'N/A';
                                $formattedAmount = MoneyFormatter::format($entry->amount, $entry->currency_code ?? '');
                                $memo = $entry->memo;

                                $line = "{$accountName}: {$formattedAmount}";

                                if ($memo !== null) {
                                    $line .= " - {$memo}";
                                }

                                return $line;
                            }),
                    ]),
            ]);
    }
}
