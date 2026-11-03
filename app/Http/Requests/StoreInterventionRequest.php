<?php

namespace App\Http\Requests;

use App\Enums\TypeIntervention;
use App\Models\Intervention;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Intervention::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_values(array_filter(
            array_map(fn (TypeIntervention $type): string => $type->value, TypeIntervention::cases()),
            fn (string $type): bool => $type !== TypeIntervention::Recolte->value,
        ));

        return [
            'parcelle_id' => ['required', 'integer', $this->existsForExploitation('parcelles')],
            'cycle_culture_id' => ['nullable', 'integer', $this->existsForExploitation('cycles_culturaux')],
            'intrant_id' => ['nullable', 'integer', $this->existsForExploitation('intrants')],
            'type' => ['required', Rule::in($types)],
            'date_intervention' => ['required', 'date', 'before_or_equal:today'],
            'quantite' => ['nullable', 'numeric', 'min:0'],
            'unite' => ['nullable', 'string', 'max:20'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function existsForExploitation(string $table): Exists
    {
        $rule = Rule::exists($table, 'id');
        $exploitationId = $this->user()?->exploitation_id;

        if ($exploitationId !== null) {
            $rule->where(fn (Builder|EloquentBuilder $query) => $query->where('exploitation_id', $exploitationId));
        }

        return $rule;
    }
}
