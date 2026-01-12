<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConvertsUuidsToIds;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDeductionRequest extends FormRequest
{
    use ConvertsUuidsToIds;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_uuid' => 'required|string|exists:employees,uuid',
            'employee_id' => 'required|exists:employees,id',
            'deduction_uuid' => 'required|string|exists:deductions,uuid',
            'deduction_id' => 'required|exists:deductions,id',
            'amount' => 'required|numeric|min:0',
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

        if (is_array($validated)) {
            unset($validated['employee_uuid'], $validated['deduction_uuid']);
        }

        return $validated;
    }
}
