<?php

namespace App\Http\Requests;

use App\Enums\StatutCycleCulture;
use App\Models\CampaignWaterBudget;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreWaterBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CampaignWaterBudget::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => [
                'required',
                'integer',
                $this->existsForExploitation('cycles_culturaux', [
                    'statut' => StatutCycleCulture::EnCours->value,
                ]),
                Rule::unique('campaign_water_budgets', 'campaign_id'),
            ],
            'volume_alloue_m3' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'seuil_alerte_pct' => ['nullable', 'numeric', 'between:0,100'],
        ];
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
