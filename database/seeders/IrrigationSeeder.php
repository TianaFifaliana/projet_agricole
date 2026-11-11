<?php

namespace Database\Seeders;

use App\Enums\EtatSourceEau;
use App\Enums\TypeSourceEau;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\User;
use App\Models\WaterSource;
use App\Services\IrrigationService;
use App\Services\SuiviNiveauEauService;
use App\Services\WaterBudgetService;
use Illuminate\Database\Seeder;

class IrrigationSeeder extends Seeder
{
    public function run(
        WaterBudgetService $budgets,
        IrrigationService $irrigations,
        SuiviNiveauEauService $suivi,
    ): void {
        $soavina = Exploitation::where('code', 'EXP-001')->firstOrFail();
        $anjohory = Exploitation::where('code', 'EXP-002')->firstOrFail();

        $agentSoavina = $soavina->users()->where('email', 'agent@agrisaas.test')->firstOrFail();
        $agentAnjohory = $anjohory->users()->where('email', 'agent.anjohory@agrisaas.test')->firstOrFail();

        $this->creerSourcesSoavina($soavina);
        $this->creerSourcesAnjohory($anjohory);

        $canalAndranomandry = $this->source($soavina, 'Canal Andranomandry');

        $budgetRz01 = $budgets->allocateBudget($this->cycle($soavina, 'RZ-01', '2026/27'), 8000);
        $budgetRz02 = $budgets->allocateBudget($this->cycle($soavina, 'RZ-02', '2026/27'), 5000);
        $budgets->allocateBudget($this->cycle($soavina, 'RZ-05', '2026/27'), 12000);

        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-01', 600, 60000, 'Arrosage de establishment après repiquage');
        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-03', 600, 60000, 'Maintien de la lame d\'eau');
        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-05', 600, 60000, 'Complément après trois jours sans pluie');
        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-07', 550, 55000, 'Arrosage ajusté selon la pépinière');
        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-09', 550, 55000, 'Arrosage du matin');
        $this->irriger($soavina, 'RZ-01', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-10', 550, 55000, 'Arrosage avant relevé de niveau');

        $this->irriger($soavina, 'RZ-02', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-07', 700, 70000, 'Premier arrosage du bas-fond');
        $this->irriger($soavina, 'RZ-02', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-08', 700, 70000, 'Deuxième arrosage du bas-fond');
        $this->irriger($soavina, 'RZ-02', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-09', 550, 55000, 'Arrosage régulier');
        $this->irriger($soavina, 'RZ-02', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-10', 550, 55000, 'Dernier volume disponible du quota');

        $this->irriger($soavina, 'RZ-05', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-02', 550, 55000, 'Arrosage du Makalioka traditionnel');
        $this->irriger($soavina, 'RZ-05', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-05', 800, 80000, 'Arrosage renforcé');
        $this->irriger($soavina, 'RZ-05', '2026/27', $canalAndranomandry, $agentSoavina, '2026-11-09', 800, 80000, 'Arrosage du soir');

        $budgets->requestAdjustment(
            $budgetRz01,
            2000,
            'Brèche suspectée sur la digue est de la rizière RZ-01 : volume complémentaire pour maintenir la lame d\'eau jusqu\'à la réparation.',
            $agentSoavina,
        );

        $this->relever($soavina, 'RZ-01', '2026/27', $agentSoavina, '2026-11-04', 60, 0, 'Lame d\'eau de référence après le repiquage.');
        $this->relever($soavina, 'RZ-01', '2026/27', $agentSoavina, '2026-11-08', 24, 0, 'Niveau conforme après les arrosages.');
        $this->relever($soavina, 'RZ-01', '2026/27', $agentSoavina, '2026-11-10', 5, 8, 'Niveau anormalement bas malgré la pluie : brèche suspectée sur la digue est.');

        $budgets->allocateBudget($this->cycle($anjohory, 'RZ-01', '2026/27'), 15000);

        $canalAntsamaka = $this->source($anjohory, 'Canal Antsamaka');

        $this->irriger($anjohory, 'RZ-01', '2026/27', $canalAntsamaka, $agentAnjohory, '2026-11-05', 600, 80000, 'Arrosage après le semis direct');
        $this->irriger($anjohory, 'RZ-01', '2026/27', $canalAntsamaka, $agentAnjohory, '2026-11-08', 600, 80000, 'Deuxième arrosage');
        $this->irriger($anjohory, 'RZ-01', '2026/27', $canalAntsamaka, $agentAnjohory, '2026-11-10', 600, 80000, 'Troisième arrosage avant le tallage');

        $this->relever($anjohory, 'RZ-01', '2026/27', $agentAnjohory, '2026-11-09', 55, 0, 'Lame d\'eau homogène après le second arrosage.');
    }

    private function creerSourcesSoavina(Exploitation $exploitation): void
    {
        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Canal Andranomandry',
            'type' => TypeSourceEau::Canal,
            'debit_m3_h' => 120,
            'etat' => EtatSourceEau::Operationnelle,
            'actif' => true,
        ]);

        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Barrage de Bevava',
            'type' => TypeSourceEau::Barrage,
            'debit_m3_h' => 250,
            'etat' => EtatSourceEau::Operationnelle,
            'actif' => true,
        ]);

        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Pompe Ambalakely',
            'type' => TypeSourceEau::Pompe,
            'debit_m3_h' => 45,
            'etat' => EtatSourceEau::EnPanne,
            'actif' => true,
        ]);
    }

