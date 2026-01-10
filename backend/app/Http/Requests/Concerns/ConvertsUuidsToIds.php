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
