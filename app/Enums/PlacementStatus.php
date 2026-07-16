<?php

namespace App\Enums;

enum PlacementStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';

    /** Arabic label for the interface. */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Completed => 'مكتمل',
            self::Withdrawn => 'منسحب',
        };
    }
}
