<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConvertsUuidsToIds;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    use ConvertsUuidsToIds;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        return $this->user()->can('update', $employee);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        // Ensure user relationship is loaded if needed
        if ($employee && !$employee->relationLoaded('user')) {
            $employee->load('user');
        }

        $userId = $employee ? $employee->user_id : null;

        $rules = [
            // User fields
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',

            // Employee fields
            'employee_no' => 'sometimes|string|unique:employees,employee_no,' . ($employee ? $employee->id : 'NULL'),
            'company_uuid' => 'sometimes|nullable|exists:companies,uuid',
            'department_uuid' => 'sometimes|nullable|exists:departments,uuid',
            'position_uuid' => 'sometimes|nullable|exists:positions,uuid',
            // ID fields (set by prepareForValidation, included here so they appear in validated())
            'company_id' => 'sometimes|nullable|exists:companies,id',
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'position_id' => 'sometimes|nullable|exists:positions,id',
            'employment_type' => 'sometimes|nullable|string|in:regular,contractual,probationary',
            'hire_date' => 'sometimes|nullable|date',
            'termination_date' => 'sometimes|nullable|date|after:hire_date',
            'status' => 'sometimes|nullable|string|in:active,inactive,terminated',
        ];

        // Add email validation with uniqueness check
        if ($userId) {
            $rules['email'] = 'sometimes|string|email|max:255|unique:users,email,' . $userId;
        } else {
            $rules['email'] = 'sometimes|string|email|max:255';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation and convert UUIDs to IDs
     * Note: UUIDs are validated first, then converted to IDs after validation passes
     */
    protected function prepareForValidation(): void
    {
        $data = $this->convertUuidsToIds($this->all());
        $this->merge($data);
    }

    /**
     * Get validated data with UUIDs converted to IDs and UUID fields removed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Remove UUID fields from validated data (IDs are already there)
        if (is_array($validated)) {
            unset($validated['company_uuid'], $validated['department_uuid'], $validated['position_uuid']);
        }

        return $validated;
    }
}
