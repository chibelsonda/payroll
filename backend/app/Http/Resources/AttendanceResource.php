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
            'hours_worked' => (string) $this->hours_worked,
            'status' => $this->status,
            'is_incomplete' => $this->is_incomplete,
            'needs_review' => $this->needs_review,
            'is_auto_corrected' => $this->is_auto_corrected,
            'is_locked' => $this->is_locked ?? false,
            'correction_reason' => $this->correction_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
