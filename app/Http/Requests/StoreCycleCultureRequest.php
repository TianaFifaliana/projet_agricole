<?php

namespace App\Http\Requests;

use App\Models\CycleCulture;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreCycleCultureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CycleCulture::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parcelle_id' => ['required', 'integer', $this->existsForExploitation('parcelles')],
            'culture_id' => ['required', 'integer', $this->existsForExploitation('cultures')],
            'campagne' => ['required', 'string', 'regex:/^\d{4}(\/\d{4})?$/'],
            'date_semis' => ['required', 'date'],
            'date_recolte_prevue' => ['nullable', 'date', 'after_or_equal:date_semis'],
            'surface_ha' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
