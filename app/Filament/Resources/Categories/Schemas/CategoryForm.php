<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use App\Models\Category;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la categoría')
                    ->description('Organiza tus categorías y asigna presupuestos opcionales.')
                    ->schema([
                        Hidden::make('user_id')
                            ->default(static fn (): ?int => Auth::id())
                            ->required(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej. Supermercado')
                            ->required()
                            ->rule('string')
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: static function (Unique $rule): Unique {
                                    return $rule->where('user_id', Auth::id() ?? 0);
                                }
                            ),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(self::typeOptions())
                            ->required()
                            ->native(false)
                            ->live()
                            ->enum(CategoryType::class),
                        Select::make('budget_id')
                            ->label('Presupuesto')
                            ->relationship(
                                name: 'budget',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query): Builder {
                                    $userId = Auth::id() ?? 0;

                                    return $query->where('user_id', $userId);
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Sin presupuesto asignado')
                            ->helperText('Opcional, vincula la categoría a un presupuesto.'),
                        Select::make('parent_id')
                            ->label('Categoría padre')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (
                                    Builder $query,
                                    Get $get,
                                    ?Category $record
                                ): Builder {
                                    $userId = Auth::id() ?? 0;

                                    $query
                                        ->where('user_id', $userId)
                                        ->where('is_archived', false);

                                    $type = $get('type');

                                    if ($type !== null) {
                                        $query->where('type', $type);
                                    }

                                    if ($record !== null) {
                                        $query->whereKeyNot($record->getKey());
                                    }

                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->nullable()
                            ->helperText('Agrupa categorías jerárquicamente.')
                            ->disableOptionWhen(static function (int|string $value, $label, ?Category $record): bool {
                                if ($record === null) {
                                    return false;
                                }

                                return (int) $value === $record->getKey();
                            }),
                        Toggle::make('is_archived')
                            ->label('Archivada')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (CategoryType::cases() as $type) {
            $options[$type->value] = match ($type) {
                CategoryType::Income => 'Ingreso',
                CategoryType::Expense => 'Gasto',
            };
        }

        return $options;
    }
}
