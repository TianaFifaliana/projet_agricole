<?php

namespace App\Enums;

enum EtatSourceEau: string
{
    case Operationnelle = 'operationnelle';
    case EnPanne = 'en_panne';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Operationnelle => 'Opérationnelle',
            self::EnPanne => 'En panne',
            self::Maintenance => 'En maintenance',
        };
    }

    public function estOperationnelle(): bool
    {
        return $this === self::Operationnelle;
    }
}
