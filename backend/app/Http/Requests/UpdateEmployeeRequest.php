<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
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
        $data = $this->all();

        // Convert UUIDs to IDs for internal processing
        // Handle company_uuid - always set company_id if company_uuid is present in request
        if (array_key_exists('company_uuid', $data)) {
            if (!empty($data['company_uuid']) && is_string($data['company_uuid'])) {
                $company = \App\Models\Company::where('uuid', $data['company_uuid'])->first();
                if ($company) {
                    $data['company_id'] = $company->id;
                }
                // If UUID is invalid, leave company_id unset - validation will catch it
            } else {
                // Null or empty value - explicitly set to null
                $data['company_id'] = null;
            }
            // Don't unset company_uuid yet - let validation check it first
        }

        // Handle department_uuid
        if (array_key_exists('department_uuid', $data)) {
            if (!empty($data['department_uuid']) && is_string($data['department_uuid'])) {
                $department = \App\Models\Department::where('uuid', $data['department_uuid'])->first();
                if ($department) {
                    $data['department_id'] = $department->id;
                }
                // If UUID is invalid, leave department_id unset - validation will catch it
            } else {
                // Null or empty value - explicitly set to null
                $data['department_id'] = null;
            }
        }

        // Handle position_uuid
        if (array_key_exists('position_uuid', $data)) {
            if (!empty($data['position_uuid']) && is_string($data['position_uuid'])) {
                $position = \App\Models\Position::where('uuid', $data['position_uuid'])->first();
                if ($position) {
                    $data['position_id'] = $position->id;
                }
                // If UUID is invalid, leave position_id unset - validation will catch it
            } else {
                // Null or empty value - explicitly set to null
                $data['position_id'] = null;
            }
        }

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
