<?php

namespace App\Enums;

enum StatutBudgetEau: string
{
    case Actif = 'actif';
    case Alerte = 'alerte';
    case Epuise = 'epuise';
    case Cloture = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::Actif => 'Actif',
            self::Alerte => 'Seuil d\'alerte atteint',
            self::Epuise => 'Épuisé',
            self::Cloture => 'Clôturé',
        };
    }
}
