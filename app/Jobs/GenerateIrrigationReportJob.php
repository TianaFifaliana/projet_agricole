<?php

namespace App\Jobs;

use App\Models\CycleCulture;
use App\Models\Irrigation;
use App\Models\IrrigationReport;
use App\Models\Scopes\ExploitationScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateIrrigationReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 600];

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = CycleCulture::withoutGlobalScope(ExploitationScope::class)
            ->with(['culture', 'parcelle'])
            ->find($this->campaignId);

        if (! $campaign instanceof CycleCulture) {
            Log::channel('audit')->warning('irrigation.report.campagne.introuvable', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        $irrigations = Irrigation::withoutGlobalScope(ExploitationScope::class)
            ->where('campaign_id', $campaign->getKey())
            ->with('productionUnit')
            ->orderBy('date')
            ->get();

        $lignes = [];
        $total = 0.0;
        $debut = null;
        $fin = null;

        foreach ($irrigations as $irrigation) {
            $date = $irrigation->date;

            if ($date === null) {
                continue;
            }

            if ($debut === null || $date->lt($debut)) {
                $debut = $date->copy();
            }

            if ($fin === null || $date->gt($fin)) {
                $fin = $date->copy();
            }

            $semaineDebut = $date->copy()->startOfWeek(Carbon::MONDAY);
            $semaineFin = $date->copy()->endOfWeek(Carbon::SUNDAY);
            $semaine = sprintf('%04d-W%02d', $date->isoWeekYear, $date->isoWeek);
            $cle = $semaine.'|'.$irrigation->production_unit_id;

            $lignes[$cle] ??= [
                'parcelle_id' => (int) $irrigation->production_unit_id,
                'parcelle_code' => $irrigation->productionUnit?->code,
                'parcelle_nom' => $irrigation->productionUnit?->nom,
                'semaine' => $semaine,
                'semaine_debut' => $semaineDebut->toDateString(),
                'semaine_fin' => $semaineFin->toDateString(),
                'volume_m3' => 0.0,
                'nombre_irrigations' => 0,
            ];

            $lignes[$cle]['volume_m3'] = round($lignes[$cle]['volume_m3'] + (float) $irrigation->volume_m3, 2);
            $lignes[$cle]['nombre_irrigations']++;

            $total += (float) $irrigation->volume_m3;
        }

        $lignes = array_values($lignes);

        usort($lignes, fn (array $a, array $b): int => [$a['semaine'], $a['parcelle_code']] <=> [$b['semaine'], $b['parcelle_code']]);

        $rapport = IrrigationReport::withoutGlobalScope(ExploitationScope::class)->updateOrCreate(
            ['campaign_id' => $campaign->getKey()],
            [
                'exploitation_id' => $campaign->exploitation_id,
                'periode_debut' => $debut?->toDateString(),
                'periode_fin' => $fin?->toDateString(),
                'total_volume_m3' => round($total, 2),
                'lignes' => $lignes,
                'genere_le' => now(),
            ],
        );

        Log::channel('audit')->info('irrigation.report.genere', [
            'irrigation_report_id' => $rapport->getKey(),
            'campaign_id' => $campaign->getKey(),
            'exploitation_id' => $campaign->exploitation_id,
            'total_volume_m3' => (float) $rapport->total_volume_m3,
            'nombre_lignes' => count($lignes),
        ]);
    }
}
