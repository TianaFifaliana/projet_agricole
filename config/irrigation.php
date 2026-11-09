<?php

return [
    'capacite_m3_par_ha' => (float) env('IRRIGATION_CAPACITE_M3_PAR_HA', 5000),
    'seuil_alerte_defaut_pct' => (float) env('IRRIGATION_SEUIL_ALERTE_PCT', 80),
    'cout_energie_par_m3' => (float) env('IRRIGATION_COUT_ENERGIE_PAR_M3', 0.15),
    'cout_main_oeuvre_par_heure' => (float) env('IRRIGATION_COUT_MAIN_OEUVRE_PAR_HEURE', 20),
];
