<?php

namespace App\Enums;

enum EvaluationKind: string
{
    case Field = 'field';
    case Academic = 'academic';

    /** Arabic label for the interface. */
    public function label(): string
    {
        return match ($this) {
            self::Field => 'تقييم ميداني',
            self::Academic => 'تقييم أكاديمي',
        };
    }
}
