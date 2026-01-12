<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\DeductionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Get current authenticated user
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Example: Routes protected by role middleware
        Route::middleware('role:admin')->group(function () {
            // Admin-only routes can go here
        });

        // Example: Routes protected by permission middleware
        Route::middleware('permission:manage users')->group(function () {
            // User management routes can go here
        });

        // Example: Posts resource with permission-based access
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/{id}', [PostController::class, 'show'])->name('show');

            Route::post('/', [PostController::class, 'store'])
                ->middleware('permission:create posts')
                ->name('store');

            Route::put('/{id}', [PostController::class, 'update'])
                ->middleware('permission:edit posts')
                ->name('update');

            Route::delete('/{id}', [PostController::class, 'destroy'])
                ->middleware('permission:delete posts')
                ->name('destroy');
        });

        // Alternative: Using role_or_permission middleware
        Route::middleware('role_or_permission:admin|manage users')->group(function () {
            // Routes accessible by admin role OR manage users permission
        });

        // Existing resource routes
        Route::apiResource('employees', EmployeeController::class);

        // Dropdown data endpoints
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('positions', [PositionController::class, 'index'])->name('positions.index');

        // Payroll routes
        Route::prefix('payroll-runs')->name('payroll-runs.')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])->name('index');
            Route::post('/', [PayrollController::class, 'store'])->name('store');
            Route::get('/{payrollRunUuid}', [PayrollController::class, 'showPayrollRun'])->name('show');
            Route::get('/{payrollRunUuid}/payrolls', [PayrollController::class, 'getPayrolls'])->name('payrolls');
            Route::get('/{payrollRunUuid}/export-excel', [PayrollController::class, 'exportExcel'])->name('export-excel');
            Route::post('/{payrollRunUuid}/generate', [PayrollController::class, 'generatePayroll'])->name('generate');
            Route::post('/{payrollRunUuid}/finalize', [PayrollController::class, 'finalize'])->name('finalize');
        });
        Route::get('payrolls/{payrollUuid}', [PayrollController::class, 'show'])->name('payrolls.show');

        // Attendance routes
        Route::apiResource('attendances', AttendanceController::class);

        // Deduction routes
        Route::apiResource('deductions', DeductionController::class);

        // Leave Request routes
        Route::prefix('leave-requests')->name('leave-requests.')->group(function () {
            Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
            Route::post('/', [LeaveRequestController::class, 'store'])->name('store');
            Route::get('/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('show');
            Route::post('/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
            Route::post('/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('reject');
            Route::delete('/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('destroy');
        });

        // Loan routes
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', [LoanController::class, 'index'])->name('index');
            Route::post('/', [LoanController::class, 'store'])->name('store');
            Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
            Route::put('/{loan}', [LoanController::class, 'update'])->name('update');
            Route::patch('/{loan}', [LoanController::class, 'update'])->name('update');
            Route::delete('/{loan}', [LoanController::class, 'destroy'])->name('destroy');
            Route::get('/{loan}/payments', [LoanController::class, 'payments'])->name('payments');
        });
    });
});
