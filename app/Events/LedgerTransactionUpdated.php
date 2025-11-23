<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LedgerTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LedgerTransactionUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public LedgerTransaction $transaction) {}
}
