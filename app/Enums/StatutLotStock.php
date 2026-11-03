<?php

namespace App\Enums;

enum StatutLotStock: string
{
    case Disponible = 'disponible';
    case Epuise = 'epuise';
    case Perime = 'perime';
    case Bloque = 'bloque';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Epuise => 'Épuisé',
            self::Perime => 'Périmé',
            self::Bloque => 'Bloqué',
        };
    }
}
