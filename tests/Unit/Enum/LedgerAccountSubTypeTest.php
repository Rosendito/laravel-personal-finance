<?php

declare(strict_types=1);

use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;

describe(LedgerAccountSubType::class, function (): void {
    it('returns the expected liquid subtypes and liquidity helper', function (): void {
        expect(LedgerAccountSubType::liquidSubtypes())->toBe([
            LedgerAccountSubType::CASH,
            LedgerAccountSubType::BANK,
            LedgerAccountSubType::WALLET,
        ]);

        foreach (LedgerAccountSubType::liquidSubtypes() as $subtype) {
            expect($subtype->isLiquid())->toBeTrue();
        }

        $nonLiquidSubtypes = array_values(array_filter(
            LedgerAccountSubType::cases(),
            fn (LedgerAccountSubType $subtype): bool => ! in_array($subtype, LedgerAccountSubType::liquidSubtypes()),
        ));

        foreach ($nonLiquidSubtypes as $subtype) {
            expect($subtype->isLiquid())->toBeFalse();
        }
    });

    it('maps each subtype to the expected account type', function (LedgerAccountSubType $subtype, LedgerAccountType $type): void {
        expect($subtype->type())->toBe($type);
    })->with([
        'cash' => [LedgerAccountSubType::CASH, LedgerAccountType::ASSET],
        'bank' => [LedgerAccountSubType::BANK, LedgerAccountType::ASSET],
        'wallet' => [LedgerAccountSubType::WALLET, LedgerAccountType::ASSET],
        'loan receivable' => [LedgerAccountSubType::LOAN_RECEIVABLE, LedgerAccountType::ASSET],
        'investment' => [LedgerAccountSubType::INVESTMENT, LedgerAccountType::ASSET],
        'loan payable' => [LedgerAccountSubType::LOAN_PAYABLE, LedgerAccountType::LIABILITY],
        'credit card' => [LedgerAccountSubType::CREDIT_CARD, LedgerAccountType::LIABILITY],
    ]);
});
