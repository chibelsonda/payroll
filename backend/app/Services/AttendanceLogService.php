<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class AttendanceLogService
{
    public function __construct(
        protected AttendanceProcessingService $attendanceProcessingService
    ) {}

    /**
     * Get attendance logs with optional filters
     */
    public function getAttendanceLogs(?int $employeeId = null, ?string $date = null)
    {
        $query = AttendanceLog::with(['employee.user'])
            ->orderBy('log_time', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($date) {
            $query->whereDate('log_time', $date);
        }

        return $query->get();
    }

    /**
     * Create a new attendance log
     *
     * Uses database transaction to ensure log creation and attendance processing are atomic
     */
    public function createAttendanceLog(array $data): AttendanceLog
    {
        return DB::transaction(function () use ($data) {
            // Set log_time to now() if not provided
            if (!isset($data['log_time'])) {
                $data['log_time'] = now();
            }

            // Convert log_time to Carbon if it's a string
            if (is_string($data['log_time'])) {
                $data['log_time'] = Carbon::parse($data['log_time']);
            }

            // Validate log data before creating
            if (!isset($data['employee_id'])) {
                throw new \InvalidArgumentException('Employee ID is required for attendance log');
            }

            if (!isset($data['type']) || !in_array($data['type'], ['IN', 'OUT'])) {
                throw new \InvalidArgumentException('Log type must be IN or OUT');
            }

            $log = AttendanceLog::create($data);

            // Recalculate attendance summary for the log date using the processing service
            // This is done within the transaction to ensure consistency
            $this->attendanceProcessingService->processAttendance(
                $log->employee_id,
                $log->log_time->toDateString()
            );

            return $log->load(['employee.user']);
        });
    }

    /**
     * Delete an attendance log
     */
    public function deleteAttendanceLog(AttendanceLog $log): bool
    {
        $employeeId = $log->employee_id;
        $date = $log->log_time->toDateString();

        $deleted = $log->delete();

        if ($deleted) {
            try {
                // Recalculate attendance summary after deletion using the processing service
                $this->attendanceProcessingService->processAttendance($employeeId, $date);
            } catch (\Exception $e) {
                // Log the error but don't fail the deletion
                Log::error('Failed to recalculate attendance after log deletion', [
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);
                // Still return true since the log was deleted successfully
            }
        }

        return $deleted;
    }

    /**
     * Recalculate attendance summary from logs for a specific employee and date
     * Delegates to AttendanceProcessingService for comprehensive rule processing
     */
    public function recalculateAttendanceSummary(int $employeeId, string $date): void
    {
        $this->attendanceProcessingService->processAttendance($employeeId, $date);
    }

    /**
    * Import attendance logs from CSV.
    *
    * Expected headers: employee_no, log_time, type
    * - employee_no: mapped to employees table (company scoped)
    * - log_time: parsable datetime
    * - type: IN or OUT (case-insensitive)
    *
    * Uses per-row transactions to avoid partial writes and recalculates attendance per inserted log.
    */
    public function importFromCsv(UploadedFile $file, int $companyId): array
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Invalid file upload.');
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read the uploaded CSV file.');
        }

        $headerRow = fgetcsv($handle);

        if ($headerRow === false) {
            fclose($handle);
            throw new InvalidArgumentException('CSV file is empty.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);
        $expectedHeaders = ['employee_no', 'log_time', 'type'];

        if ($headers !== $expectedHeaders) {
            fclose($handle);
            throw new InvalidArgumentException('Expected headers: employee_no,log_time,type');
        }

        $rowNumber = 1; // header row

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rowData = array_map(fn ($v) => trim((string) $v), $row);
            $data = array_combine($headers, $rowData);

            if ($data === false) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Malformed row data.',
                ];
                continue;
            }

            $employeeNo = $data['employee_no'] ?? '';
            $logTimeRaw = $data['log_time'] ?? '';
            $type = strtoupper($data['type'] ?? '');

            if (!$employeeNo || !$logTimeRaw || !$type) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'employee_no, log_time, and type are required.',
                ];
                continue;
            }

            if (!in_array($type, ['IN', 'OUT'], true)) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'type must be IN or OUT.',
                ];
                continue;
            }

            $employee = Employee::where('company_id', $companyId)
                ->where('employee_no', $employeeNo)
                ->first();

            if (!$employee) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Employee not found for employee_no ' . $employeeNo,
                ];
                continue;
            }

            Log::info('Log time raw: ' . $logTimeRaw);
            try {
                $logTime = Carbon::parse($logTimeRaw);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Invalid log_time format.',
                ];
                continue;
            }

            $duplicate = AttendanceLog::where('employee_id', $employee->id)
                ->where('type', $type)
                ->where('log_time', $logTime->toDateTimeString())
                ->exists();

            if ($duplicate) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Duplicate log skipped (same employee, type, and log_time).',
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($employee, $type, $logTime) {
                    $this->createAttendanceLog([
                        'employee_id' => $employee->id,
                        'type' => $type,
                        'log_time' => $logTime,
                    ]);
                });

                $created++;
            } catch (\Throwable $e) {
                Log::warning('Attendance import row failed', [
                    'row' => $rowNumber,
                    'employee_no' => $employeeNo,
                    'error' => $e->getMessage(),
                ]);

                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Failed to import row: ' . $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
