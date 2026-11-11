<?php

return [
    'capacite_m3_par_ha' => (float) env('IRRIGATION_CAPACITE_M3_PAR_HA', 5000),
    'seuil_alerte_defaut_pct' => (float) env('IRRIGATION_SEUIL_ALERTE_PCT', 80),
    'cout_energie_par_m3' => (float) env('IRRIGATION_COUT_ENERGIE_PAR_M3', 0.15),
    'cout_main_oeuvre_par_heure' => (float) env('IRRIGATION_COUT_MAIN_OEUVRE_PAR_HEURE', 20),

    /*
     * Prévision de besoin en eau : répartition du cycle cultural en phases et
     * besoins hydriques journaliers nets de référence (mm/jour) inspirés des
     * coefficients culturaux du riz (FAO). Les parts de cycle doivent totaliser 1.
     */
    'phases_culturales' => [
        'pepiniere' => [
            'part_cycle' => 0.20,
            'besoin_journalier_mm' => 12.0,
        ],
        'vegetatif' => [
            'part_cycle' => 0.35,
            'besoin_journalier_mm' => 8.0,
        ],
        'reproductif' => [
            'part_cycle' => 0.25,
            'besoin_journalier_mm' => 10.0,
        ],
        'maturation' => [
            'part_cycle' => 0.20,
            'besoin_journalier_mm' => 5.0,
        ],
    ],
    'rendement_reseau_defaut_pct' => (float) env('IRRIGATION_RENDEMENT_RESEAU_PCT', 70),
    'marge_securite_defaut_pct' => (float) env('IRRIGATION_MARGE_SECURITE_PCT', 10),

    /*
     * Suivi du niveau d'eau dans la rizière : percolation moyenne attendue
     * (mm/jour infiltrés dans le sol) et tolérance d'écart (mm) au-delà de
     * laquelle une différence entre le niveau attendu et le niveau constaté
     * est considérée comme une fuite probable.
     */
    'percolation_attendue_mm_jour' => (float) env('IRRIGATION_PERCOLATION_MM_JOUR', 2),
    'tolerance_ecart_niveau_mm' => (float) env('IRRIGATION_TOLERANCE_ECART_NIVEAU_MM', 15),
];
