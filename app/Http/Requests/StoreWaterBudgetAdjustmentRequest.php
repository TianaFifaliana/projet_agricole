<?php

namespace App\Http\Requests;

use App\Models\WaterBudgetAdjustment;
use Illuminate\Foundation\Http\FormRequest;

class StoreWaterBudgetAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WaterBudgetAdjustment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'volume_supplementaire_m3' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'motif' => ['required', 'string', 'max:2000'],
        ];
    }
}
