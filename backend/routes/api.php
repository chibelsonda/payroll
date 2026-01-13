<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceLogController;
use App\Http\Controllers\Api\V1\AttendanceCorrectionRequestController;
use App\Http\Controllers\Api\V1\AttendanceSettingsController;
use App\Http\Controllers\Api\V1\Admin\AttendanceFixController;
use App\Http\Controllers\Api\V1\Admin\AttendanceResolveController;
use App\Http\Controllers\Api\V1\Admin\AttendanceManageController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\DeductionController;
use App\Http\Controllers\Api\V1\SalaryController;
use App\Http\Controllers\Api\V1\ContributionController;
use App\Http\Controllers\Api\V1\EmployeeDeductionController;
use App\Http\Controllers\Api\V1\EmployeeContributionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes (no company context needed, but try to set it if header is present)
        Route::middleware('active.company')->get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Companies routes
        // GET: Requires company (for existing users)
        // POST: No company required (for onboarding)
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');

        // Company-scoped routes (require active company context)
        Route::middleware(['ensure.user.has.company', 'active.company'])->group(function () {
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

            // Dropdown data endpoints (company-scoped)
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
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::get('/summary', [AttendanceController::class, 'summary'])->name('summary');
                Route::apiResource('logs', AttendanceLogController::class)->only(['index', 'store', 'destroy']);
                Route::post('/correction-request', [AttendanceCorrectionRequestController::class, 'store'])->name('correction-request');
            });
            Route::apiResource('attendances', AttendanceController::class)->except(['store', 'update']);

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
                Route::delete('/{loan}', [LoanController::class, 'destroy'])->name('destroy');
                Route::get('/{loan}/payments', [LoanController::class, 'payments'])->name('payments');
            });

            // Admin-only routes for salary, contribution, and employee assignment management
            Route::middleware('role:admin')->group(function () {
            // Salary routes
            Route::prefix('salaries')->name('salaries.')->group(function () {
                Route::get('/', [SalaryController::class, 'index'])->name('index');
                Route::post('/', [SalaryController::class, 'store'])->name('store');
                Route::get('/{salary}', [SalaryController::class, 'show'])->name('show');
                Route::put('/{salary}', [SalaryController::class, 'update'])->name('update');
                Route::delete('/{salary}', [SalaryController::class, 'destroy'])->name('destroy');
            });

            // Employee salary history
            Route::get('employees/{employeeUuid}/salaries', [SalaryController::class, 'employeeSalaries'])->name('employees.salaries');

            // Contribution routes
            Route::prefix('contributions')->name('contributions.')->group(function () {
                Route::get('/', [ContributionController::class, 'index'])->name('index');
                Route::post('/', [ContributionController::class, 'store'])->name('store');
                Route::get('/{contribution}', [ContributionController::class, 'show'])->name('show');
                Route::put('/{contribution}', [ContributionController::class, 'update'])->name('update');
                Route::delete('/{contribution}', [ContributionController::class, 'destroy'])->name('destroy');
            });

            // Employee deduction assignment routes
            Route::prefix('employees/{employeeUuid}/deductions')->name('employees.deductions.')->group(function () {
                Route::get('/', [EmployeeDeductionController::class, 'index'])->name('index');
                Route::post('/', [EmployeeDeductionController::class, 'store'])->name('store');
                Route::delete('/{deductionUuid}', [EmployeeDeductionController::class, 'destroy'])->name('destroy');
            });

            // Employee contribution assignment routes
            Route::prefix('employees/{employeeUuid}/contributions')->name('employees.contributions.')->group(function () {
                Route::get('/', [EmployeeContributionController::class, 'index'])->name('index');
                Route::post('/', [EmployeeContributionController::class, 'store'])->name('store');
                Route::delete('/{contributionUuid}', [EmployeeContributionController::class, 'destroy'])->name('destroy');
            });

            // Admin attendance routes
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::post('/{attendance}/fix', [AttendanceFixController::class, 'fix'])->name('fix');
                Route::post('/{attendance}/resolve', [AttendanceResolveController::class, 'resolve'])->name('resolve');
                Route::post('/recalculate', [AttendanceManageController::class, 'recalculate'])->name('recalculate');
                Route::post('/approve', [AttendanceManageController::class, 'approve'])->name('approve');
                Route::post('/mark-incomplete', [AttendanceManageController::class, 'markIncomplete'])->name('mark-incomplete');
                Route::post('/lock', [AttendanceManageController::class, 'lock'])->name('lock');
            });
            // Admin attendance log routes (separate from attendance routes to avoid conflicts)
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::put('/logs/{attendanceLog}', [AttendanceManageController::class, 'updateLog'])->name('logs.update');
            });

                // Attendance Settings routes (admin only)
                Route::prefix('attendance-settings')->name('attendance-settings.')->group(function () {
                    Route::get('/', [AttendanceSettingsController::class, 'show'])->name('show');
                    Route::put('/', [AttendanceSettingsController::class, 'update'])->name('update');
                });
            });
        }); // End active.company middleware group
    }); // End auth:sanctum middleware group
});
