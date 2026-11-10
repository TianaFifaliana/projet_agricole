<?php

namespace App\Enums;

enum StatutPrevisionEau: string
{
    case Proposee = 'proposee';
    case Allouee = 'allouee';

    public function label(): string
    {
        return match ($this) {
            self::Proposee => 'Proposée',
            self::Allouee => 'Convertie en budget',
        };
    }
}
