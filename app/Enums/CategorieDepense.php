<?php

namespace App\Enums;

enum CategorieDepense: string
{
    case Irrigation = 'irrigation';
    case Intrant = 'intrant';
    case MainOeuvre = 'main_oeuvre';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Irrigation => 'Irrigation (énergie & main-d\'œuvre)',
            self::Intrant => 'Intrant',
            self::MainOeuvre => 'Main-d\'œuvre',
            self::Autre => 'Autre',
        };
    }
}
