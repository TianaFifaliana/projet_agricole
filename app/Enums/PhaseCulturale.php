<?php

namespace App\Enums;

enum PhaseCulturale: string
{
    case Pepiniere = 'pepiniere';
    case Vegetatif = 'vegetatif';
    case Reproductif = 'reproductif';
    case Maturation = 'maturation';

    public function label(): string
    {
        return match ($this) {
            self::Pepiniere => 'Pépinière',
            self::Vegetatif => 'Croissance végétative',
            self::Reproductif => 'Reproduction',
            self::Maturation => 'Maturation',
        };
    }

    /**
     * @return array{part_cycle: float, besoin_journalier_mm: float}
     */
    public function parametres(): array
    {
        $parametres = config('irrigation.phases_culturales') ?? [];

        return [
            'part_cycle' => (float) ($parametres[$this->value]['part_cycle'] ?? 0.0),
            'besoin_journalier_mm' => (float) ($parametres[$this->value]['besoin_journalier_mm'] ?? 0.0),
        ];
    }
}
