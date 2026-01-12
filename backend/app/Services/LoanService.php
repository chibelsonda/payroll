<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;

class LoanService
{
    /**
     * Get all loans with pagination
     */
    public function getAllLoans()
    {
        return Loan::with(['employee.user'])->orderBy('created_at', 'desc')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a loan by UUID
     */
    public function findByUuid(string $uuid): ?Loan
    {
        return Loan::where('uuid', $uuid)->first();
    }

    /**
     * Get loan payments for a loan
     */
    public function getLoanPayments(Loan $loan)
    {
        return $loan->payments()->with('payroll')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create a new loan
     */
    public function createLoan(array $data): Loan
    {
        return Loan::create($data);
    }

    /**
     * Update an existing loan
     */
    public function updateLoan(Loan $loan, array $data): Loan
    {
        $loan->update($data);
        return $loan->fresh(['employee.user']);
    }

    /**
     * Delete a loan
     */
    public function deleteLoan(Loan $loan): bool
    {
        return $loan->delete();
    }
}
