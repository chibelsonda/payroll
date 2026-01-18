<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'employee_uuid' => $this->employee?->uuid,
            'employee' => $this->whenLoaded('employee', fn() => [
                'uuid' => $this->employee->uuid,
                'employee_no' => $this->employee->employee_no,
                'user' => $this->employee->user ? [
                    'first_name' => $this->employee->user->first_name,
                    'last_name' => $this->employee->user->last_name,
                ] : null,
            ]),
            'amount' => (string) $this->amount,
            'effective_from' => $this->effective_from->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
