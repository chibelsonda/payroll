<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Http\Resources\LoanPaymentResource;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;

class LoanController extends BaseApiController
{
    public function __construct(
        protected LoanService $loanService
    ) {}

    /**
     * Get all loans with pagination
     */
    public function index(): JsonResponse
    {
        $loans = $this->loanService->getAllLoans();

        $meta = [
            'pagination' => [
                'current_page' => $loans->currentPage(),
                'last_page' => $loans->lastPage(),
                'per_page' => $loans->perPage(),
                'total' => $loans->total(),
                'from' => $loans->firstItem(),
                'to' => $loans->lastItem(),
                'has_more_pages' => $loans->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            LoanResource::collection($loans->items()),
            'Loans retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new loan
     */
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->createLoan($request->validated());
        return $this->createdResponse(
            new LoanResource($loan->load(['employee.user'])),
            'Loan created successfully'
        );
    }

    /**
     * Get a specific loan
     */
    public function show(Loan $loan): JsonResponse
    {
        $loan = $loan->load(['employee.user']);
        return $this->successResponse(
            new LoanResource($loan),
            'Loan retrieved successfully'
        );
    }

    /**
     * Update an existing loan
     */
    public function update(UpdateLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->updateLoan($loan, $request->validated());
        return $this->successResponse(
            new LoanResource($loan),
            'Loan updated successfully'
        );
    }

    /**
     * Delete a loan
     */
    public function destroy(Loan $loan): JsonResponse
    {
        $this->loanService->deleteLoan($loan);
        return $this->noContentResponse('Loan deleted successfully');
    }

    /**
     * Get loan payments for a loan
     */
    public function payments(Loan $loan): JsonResponse
    {
        $payments = $this->loanService->getLoanPayments($loan);
        return $this->successResponse(
            LoanPaymentResource::collection($payments),
            'Loan payments retrieved successfully'
        );
    }
}
