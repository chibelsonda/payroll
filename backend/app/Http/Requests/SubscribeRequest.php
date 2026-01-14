<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConvertsUuidsToIds;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    use ConvertsUuidsToIds;

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
        return [
            'plan_uuid' => 'required|string|exists:plans,uuid',
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => ['required', 'string', Rule::in(['gcash', 'card'])],
            'provider' => 'sometimes|string|in:paymongo',
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
            unset($validated['plan_uuid']);
        }

        return $validated;
    }
}
