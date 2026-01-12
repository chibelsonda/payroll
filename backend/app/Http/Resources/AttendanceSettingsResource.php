<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSettingsResource extends JsonResource
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
            'company_id' => $this->company_id,
            'company_uuid' => $this->company->uuid ?? null,
            'default_shift_start' => $this->default_shift_start instanceof \DateTimeInterface
                ? $this->default_shift_start->format('H:i:s')
                : $this->default_shift_start,
            'default_break_start' => $this->default_break_start instanceof \DateTimeInterface
                ? $this->default_break_start->format('H:i:s')
                : $this->default_break_start,
            'default_break_end' => $this->default_break_end instanceof \DateTimeInterface
                ? $this->default_break_end->format('H:i:s')
                : $this->default_break_end,
            'default_shift_end' => $this->default_shift_end instanceof \DateTimeInterface
                ? $this->default_shift_end->format('H:i:s')
                : $this->default_shift_end,
            'max_shift_hours' => $this->max_shift_hours,
            'auto_close_missing_out' => $this->auto_close_missing_out,
            'auto_deduct_break' => $this->auto_deduct_break,
            'enable_auto_correction' => $this->enable_auto_correction ?? true,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
