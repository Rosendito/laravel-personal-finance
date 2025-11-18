<?php

declare(strict_types=1);

namespace App\Filament\Resources\Budgets\Schemas;

use App\Models\Currency;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

final class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Budget Details')
                ->schema([
                    Hidden::make('user_id')
                        ->default(static fn (): ?int => Auth::id())
                        ->required(),
                    TextInput::make('name')
                        ->label('Name')
                        ->placeholder('Monthly groceries')
                        ->required()
                        ->rule('string')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: static function (Unique $rule): Unique {
                            return $rule->where('user_id', Auth::id() ?? 0);
                        }),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
            Section::make('Initial Period')
                ->description('Every budget must have at least one period. You can add more periods later.')
                ->schema([
                    DatePicker::make('first_start_at')
                        ->label('Start date')
                        ->default(static fn (): string => now()->startOfMonth()->toDateString())
                        ->required()
                        ->rule('date')
                        ->helperText('Transactions are included starting on this date.')
                        ->hiddenOn('edit'),
                    DatePicker::make('first_end_at')
                        ->label('End date (exclusive)')
                        ->default(static fn (): string => now()->startOfMonth()->addMonth()->toDateString())
                        ->required()
                        ->after('first_start_at')
                        ->rule('date')
                        ->helperText('Transactions on this date belong to the next period.')
                        ->hiddenOn('edit'),
                    TextInput::make('first_amount')
                        ->label('Budgeted Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->rule('decimal:0,6')
                        ->hiddenOn('edit'),
                    Select::make('first_currency_code')
                        ->label('Currency')
                        ->default(static fn (): ?string => Currency::query()->first()?->code)
                        ->required()
                        ->options(Currency::query()->pluck('code', 'code'))
                        ->searchable()
                        ->hiddenOn('edit'),
                ])
                ->hiddenOn('edit'),
        ]);
    }
}
