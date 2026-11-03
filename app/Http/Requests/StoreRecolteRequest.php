<?php

namespace App\Http\Requests;

use App\Models\CycleCulture;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecolteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cycle = $this->route('cycle');

        return $cycle instanceof CycleCulture
            && ($this->user()?->can('enregistrerRecolte', $cycle) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantite_kg' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'date_recolte' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
