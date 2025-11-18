<?php

declare(strict_types=1);

namespace App\Filament\Resources\Budgets\Pages;

use App\Filament\Resources\Budgets\BudgetResource;
use App\Models\Budget;
use Filament\Resources\Pages\CreateRecord;

final class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove the first period fields from the budget data
        // They'll be used in afterCreate
        unset(
            $data['first_start_at'],
            $data['first_end_at'],
            $data['first_amount'],
            $data['first_currency_code']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $budget = $this->record;
        $data = $this->data;

        // Create the first period
        if (
            isset(
                $data['first_start_at'],
                $data['first_end_at'],
                $data['first_amount'],
                $data['first_currency_code']
            )
        ) {
            $budget->periods()->create([
                'start_at' => $data['first_start_at'],
                'end_at' => $data['first_end_at'],
                'amount' => $data['first_amount'],
                'currency_code' => $data['first_currency_code'],
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
