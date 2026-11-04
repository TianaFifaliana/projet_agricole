<?php

namespace App\Enums;

enum StatutCampagne: string
{
    case Planifiee = 'planifiee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Cloturee = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiee',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminee',
            self::Cloturee => 'Cloturee',
        };
    }
}
