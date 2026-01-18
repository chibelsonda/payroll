<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContributionRequest extends FormRequest
{
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
        $contribution = $this->route('contribution');
        return [
            'name' => 'sometimes|required|string|max:255|unique:contributions,name,' . ($contribution ? $contribution->id : 'NULL'),
            'employee_share' => 'sometimes|required|numeric|min:0|max:100',
            'employer_share' => 'sometimes|required|numeric|min:0|max:100',
        ];
    }
}
