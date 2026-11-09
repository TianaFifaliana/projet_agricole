<?php

namespace App\Enums;

enum TypeSourceEau: string
{
    case Canal = 'canal';
    case Barrage = 'barrage';
    case Riviere = 'riviere';
    case Pompe = 'pompe';
    case Puits = 'puits';

    public function label(): string
    {
        return match ($this) {
            self::Canal => 'Canal',
            self::Barrage => 'Barrage',
            self::Riviere => 'Rivière',
            self::Pompe => 'Pompe',
            self::Puits => 'Puits',
        };
    }
}
