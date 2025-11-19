<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $currency = Currency::find('USDT');

        LedgerAccount::factory()->for($user)->create([
            'user_id' => $user->id,
            'name' => 'Binance USDT',
            'type' => LedgerAccountType::Asset,
            'currency_code' => $currency->code,
        ]);

        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Comida',
        ]);
    }
}
