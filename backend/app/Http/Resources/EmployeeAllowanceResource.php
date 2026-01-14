<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAllowanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => (string) $this->id, // Use ID as UUID since migration doesn't have UUID column
            'employee_uuid' => $this->employee->uuid ?? null,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'uuid' => $this->employee->uuid,
                    'employee_no' => $this->employee->employee_no,
                    'user' => [
                        'first_name' => $this->employee->user->first_name ?? null,
                        'last_name' => $this->employee->user->last_name ?? null,
                    ],
                ];
            }),
            'type' => $this->type,
            'description' => $this->description,
            'amount' => (string) $this->amount,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
