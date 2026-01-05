<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Tables;

use App\Enums\CategoryType;
use App\Helpers\MoneyFormatter;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(static fn (?CategoryType $state): string => match ($state) {
                        CategoryType::Income => 'Ingreso',
                        CategoryType::Expense => 'Gasto',
                        default => 'Desconocido',
                    })
                    ->color(static fn (?CategoryType $state): string => match ($state) {
                        CategoryType::Income => 'success',
                        CategoryType::Expense => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Padre')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('budget.name')
                    ->label('Presupuesto')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->state(static function (Category $record): string {
                        $balance = (string) ($record->balance ?? '0');

                        if ($record->type === CategoryType::Income && str_starts_with($balance, '-')) {
                            $balance = mb_ltrim($balance, '-');
                        }

                        return MoneyFormatter::format(
                            $balance,
                            config('finance.currency.default'),
                        );
                    })
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('entries_count')
                    ->label('Movimientos')
                    ->counts('entries')
                    ->formatStateUsing(static fn (?int $state): string => (string) ($state ?? 0))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_archived')
                    ->label('Archivada')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('is_reportable')
                    ->label('Reportable')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        CategoryType::Income->value => 'Ingreso',
                        CategoryType::Expense->value => 'Gasto',
                    ]),
                SelectFilter::make('budget')
                    ->label('Presupuesto')
                    ->relationship(
                        name: 'budget',
                        titleAttribute: 'name',
                        modifyQueryUsing: static function (Builder $query): Builder {
                            $userId = Auth::id() ?? 0;

                            return $query->where('user_id', $userId);
                        }
                    ),
                TernaryFilter::make('is_archived')
                    ->label('Estado')
                    ->nullable()
                    ->placeholder('Todas')
                    ->trueLabel('Archivadas')
                    ->falseLabel('Activas'),
                TernaryFilter::make('is_reportable')
                    ->label('Reporte')
                    ->nullable()
                    ->placeholder('Todas')
                    ->trueLabel('Reportables')
                    ->falseLabel('No reportables'),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
