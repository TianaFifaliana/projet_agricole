<?php

namespace App\Enums;

enum StatutCycleCulture: string
{
    case Planifie = 'planifie';
    case EnCours = 'en_cours';
    case Recolte = 'recolte';
    case Cloture = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::Planifie => 'Planifié',
            self::EnCours => 'En cours',
            self::Recolte => 'Récolté',
            self::Cloture => 'Clôturé',
        };
    }
}
