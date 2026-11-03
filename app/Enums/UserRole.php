<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrateur = 'administrateur';
    case Responsable = 'responsable';
    case Agent = 'agent';

    public function label(): string
    {
        return match ($this) {
            self::Administrateur => 'Administrateur',
            self::Responsable => "Responsable d'exploitation",
            self::Agent => 'Agent / Technicien',
        };
    }
}
