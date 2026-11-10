<?php

namespace App\Http\Requests;

use App\Enums\EtatSourceEau;
use App\Enums\StatutCycleCulture;
use App\Models\CycleCulture;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\Scopes\ExploitationScope;
use App\Models\WaterSource;
use App\Rules\WaterQuotaRule;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreIrrigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Irrigation::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $campaign = $this->campaign();
        $parcelle = $this->parcelle();
        $source = $this->waterSource();

        $debit = (float) ($this->input('debit_m3_h') ?? ($source?->debit_m3_h ?? 0));
        $duree = (int) $this->input('duree_minutes', 0);
        $volume = round($debit * ($duree / 60), 2);

        $rules = [
            'campaign_id' => ['required', 'integer', $this->existsForExploitation('cycles_culturaux', [
                'statut' => StatutCycleCulture::EnCours->value,
            ])],
            'production_unit_id' => ['required', 'integer', $this->existsForExploitation('parcelles')],
            'water_source_id' => ['required', 'integer', $this->existsForExploitation('water_sources', [
                'etat' => EtatSourceEau::Operationnelle->value,
            ])],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'duree_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'debit_m3_h' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ];

        if ($source !== null) {
            $rules['debit_m3_h'][] = function (string $attribute, mixed $value, Closure $fail) use ($source): void {
                if ($value !== null && (float) $value > (float) $source->debit_m3_h + 0.0001) {
                    $fail("Le débit ne peut pas dépasser celui de la source d'eau ({$source->debit_m3_h} m³/h).");
                }
            };
        }

        if ($campaign !== null && $parcelle !== null && $volume > 0) {
            $capacite = (float) $parcelle->superficie_ha * (float) config('irrigation.capacite_m3_par_ha', 5000);

            $rules['duree_minutes'][] = function (string $attribute, mixed $value, Closure $fail) use ($volume, $capacite, $parcelle): void {
                if ($volume > $capacite + 0.0001) {
                    $fail(sprintf(
                        'Le volume calculé (%s m³) dépasse la capacité de la rizière %s (%s m³).',
                        number_format($volume, 2, ',', ' '),
                        $parcelle->code,
                        number_format($capacite, 2, ',', ' '),
                    ));
                }
            };

            $rules['duree_minutes'] = [...$rules['duree_minutes'], ...$this->reglesQuota($campaign, $volume)];
        }

        return $rules;
    }

    /**
     * Identifiant de la campagne validée : champ du formulaire par défaut.
     */
    protected function campaignId(): int|string|null
    {
        return $this->input('campaign_id');
    }

    /**
     * Règles de pré-validation du quota d'eau.
     *
     * @return array<int, mixed>
     */
    protected function reglesQuota(CycleCulture $campaign, float $volume): array
    {
        return [new WaterQuotaRule($campaign, $volume)];
    }

    private function campaign(): ?CycleCulture
    {
        $id = $this->campaignId();

        if (! is_numeric($id)) {
            return null;
        }

        return CycleCulture::withoutGlobalScope(ExploitationScope::class)->find((int) $id);
    }

    private function parcelle(): ?Parcelle
    {
        return $this->modele(Parcelle::class, 'production_unit_id');
    }

    private function waterSource(): ?WaterSource
    {
        return $this->modele(WaterSource::class, 'water_source_id');
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $classe
     * @return TModel|null
     */
    private function modele(string $classe, string $cle): ?object
    {
        $id = $this->input($cle);

        if (! is_numeric($id)) {
            return null;
        }

        return $classe::withoutGlobalScope(ExploitationScope::class)->find((int) $id);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function existsForExploitation(string $table, array $conditions = []): Exists
    {
        $rule = Rule::exists($table, 'id');
        $exploitationId = $this->user()?->exploitation_id;

        if ($exploitationId !== null) {
            $rule->where(fn (Builder|EloquentBuilder $query) => $query->where('exploitation_id', $exploitationId));
        }

        foreach ($conditions as $column => $value) {
            $rule->where(fn (Builder|EloquentBuilder $query) => $query->where($column, $value));
        }

        return $rule;
    }
}
