<?php

namespace App\Enums;

enum StatutMouvementStock: string
{
    case Entree = 'entree';
    case Sortie = 'sortie';
    case Ajustement = 'ajustement';
    case Transfert = 'transfert';
    case Perte = 'perte';
    case Retour = 'retour';

    public function label(): string
    {
        return match ($this) {
            self::Entree => 'Entree',
            self::Sortie => 'Sortie',
            self::Ajustement => 'Ajustement',
            self::Transfert => 'Transfert',
            self::Perte => 'Perte',
            self::Retour => 'Retour',
        };
    }

    public function estEntree(): bool
    {
        return in_array($this, [self::Entree, self::Retour, self::Transfert], true);
    }

    public function estSortie(): bool
    {
        return in_array($this, [self::Sortie, self::Perte], true);
    }
}
