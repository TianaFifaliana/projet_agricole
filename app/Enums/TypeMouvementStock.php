<?php

namespace App\Enums;

enum TypeMouvementStock: string
{
    case Entree = 'entree';
    case Sortie = 'sortie';
    case Ajustement = 'ajustement';
    case Transfert = 'transfert';

    public function label(): string
    {
        return match ($this) {
            self::Entree => 'Entrée',
            self::Sortie => 'Sortie',
            self::Ajustement => 'Ajustement',
            self::Transfert => 'Transfert',
        };
    }
}
