<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Http\Resources\AttendanceSettingsResource;
use App\Models\AttendanceSettings;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSettingsController extends BaseApiController
{
    /**
     * Get attendance settings for a company
     * If company_uuid is provided, get that company's settings
     * Otherwise, get settings for the first company (for admin convenience)
     */
    public function show(Request $request): JsonResponse
    {
        $companyUuid = $request->query('company_uuid');
        
        if ($companyUuid) {
            $company = Company::where('uuid', $companyUuid)->first();
            if (!$company) {
                return $this->notFoundResponse('Company not found');
            }
            $companyId = $company->id;
        } else {
            // Get first company for admin convenience
            $company = Company::first();
            if (!$company) {
                return $this->errorResponse('No company found. Please create a company first.', [], [], 404);
            }
            $companyId = $company->id;
        }

        $settings = AttendanceSettings::where('company_id', $companyId)->first();

        // If no settings exist, return defaults
        if (!$settings) {
            $defaults = AttendanceSettings::getDefaults();
            return $this->successResponse([
                'company_uuid' => $company->uuid,
                'company_id' => $companyId,
                'default_shift_start' => $defaults['default_shift_start'],
                'default_break_start' => $defaults['default_break_start'],
                'default_break_end' => $defaults['default_break_end'],
                'default_shift_end' => $defaults['default_shift_end'],
                'max_shift_hours' => $defaults['max_shift_hours'],
                'auto_close_missing_out' => $defaults['auto_close_missing_out'],
                'auto_deduct_break' => $defaults['auto_deduct_break'],
                'enable_auto_correction' => $defaults['enable_auto_correction'],
                'is_default' => true,
            ], 'Using default attendance settings');
        }

        return $this->successResponse(
            new AttendanceSettingsResource($settings->load('company')),
            'Attendance settings retrieved successfully'
        );
    }

    /**
     * Update or create attendance settings for a company
     */
    public function update(UpdateAttendanceSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $companyUuid = $validated['company_uuid'];
        
        $company = Company::where('uuid', $companyUuid)->first();
        if (!$company) {
            return $this->notFoundResponse('Company not found');
        }

        // Remove company_uuid from validated data as we'll use company_id
        unset($validated['company_uuid']);
        $validated['company_id'] = $company->id;

        // Update or create settings
        $settings = AttendanceSettings::updateOrCreate(
            ['company_id' => $company->id],
            $validated
        );

        return $this->successResponse(
            new AttendanceSettingsResource($settings->load('company')),
            'Attendance settings updated successfully'
        );
    }
}
