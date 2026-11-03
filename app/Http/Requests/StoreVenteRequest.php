<?php

namespace App\Http\Requests;

use App\Models\Vente;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreVenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vente::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exploitation_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->isAdministrateur() ?? false),
                'nullable',
                'integer',
                'exists:exploitations,id',
            ],
            'partenaire_id' => ['required', 'integer', $this->existsForExploitation('partenaires')],
            'date_vente' => ['required', 'date', 'before_or_equal:today'],
            'taux_tva' => ['nullable', 'numeric', 'between:0,30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.lot_stock_id' => ['nullable', 'integer', $this->existsForExploitation('lots_stock')],
            'lignes.*.designation' => ['required', 'string', 'max:150'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'lignes.*.unite' => ['required', 'string', 'max:20'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
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
