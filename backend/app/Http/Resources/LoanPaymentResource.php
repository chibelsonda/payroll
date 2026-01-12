<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanPaymentResource extends JsonResource
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
            'loan_uuid' => $this->loan?->uuid,
            'loan' => $this->whenLoaded('loan', fn() => new LoanResource($this->loan)),
            'payroll_uuid' => $this->payroll?->uuid,
            'payroll' => $this->whenLoaded('payroll', fn() => [
                'uuid' => $this->payroll->uuid,
                'payroll_run_uuid' => $this->payroll->payrollRun?->uuid,
            ]),
            'amount' => (string) $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
