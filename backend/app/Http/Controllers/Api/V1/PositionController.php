<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends BaseApiController
{
    /**
     * Display a listing of positions (for dropdowns)
     * Supports filtering by department_uuid query parameter
     */
    public function index(): JsonResponse
    {
        $query = Position::query()->with('department');

        if (request()->has('department_uuid') && request('department_uuid')) {
            $department = \App\Models\Department::where('uuid', request('department_uuid'))->first();
            if ($department) {
                $query->where('department_id', $department->id);
            } else {
                // Return empty result if department not found
                return $this->successResponse([], 'Positions retrieved successfully');
            }
        }

        $positions = $query->orderBy('title')->get();
        return $this->successResponse(
            \App\Http\Resources\PositionResource::collection($positions),
            'Positions retrieved successfully'
        );
    }

}
