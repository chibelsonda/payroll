<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can update attendance settings
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // company_id is automatically set by SetActiveCompany middleware
            'default_shift_start' => 'required|date_format:H:i:s',
            'default_break_start' => 'required|date_format:H:i:s',
            'default_break_end' => 'required|date_format:H:i:s',
            'default_shift_end' => 'required|date_format:H:i:s',
            'max_shift_hours' => 'required|integer|min:1|max:24',
            'auto_close_missing_out' => 'boolean',
            'auto_deduct_break' => 'boolean',
            'enable_auto_correction' => 'boolean',
        ];
    }
}
