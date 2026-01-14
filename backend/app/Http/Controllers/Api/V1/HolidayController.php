<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;

class HolidayController extends BaseApiController
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    /**
     * Get all holidays
     */
    public function index(): JsonResponse
    {
        $holidays = $this->holidayService->getAllHolidays();

        $meta = [
            'pagination' => [
                'current_page' => $holidays->currentPage(),
                'last_page' => $holidays->lastPage(),
                'per_page' => $holidays->perPage(),
                'total' => $holidays->total(),
                'from' => $holidays->firstItem(),
                'to' => $holidays->lastItem(),
                'has_more_pages' => $holidays->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            HolidayResource::collection($holidays->items()),
            'Holidays retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new holiday
     */
    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $companyId = app('active_company_id');
        $data = $request->validated();
        $data['company_id'] = $companyId;

        $holiday = $this->holidayService->createHoliday($data);

        return $this->createdResponse(
            new HolidayResource($holiday->load('company')),
            'Holiday created successfully'
        );
    }

    /**
     * Get a single holiday by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        $holiday = $this->holidayService->findHolidayByUuid($uuid);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', [], [], 404);
        }

        return $this->successResponse(
            new HolidayResource($holiday),
            'Holiday retrieved successfully'
        );
    }

    /**
     * Update a holiday
     */
    public function update(UpdateHolidayRequest $request, string $uuid): JsonResponse
    {
        $holiday = $this->holidayService->findHolidayByUuid($uuid);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', [], [], 404);
        }

        $holiday = $this->holidayService->updateHoliday($holiday, $request->validated());

        return $this->successResponse(
            new HolidayResource($holiday),
            'Holiday updated successfully'
        );
    }

    /**
     * Delete a holiday
     */
    public function destroy(string $uuid): JsonResponse
    {
        $holiday = $this->holidayService->findHolidayByUuid($uuid);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', [], [], 404);
        }

        $this->holidayService->deleteHoliday($holiday);

        return $this->successResponse(
            null,
            'Holiday deleted successfully'
        );
    }
}
