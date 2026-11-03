<?php

namespace App\Http\Requests;

use App\Models\Parcelle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParcelleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $parcelle = $this->route('parcelle');

        return $parcelle instanceof Parcelle
            && ($this->user()?->can('update', $parcelle) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parcelle = $this->route('parcelle');
        $exploitationId = $this->user()?->exploitation_id ?? $parcelle?->exploitation_id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('parcelles', 'code')
                    ->where('exploitation_id', $exploitationId)
                    ->ignore($parcelle?->getKey()),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'superficie_ha' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'type_sol' => ['nullable', 'string', 'max:60'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
