<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDeductionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_uuid' => $this->employee?->uuid,
            'employee' => $this->whenLoaded('employee', fn() => [
                'uuid' => $this->employee->uuid,
                'employee_no' => $this->employee->employee_no,
                'user' => $this->employee->relationLoaded('user') ? [
                    'uuid' => $this->employee->user->uuid,
                    'first_name' => $this->employee->user->first_name,
                    'last_name' => $this->employee->user->last_name,
                    'email' => $this->employee->user->email,
                ] : null,
            ]),
            'deduction_uuid' => $this->deduction?->uuid,
            'deduction' => $this->whenLoaded('deduction', fn() => new DeductionResource($this->deduction)),
            'amount' => (string) $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
