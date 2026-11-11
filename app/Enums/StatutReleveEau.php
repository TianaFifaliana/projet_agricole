<?php

namespace App\Enums;

enum StatutReleveEau: string
{
    case Conforme = 'conforme';
    case FuiteProbable = 'fuite_probable';
    case SurAlimentation = 'sur_alimentation';

    public function label(): string
    {
        return match ($this) {
            self::Conforme => 'Conforme',
            self::FuiteProbable => 'Fuite probable',
            self::SurAlimentation => 'Sur-alimentation',
        };
    }
}
