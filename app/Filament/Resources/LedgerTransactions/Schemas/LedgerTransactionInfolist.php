<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Schemas;

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
                                if ($entry === null) {
                                    return '';
                                }

                                $accountName = $entry->account->name ?? 'N/A';
                                $amount = number_format((float) $entry->amount, 2, '.', ',');
                                $currency = $entry->currency_code ?? '';
                                $category = $entry->category?->name;
                                $memo = $entry->memo;

                                $line = "{$accountName}: {$amount} {$currency}";

                                if ($category !== null) {
                                    $line .= " ({$category})";
                                }

                                if ($memo !== null) {
                                    $line .= " - {$memo}";
                                }

                                return $line;
                            }),
                    ]),
            ]);
    }
}
