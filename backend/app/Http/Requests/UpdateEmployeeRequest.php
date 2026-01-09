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
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'employee_id' => 'sometimes|string|unique:employees,employee_id,' . ($employee ? $employee->id : 'NULL'),
        ];

        // Add email validation with uniqueness check
        if ($userId) {
            $rules['email'] = 'sometimes|string|email|max:255|unique:users,email,' . $userId;
        } else {
            $rules['email'] = 'sometimes|string|email|max:255';
        }

        return $rules;
    }
}
