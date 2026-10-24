<?php

namespace App\Enums;

enum AddonSelectionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Choose one',
            self::Multiple => 'Choose multiple',
        };
    }
}
