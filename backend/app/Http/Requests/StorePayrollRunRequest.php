<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConvertsUuidsToIds;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    use ConvertsUuidsToIds;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_uuid' => 'required|string|exists:companies,uuid',
            // ID field (set by prepareForValidation, included here so it appears in validated())
            'company_id' => 'required|exists:companies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'pay_date' => 'required|date|after_or_equal:period_end',
        ];
    }

    /**
     * Prepare the data for validation and convert UUIDs to IDs
     */
    protected function prepareForValidation(): void
    {
        $data = $this->convertUuidsToIds($this->all());
        $this->merge($data);
    }

    /**
     * Get validated data with UUIDs converted to IDs and UUID fields removed
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Remove UUID fields from validated data (IDs are already there)
        if (is_array($validated)) {
            unset($validated['company_uuid']);
        }

        return $validated;
    }
}
