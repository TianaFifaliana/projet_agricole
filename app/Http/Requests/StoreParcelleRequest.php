<?php

namespace App\Http\Requests;

use App\Models\Parcelle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParcelleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Parcelle::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $exploitationId = $this->user()?->exploitation_id ?? $this->input('exploitation_id');

        return [
            'exploitation_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->isAdministrateur() ?? false),
                'nullable',
                'integer',
                'exists:exploitations,id',
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('parcelles', 'code')->where('exploitation_id', $exploitationId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'superficie_ha' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'type_sol' => ['nullable', 'string', 'max:60'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'code',
            'nom' => 'nom',
            'superficie_ha' => 'superficie (ha)',
            'type_sol' => 'type de sol',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
        ];
    }
}
