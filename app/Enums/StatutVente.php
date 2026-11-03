<?php

namespace App\Enums;

enum StatutVente: string
{
    case Brouillon = 'brouillon';
    case Validee = 'validee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Validee => 'Validée',
            self::Annulee => 'Annulée',
        };
    }
}
