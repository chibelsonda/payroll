<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
        $email = $this->input('email');
        
        // Check if there's a pending invitation for this email
        $hasPendingInvitation = $email && Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        // If there's a pending invitation, allow registration even if email exists
        // This handles the case where a user was invited but hasn't registered yet
        $emailRule = $hasPendingInvitation
            ? 'required|string|email|max:255'
            : 'required|string|email|max:255|unique:users';

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => $emailRule,
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,staff,employee,owner',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'The email has already been taken. If you have an account, please log in instead.',
        ];
    }
}
