<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'company' => $this->whenLoaded('company', fn() => [
                'uuid' => $this->company->uuid,
                'name' => $this->company->name,
                'registration_no' => $this->company->registration_no,
            ]),
            'company_uuid' => $this->company?->uuid,
            'department' => $this->whenLoaded('department', fn() => [
                'uuid' => $this->department->uuid,
                'name' => $this->department->name,
            ]),
            'department_uuid' => $this->department?->uuid,
            'position' => $this->whenLoaded('position', fn() => [
                'uuid' => $this->position->uuid,
                'title' => $this->position->title,
            ]),
            'position_uuid' => $this->position?->uuid,
            'employee_no' => $this->employee_no,
            'employment_type' => $this->employment_type,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'termination_date' => $this->termination_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
