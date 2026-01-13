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
     * Get attendance settings for the active company
     * Company is set by SetActiveCompany middleware
     */
    public function show(Request $request): JsonResponse
    {
        $companyId = app('active_company_id');

        if (!$companyId) {
            return $this->errorResponse('Active company not set', [], [], 400);
        }

        $company = Company::find($companyId);
        if (!$company) {
            return $this->notFoundResponse('Company not found');
        }

        // AttendanceSettings model is company-scoped, so we can query directly
        $settings = AttendanceSettings::first();

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
     * Update or create attendance settings for the active company
     * Company is set by SetActiveCompany middleware
     */
    public function update(UpdateAttendanceSettingsRequest $request): JsonResponse
    {
        $companyId = app('active_company_id');

        if (!$companyId) {
            return $this->errorResponse('Active company not set', [], [], 400);
        }

        $company = Company::find($companyId);
        if (!$company) {
            return $this->notFoundResponse('Company not found');
        }

        $validated = $request->validated();

        // company_id is automatically set by CompanyScopedModel when creating
        // Update or create settings for the active company
        $settings = AttendanceSettings::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return $this->successResponse(
            new AttendanceSettingsResource($settings->load('company')),
            'Attendance settings updated successfully'
        );
    }
}
