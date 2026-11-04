<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Administrateur = 'administrateur';
    case Responsable = 'responsable';
    case Agent = 'agent';
    case Technicien = 'technicien';

    public function label(): string
    {
        return match ($this) {
            self::Administrateur => 'Administrateur',
            self::Responsable => 'Responsable',
            self::Agent => 'Agent',
            self::Technicien => 'Technicien',
        };
    }
}
