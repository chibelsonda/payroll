<?php

namespace App\Jobs;

use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $payrollRunUuid
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PayrollService $payrollService): void
    {
        try {
            Log::info('Starting payroll generation job', [
                'payroll_run_uuid' => $this->payrollRunUuid,
            ]);

            $payrollService->generatePayroll($this->payrollRunUuid);

            Log::info('Payroll generation job completed successfully', [
                'payroll_run_uuid' => $this->payrollRunUuid,
            ]);
        } catch (\Exception $e) {
            Log::error('Payroll generation job failed', [
                'payroll_run_uuid' => $this->payrollRunUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GeneratePayrollJob failed after all retries', [
            'payroll_run_uuid' => $this->payrollRunUuid,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
