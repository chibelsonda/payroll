<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeductionRequest extends FormRequest
{
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
        $deduction = $this->route('deduction');
        return [
            'name' => 'sometimes|required|string|max:255|unique:deductions,name,' . ($deduction ? $deduction->id : 'NULL'),
            'type' => 'sometimes|required|string|in:fixed,percentage',
        ];
    }
}
