<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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
                'user' => $this->employee->relationLoaded('user') ? [
                    'uuid' => $this->employee->user->uuid,
                    'first_name' => $this->employee->user->first_name,
                    'last_name' => $this->employee->user->last_name,
                    'email' => $this->employee->user->email,
                ] : null,
            ]),
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
