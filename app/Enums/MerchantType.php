<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum MerchantType: string implements HasColor, HasIcon, HasLabel
{
    case STORE = 'store';
    case MARKETPLACE = 'marketplace';
    case OTHER = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::STORE => 'Tienda',
            self::MARKETPLACE => 'Marketplace',
            self::OTHER => 'Otro',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::STORE => 'heroicon-m-building-storefront',
            self::MARKETPLACE => 'heroicon-m-shopping-bag',
            self::OTHER => 'heroicon-m-ellipsis-horizontal-circle',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::STORE => 'success',
            self::MARKETPLACE => 'info',
            self::OTHER => 'gray',
        };
    }
}