    private function creerSourcesAnjohory(Exploitation $exploitation): void
    {
        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Canal Antsamaka',
            'type' => TypeSourceEau::Canal,
            'debit_m3_h' => 200,
            'etat' => EtatSourceEau::Operationnelle,
            'actif' => true,
        ]);

        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Barrage de Mandroatra',
            'type' => TypeSourceEau::Barrage,
            'debit_m3_h' => 180,
            'etat' => EtatSourceEau::Maintenance,
            'actif' => true,
        ]);

        WaterSource::create([
            'exploitation_id' => $exploitation->getKey(),
            'nom' => 'Pompe Matsaborimena',
            'type' => TypeSourceEau::Pompe,
            'debit_m3_h' => 60,
            'etat' => EtatSourceEau::Operationnelle,
            'actif' => true,
        ]);
    }

    private function irriger(
        Exploitation $exploitation,
        string $parcelleCode,
        string $campagne,
        WaterSource $source,
        User $operateur,
        string $date,
        int $dureeMinutes,
        ?float $cout,
        ?string $observation,
    ): void {
        $cycle = $this->cycle($exploitation, $parcelleCode, $campagne);

        app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $cycle->getKey(),
            'production_unit_id' => $cycle->parcelle_id,
            'water_source_id' => $source->getKey(),
            'date' => $date,
            'duree_minutes' => $dureeMinutes,
            'debit_m3_h' => (float) $source->debit_m3_h,
            'cout' => $cout,
            'observation' => $observation,
        ], $operateur);
    }

    private function relever(
        Exploitation $exploitation,
        string $parcelleCode,
        string $campagne,
        User $operateur,
        string $dateReleve,
        float $niveauConstate,
        float $precipitations,
        ?string $observation,
    ): void {
        $cycle = $this->cycle($exploitation, $parcelleCode, $campagne);

        app(SuiviNiveauEauService::class)->enregistrer([
            'campaign_id' => $cycle->getKey(),
            'date_releve' => $dateReleve,
            'niveau_constate_mm' => $niveauConstate,
            'precipitations_mm' => $precipitations,
            'observation' => $observation,
        ], $operateur);
    }

    private function cycle(Exploitation $exploitation, string $parcelleCode, string $campagne): CycleCulture
    {
        return CycleCulture::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('campagne', $campagne)
            ->whereHas('parcelle', fn ($query) => $query->where('code', $parcelleCode))
            ->firstOrFail();
    }

    private function source(Exploitation $exploitation, string $nom): WaterSource
    {
        return WaterSource::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', $nom)
            ->firstOrFail();
    }
}
