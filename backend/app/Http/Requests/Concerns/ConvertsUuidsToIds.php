<?php

namespace App\Http\Requests\Concerns;

trait ConvertsUuidsToIds
{
    /**
     * Convert UUID fields to their corresponding ID fields in the request data.
     *
     * This method handles the conversion of:
     * - company_uuid -> company_id
     * - department_uuid -> department_id
     * - position_uuid -> position_id
     * - employee_uuid -> employee_id
     * - deduction_uuid -> deduction_id
     * - loan_uuid -> loan_id
     * - payroll_uuid -> payroll_id
     * - payroll_run_uuid -> payroll_run_id
     *
     * @param array $data The request data array
     * @return array The data array with ID fields added (UUID fields remain for validation)
     */
    protected function convertUuidsToIds(array $data): array
    {
        // Mapping of UUID field names to their model classes and ID field names
        $uuidMappings = [
            'company_uuid' => [
                'model' => \App\Models\Company::class,
                'id_field' => 'company_id',
            ],
            'department_uuid' => [
                'model' => \App\Models\Department::class,
                'id_field' => 'department_id',
            ],
            'position_uuid' => [
                'model' => \App\Models\Position::class,
                'id_field' => 'position_id',
            ],
            'employee_uuid' => [
                'model' => \App\Models\Employee::class,
                'id_field' => 'employee_id',
            ],
            'deduction_uuid' => [
                'model' => \App\Models\Deduction::class,
                'id_field' => 'deduction_id',
            ],
            'loan_uuid' => [
                'model' => \App\Models\Loan::class,
                'id_field' => 'loan_id',
            ],
            'payroll_uuid' => [
                'model' => \App\Models\Payroll::class,
                'id_field' => 'payroll_id',
            ],
            'payroll_run_uuid' => [
                'model' => \App\Models\PayrollRun::class,
                'id_field' => 'payroll_run_id',
            ],
            'contribution_uuid' => [
                'model' => \App\Models\Contribution::class,
                'id_field' => 'contribution_id',
            ],
            'plan_uuid' => [
                'model' => \App\Models\Plan::class,
                'id_field' => 'plan_id',
            ],
        ];

        foreach ($uuidMappings as $uuidField => $mapping) {
            if (array_key_exists($uuidField, $data)) {
                if (!empty($data[$uuidField]) && is_string($data[$uuidField])) {
                    // Valid UUID provided - find the model and get its ID
                    $model = $mapping['model']::where('uuid', $data[$uuidField])->first();
                    if ($model) {
                        $data[$mapping['id_field']] = $model->id;
                    }
                    // If UUID is invalid, leave ID field unset - validation will catch it
                } else {
                    // Null or empty value - explicitly set ID to null
                    $data[$mapping['id_field']] = null;
                }
                // UUID field remains in data for validation purposes
            }
        }

        return $data;
    }
}
