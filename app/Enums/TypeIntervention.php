<?php

namespace App\Enums;

enum TypeIntervention: string
{
    case Preparation = 'preparation';
    case Semis = 'semis';
    case Fertilisation = 'fertilisation';
    case Traitement = 'traitement';
    case Irrigation = 'irrigation';
    case Recolte = 'recolte';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Preparation => 'Préparation du sol',
            self::Semis => 'Semis',
            self::Fertilisation => 'Fertilisation',
            self::Traitement => 'Traitement phytosanitaire',
            self::Irrigation => 'Irrigation',
            self::Recolte => 'Récolte',
            self::Autre => 'Autre',
        };
    }

    public function estRecolte(): bool
    {
        return $this === self::Recolte;
    }
}
