<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LedgerAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LedgerAccountUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LedgerAccount $account,
        public ?string $oldCurrencyCode,
    ) {}
}
