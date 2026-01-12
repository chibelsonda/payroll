<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'date' => $this->date?->format('Y-m-d'),
            'time_in' => $this->time_in?->format('H:i:s'),
            'time_out' => $this->time_out?->format('H:i:s'),
            'hours_worked' => (string) $this->hours_worked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
