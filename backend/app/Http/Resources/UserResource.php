<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Map Spatie roles to frontend role format
        $role = 'student'; // default
        $roles = $this->getRoleNames();

        if ($roles->contains('admin')) {
            $role = 'admin';
        } elseif ($roles->contains('user')) {
            $role = 'student';
        } elseif ($roles->contains('staff')) {
            $role = 'student'; // or map to appropriate frontend role
        }

        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name, // Full name accessor
            'email' => $this->email,
            'role' => $role, // Mapped role for frontend
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'uuid' => $this->student->uuid,
                    'user_id' => $this->student->user_id,
                    'student_id' => $this->student->student_id,
                    'created_at' => $this->student->created_at?->toIso8601String(),
                    'updated_at' => $this->student->updated_at?->toIso8601String(),
                ];
            }),
        ];
    }
}
