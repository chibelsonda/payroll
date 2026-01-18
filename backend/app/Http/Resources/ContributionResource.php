<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionResource extends JsonResource
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
            'name' => $this->name,
            'employee_share' => (string) $this->employee_share,
            'employer_share' => (string) $this->employer_share,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
