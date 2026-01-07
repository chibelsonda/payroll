<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $subject = $this->route('subject');
        return $this->user()->can('update', $subject);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $subject = $this->route('subject');
        return [
            'code' => 'sometimes|string|unique:subjects,code,' . $subject->id,
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'credits' => 'sometimes|integer|min:1',
        ];
    }
}
