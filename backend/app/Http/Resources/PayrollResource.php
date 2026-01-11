<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
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
            'payroll_run_uuid' => $this->payrollRun?->uuid,
            'payroll_run' => $this->whenLoaded('payrollRun', fn() => new PayrollRunResource($this->payrollRun)),
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
            'basic_salary' => (string) $this->basic_salary,
            'gross_pay' => (string) $this->gross_pay,
            'total_deductions' => (string) $this->total_deductions,
            'net_pay' => (string) $this->net_pay,
            'earnings' => $this->whenLoaded('earnings', fn() => PayrollEarningResource::collection($this->earnings)),
            'deductions' => $this->whenLoaded('deductions', fn() => PayrollDeductionResource::collection($this->deductions)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
