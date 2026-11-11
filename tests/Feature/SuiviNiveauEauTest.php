<?php

namespace Tests\Feature;

use App\Enums\PhaseCulturale;
use App\Enums\StatutCycleCulture;
use App\Enums\StatutReleveEau;
use App\Events\FuiteProbableDetectee;
use App\Exceptions\RegleMetierException;
use App\Listeners\NotifierFuiteProbable;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\JournalAudit;
use App\Models\Parcelle;
use App\Models\ReleveNiveauEau;
use App\Models\User;
use App\Models\WaterSource;
use App\Notifications\FuiteEauDetecteeNotification;
use App\Services\SuiviNiveauEauService;
use App\Services\WaterNeedsForecastService;
use Illuminate\Database\QueryException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SuiviNiveauEauTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_premier_releve_etablit_la_reference_sans_bilan(): void
    {
        [$user, $campaign] = $this->contexte();

        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);

        $this->assertTrue($releve->estPremier());
        $this->assertSame(StatutReleveEau::Conforme, $releve->statut);
        $this->assertSame(PhaseCulturale::Pepiniere, $releve->phase_culturale);
        $this->assertNull($releve->niveau_precedent_mm);
        $this->assertNull($releve->niveau_attendu_mm);
        $this->assertNull($releve->ecart_mm);
        $this->assertNull($releve->pertes_anormales_m3);
    }

    public function test_le_bilan_hydrique_agrege_pluie_irrigation_evaporation_et_percolation(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);

        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 65, 10), $user);

        $this->assertFalse($releve->estPremier());
        $this->assertSame(PhaseCulturale::Vegetatif, $releve->phase_culturale);
        $this->assertSame(100.0, (float) $releve->niveau_precedent_mm);
        $this->assertSame(100.0, (float) $releve->apport_irrigation_m3);
        $this->assertSame(36.0, (float) $releve->evaporation_attendue_mm);
        $this->assertSame(8.0, (float) $releve->percolation_attendue_mm);
        $this->assertSame(71.0, (float) $releve->niveau_attendu_mm);
        $this->assertSame(6.0, (float) $releve->ecart_mm);
        $this->assertSame(120.0, (float) $releve->pertes_anormales_m3);
        $this->assertSame(44.0, $releve->pertesNormalesMm());
        $this->assertSame(StatutReleveEau::Conforme, $releve->statut);
    }

    public function test_un_ecart_superieur_a_la_tolerance_detecte_une_fuite_probable(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        Event::fake([FuiteProbableDetectee::class]);

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);

        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 50, 10), $user);

        $this->assertSame(StatutReleveEau::FuiteProbable, $releve->statut);
        $this->assertTrue($releve->estFuiteProbable());
        $this->assertSame(21.0, (float) $releve->ecart_mm);
        $this->assertSame(420.0, (float) $releve->pertes_anormales_m3);
        Event::assertDispatched(FuiteProbableDetectee::class, fn (FuiteProbableDetectee $event): bool => $event->releve->is($releve));
    }

    public function test_un_niveau_superieur_a_l_attendu_signale_une_sur_alimentation(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        Event::fake([FuiteProbableDetectee::class]);

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);

        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 100, 10), $user);

        $this->assertSame(StatutReleveEau::SurAlimentation, $releve->statut);
        $this->assertSame(-29.0, (float) $releve->ecart_mm);
        $this->assertSame(0.0, (float) $releve->pertes_anormales_m3);
        Event::assertNotDispatched(FuiteProbableDetectee::class);
    }

    public function test_un_seul_releve_par_riziere_et_par_jour_est_autorise(): void
    {
        [$user, $campaign] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('Un relevé de niveau d\'eau existe déjà pour cette rizière à cette date.');

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 90), $user);
    }

    public function test_les_releves_doivent_etre_saisis_dans_l_ordre_chronologique(): void
    {
        [$user, $campaign] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 100), $user);

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('Les relevés doivent être saisis dans l\'ordre chronologique');

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-05', 90), $user);
    }

    public function test_aucun_releve_sur_une_campagne_terminee(): void
    {
        [$user, $campaign] = $this->contexte();

        $campaign->forceFill(['statut' => StatutCycleCulture::Recolte])->save();

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('La campagne est terminée');

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
    }

    public function test_le_releve_est_borne_par_le_semis_et_le_jour_courant(): void
    {
        [$user, $campaign] = $this->contexte();

        $tentatives = [
            ['2026-04-30', 'Le relevé ne peut pas être antérieur au semis de la campagne.'],
            [Carbon::tomorrow()->toDateString(), 'Le relevé ne peut pas être postérieur à la date du jour.'],
        ];

        foreach ($tentatives as [$date, $message]) {
            try {
                $this->service()->enregistrer($this->donneesReleve($campaign, $date, 100), $user);

                $this->fail("Le relevé du {$date} aurait dû être refusé.");
            } catch (RegleMetierException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $this->assertSame(0, ReleveNiveauEau::withoutGlobalScopes()->count());
    }

    public function test_un_agent_d_une_autre_exploitation_est_refuse(): void
    {
        [$user, $campaign] = $this->contexte();

        $intrus = User::factory()->agent()->pourExploitation(Exploitation::factory()->create())->create();

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('une autre exploitation');

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $intrus);
    }

    public function test_la_comparaison_prevu_reel_confronte_la_prevision_aux_irrigations_reelles(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterNeedsForecastService::class)->generer($campaign);

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);
        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 65, 10), $user);

        $comparaison = $this->service()->comparaisonPrevuReel($campaign, Carbon::parse('2026-05-07'));

        $this->assertSame(1828.57, $comparaison['prevu_m3']);
        $this->assertSame(100.0, $comparaison['reel_m3']);
        $this->assertSame(-1728.57, $comparaison['ecart_m3']);
        $this->assertSame(-94.53, $comparaison['ecart_pct']);
        $this->assertSame(880.0, $comparaison['pertes_normales_m3']);
        $this->assertSame(120.0, $comparaison['pertes_anormales_m3']);
        $this->assertSame(65.0, $comparaison['dernier_niveau_mm']);
    }

    public function test_la_comparaison_sans_prevision_renvoie_un_prevu_indetermine(): void
    {
        [$user, $campaign] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);

        $comparaison = $this->service()->comparaisonPrevuReel($campaign, Carbon::parse('2026-05-03'));

        $this->assertNull($comparaison['prevu_m3']);
        $this->assertNull($comparaison['ecart_m3']);
        $this->assertNull($comparaison['ecart_pct']);
        $this->assertSame(0.0, $comparaison['reel_m3']);
        $this->assertSame(100.0, $comparaison['dernier_niveau_mm']);
    }

    public function test_l_evolution_renvoie_les_releves_dans_l_ordre_chronologique(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-09', 40);
        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-10', 60, 10), $user);

        $releves = $this->service()->evolution($campaign);

        $this->assertCount(2, $releves);
        $this->assertSame('2026-05-07', $releves[0]->date_releve->toDateString());
        $this->assertSame('2026-05-10', $releves[1]->date_releve->toDateString());
        $this->assertSame(100.0, (float) $releves[1]->niveau_precedent_mm);
    }

    public function test_les_releves_sont_isoles_par_exploitation(): void
    {
        [$userA, $campaignA] = $this->contexte();
        [$userB, $campaignB] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaignA, '2026-05-03', 100), $userA);
        $this->service()->enregistrer($this->donneesReleve($campaignB, '2026-05-03', 100), $userB);

        $this->actingAs($userA);

        $this->assertSame(1, ReleveNiveauEau::count());
        $this->assertSame(2, ReleveNiveauEau::withoutGlobalScopes()->count());
    }

    public function test_la_contrainte_de_coherence_empeche_un_niveau_negatif_en_base(): void
    {
        [$user, $campaign] = $this->contexte();

        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);

        $this->expectException(QueryException::class);

        DB::table('releves_niveau_eau')
            ->where('id', $releve->getKey())
            ->update(['niveau_constate_mm' => -5]);
    }

    public function test_le_releve_est_journalise_dans_le_canal_audit(): void
    {
        [$user, $campaign] = $this->contexte();

        Log::spy();
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);

        Log::shouldHaveReceived('channel')->with('audit')->once();
    }

    public function test_une_fuite_probable_est_tracee_dans_le_journal_d_audit(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);
        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 50, 10), $user);

        $entree = JournalAudit::withoutGlobalScopes()
            ->where('action', 'fuite_probable')
            ->first();

        $this->assertNotNull($entree);
        $this->assertSame(ReleveNiveauEau::class, $entree->entite);
        $this->assertSame(420.0, (float) $entree->nouvelles_valeurs['pertes_anormales_m3']);
    }

    public function test_le_listener_de_notification_de_fuite_est_mis_en_file(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        Queue::fake();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);
        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 50, 10), $user);

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job): bool => $job->class === NotifierFuiteProbable::class,
        );
    }

    public function test_la_notification_de_fuite_est_envoyee_aux_responsables_sur_database_et_mail(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $responsable = User::factory()->responsable()->pourExploitation($user->exploitation)->create();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);
        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 50, 10), $user);

        Notification::fake();

        app(NotifierFuiteProbable::class)->handle(new FuiteProbableDetectee($releve));

        Notification::assertSentTo(
            $responsable,
            FuiteEauDetecteeNotification::class,
            fn (FuiteEauDetecteeNotification $notification, array $channels): bool => $notification->releve->is($releve)
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true),
        );
    }

    public function test_le_message_de_notification_detaille_l_ecart_et_les_pertes(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $campaign->culture->forceFill(['nom' => 'Riz'])->save();
        $campaign->forceFill(['campagne' => '2026'])->save();

        $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-03', 100), $user);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-05', 100);
        $releve = $this->service()->enregistrer($this->donneesReleve($campaign, '2026-05-07', 50, 10), $user);
        $releve->load('parcelle');

        $data = (new FuiteEauDetecteeNotification($releve))->toArray($user);

        $this->assertSame(
            'Fuite probable : 21,00 mm manquants dans la rizière RZ-01 (niveau attendu 71,00 mm, constaté 50,00 mm), pertes estimées à 420,00 m³.',
            $data['message'],
        );
    }

    private function service(): SuiviNiveauEauService
    {
        return app(SuiviNiveauEauService::class);
    }

    /**
     * Culture de cycle court (20 jours) : pépinière jours 0-3 (12 mm/j),
     * végétatif jours 4-10 (8 mm/j), reproductif jours 11-15 (10 mm/j),
     * maturation jours 16-19 (5 mm/j). Rizière de 2 ha semée le 2026-05-01.
     *
     * @return array{0: User, 1: CycleCulture, 2: Parcelle, 3: WaterSource}
     */
    private function contexte(): array
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->agent()->pourExploitation($exploitation)->create();

        $parcelle = Parcelle::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'code' => 'RZ-01',
            'superficie_ha' => 2,
        ]);

        $culture = Culture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'duree_cycle_jours' => 20,
        ]);

        $campaign = CycleCulture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'statut' => StatutCycleCulture::EnCours,
            'date_semis' => '2026-05-01',
            'surface_ha' => 2,
        ]);

        $source = WaterSource::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'debit_m3_h' => 100,
        ]);

        return [$user, $campaign, $parcelle, $source];
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesReleve(CycleCulture $campaign, string $date, float $niveau, float $precipitations = 0): array
    {
        return [
            'campaign_id' => $campaign->getKey(),
            'date_releve' => $date,
            'niveau_constate_mm' => $niveau,
            'precipitations_mm' => $precipitations,
        ];
    }

    private function irrigation(CycleCulture $campaign, Parcelle $parcelle, WaterSource $source, string $date, float $volume): Irrigation
    {
        return Irrigation::withoutGlobalScopes()->create([
            'exploitation_id' => $campaign->exploitation_id,
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'user_id' => null,
            'date' => $date,
            'duree_minutes' => 60,
            'debit_m3_h' => $volume,
            'volume_m3' => $volume,
        ]);
    }
}
