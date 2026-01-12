<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConvertsUuidsToIds;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    use ConvertsUuidsToIds;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employee::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // User fields
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',

            // Employee fields
            'employee_no' => 'required|string|unique:employees,employee_no',
            // company_id is automatically set by SetActiveCompany middleware
            'department_uuid' => 'nullable|exists:departments,uuid',
            'position_uuid' => 'nullable|exists:positions,uuid',
            // ID fields (set by prepareForValidation, included here so they appear in validated())
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'nullable|string|in:regular,contractual,probationary',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after:hire_date',
            'status' => 'nullable|string|in:active,inactive,terminated',
        ];
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
        // company_id is set automatically by middleware, so don't remove it if present
        if (is_array($validated)) {
            unset($validated['department_uuid'], $validated['position_uuid']);
        }

        return $validated;
    }
}
