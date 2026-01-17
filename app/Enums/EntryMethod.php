<?php

declare(strict_types=1);

namespace App\Enums;

enum EntryMethod: string
{
    case MANUAL = 'manual';
    case SCRAPED = 'scraped';
}
