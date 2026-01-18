<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceProcessingService
{
    /**
     * Process attendance logs and compute summary for a specific employee and date
     * Implements detailed rules for log pairing and auto-correction
     *
     * Uses database transaction to prevent race conditions when processing logs
     */
    public function processAttendance(int $employeeId, string $date): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date) {
            $employee = Employee::find($employeeId);
            $companyId = $employee?->company_id;
            $settings = $this->getCompanySettings($companyId);

            // SAFEGUARD: Get all real logs FIRST before deleting auto-corrected ones
            // This ensures we check for real OUT logs before making decisions
            $realLogs = AttendanceLog::where('employee_id', $employeeId)
                ->whereDate('log_time', $date)
                ->where('is_auto_corrected', false)
                ->orderBy('log_time', 'asc')
                ->get();

            // SAFEGUARD: Only delete auto-corrected logs if no real OUT exists
            // This prevents deleting valid auto-corrected logs when real OUT is present
            $hasRealOutLog = $realLogs->where('type', 'OUT')->isNotEmpty();

            if (!$hasRealOutLog) {
                // Delete existing auto-corrected logs for this date (they will be regenerated if needed)
                $deletedCount = AttendanceLog::where('employee_id', $employeeId)
                    ->whereDate('log_time', $date)
                    ->where('is_auto_corrected', true)
                    ->delete();

                // Log for debugging if auto-corrected logs were deleted
                if ($deletedCount > 0) {
                    Log::info('Deleted auto-corrected logs during attendance processing', [
                        'employee_id' => $employeeId,
                        'date' => $date,
                        'deleted_count' => $deletedCount,
                    ]);
                }
            } else {
                // Log when real OUT exists to prevent auto-correction
                Log::debug('Real OUT log exists, skipping auto-corrected log deletion', [
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'real_out_count' => $realLogs->where('type', 'OUT')->count(),
                ]);
            }

            // Get all logs for the date (including any remaining auto-corrected ones), ordered chronologically
            $logs = AttendanceLog::where('employee_id', $employeeId)
                ->whereDate('log_time', $date)
                ->orderBy('log_time', 'asc')
                ->get();

            // Check if attendance record exists
            $existingAttendance = Attendance::with(['employee.user'])
                ->where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->first();

            // If no logs exist and attendance is not locked, delete the attendance record
            if ($logs->isEmpty() && $existingAttendance && !$existingAttendance->is_locked) {
                // Load relationships before deletion so we can return them
                $existingAttendance->load(['employee.user']);
                $existingAttendance->delete();
                // Return the deleted attendance (for API consistency, though it's deleted from DB)
                return $existingAttendance;
            }

            $result = $this->computeAttendance($logs, $date, $settings, $employeeId);

            // Update or create attendance summary
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'hours_worked' => $result['hours_worked'],
                    'status' => $result['status'],
                    'is_incomplete' => $result['is_incomplete'],
                    'needs_review' => $result['needs_review'],
                    'is_auto_corrected' => $result['is_auto_corrected'],
                ]
            );

            return $attendance->fresh(['employee.user']);
        });
    }

    /**
     * Compute attendance from logs using detailed rules
     */
    protected function computeAttendance($logs, string $date, array $settings, int $employeeId): array
    {
        if ($logs->isEmpty()) {
            return [
                'hours_worked' => 0,
                'status' => 'absent',
                'is_incomplete' => false,
                'needs_review' => false,
                'is_auto_corrected' => false,
            ];
        }

        $totalMinutes = 0;
        $status = 'present';
        $isIncomplete = false;
        $needsReview = false;
        $isAutoCorrected = false;
        $intervals = [];
        $inTime = null;
        $breakDuration = Carbon::parse($settings['default_break_start'])->diffInMinutes(
            Carbon::parse($settings['default_break_end'])
        );

        $dateCarbon = Carbon::parse($date);
        $shiftStart = $dateCarbon->copy()->setTimeFromTimeString($settings['default_shift_start']);
        $breakStart = $dateCarbon->copy()->setTimeFromTimeString($settings['default_break_start']);
        $breakEnd = $dateCarbon->copy()->setTimeFromTimeString($settings['default_break_end']);
        $shiftEnd = $dateCarbon->copy()->setTimeFromTimeString($settings['default_shift_end']);

        // SAFEGUARD: Check if there are any real OUT logs before processing
        // This prevents auto-correction when real OUT logs exist
        $hasRealOutLog = $logs->where('type', 'OUT')->where('is_auto_corrected', false)->isNotEmpty();

        // Process logs
        foreach ($logs as $log) {
            $logTime = $log->log_time; // Already a Carbon instance

            if ($log->type === 'IN') {
                // RULE 6: Consecutive INs
                if ($inTime !== null) {
                    // Only auto-close if no real OUT exists after the previous IN
                    // Check if there's a real OUT between the previous IN and this IN
                    $hasOutBetween = $logs
                        ->where('type', 'OUT')
                        ->where('is_auto_corrected', false)
                        ->whereBetween('log_time', [$inTime, $logTime])
                        ->isNotEmpty();

                    if (!$hasOutBetween) {
                        // RULE 6: Consecutive INs - Auto-close previous IN at break start
                        // Only if auto-correction is enabled
                        if ($settings['enable_auto_correction'] ?? true) {
                            // Check if auto-corrected OUT already exists at break start
                            $existingAutoOut = $logs->where('type', 'OUT')
                                ->where('is_auto_corrected', true)
                                ->where('log_time', $breakStart)
                                ->first();

                            if (!$existingAutoOut) {
                                $this->createAutoCorrectedLog($employeeId, $breakStart, 'OUT', 'Missing time-out auto-closed (consecutive INs)');
                            }
                            $intervals[] = ['start' => $inTime, 'end' => $breakStart];
                            $totalMinutes += $inTime->diffInMinutes($breakStart);
                            $isAutoCorrected = true;
                        } else {
                            // Auto-correction disabled - mark for review instead
                            $needsReview = true;
                            $isIncomplete = true;
                            if ($status === 'present') {
                                $status = 'incomplete';
                            }
                        }
                    }
                }
                $inTime = $logTime;
            } elseif ($log->type === 'OUT') {
                if ($inTime !== null) {
                    // RULE 1: Normal case - IN → OUT
                    $intervals[] = ['start' => $inTime, 'end' => $logTime];
                    $totalMinutes += $inTime->diffInMinutes($logTime);
                    $inTime = null;
                } else {
                    // RULE 5: OUT without prior IN
                    $needsReview = true;
                    $status = 'needs_review';
                }
            }
        }

        // RULE 7: End-of-Day OPEN IN
        // SAFEGUARD: Only auto-close if no real OUT log exists for this date
        if ($inTime !== null && !$hasRealOutLog) {
            // Check if auto-correction is enabled (master switch)
            $autoCorrectionEnabled = $settings['enable_auto_correction'] ?? true;

            if ($autoCorrectionEnabled && ($settings['auto_close_missing_out'] ?? true)) {
                // Validate that we're not creating a duplicate auto-corrected OUT
                $existingAutoOut = $logs->where('type', 'OUT')
                    ->where('is_auto_corrected', true)
                    ->where('log_time', $shiftEnd)
                    ->first();

                if (!$existingAutoOut) {
                    $this->createAutoCorrectedLog($employeeId, $shiftEnd, 'OUT', 'Missing time-out auto-closed');
                }
                $intervals[] = ['start' => $inTime, 'end' => $shiftEnd];
                $totalMinutes += $inTime->diffInMinutes($shiftEnd);
                $isAutoCorrected = true;
            } else {
                // Auto-correction disabled or auto_close_missing_out is false
                $isIncomplete = true;
                $needsReview = true;
                $status = 'incomplete';
            }
        } elseif ($inTime !== null && $hasRealOutLog) {
            // EDGE CASE: If there's a real OUT but we still have an open IN, mark for review
            // This can happen if there are multiple IN logs and the OUT doesn't match the last one
            // Log this edge case for investigation
            Log::warning('Open IN exists despite real OUT log present', [
                'employee_id' => $employeeId,
                'date' => $date,
                'open_in_time' => $inTime->format('Y-m-d H:i:s'),
                'real_out_count' => $logs->where('type', 'OUT')->where('is_auto_corrected', false)->count(),
            ]);
            $isIncomplete = true;
            $needsReview = true;
            $status = 'incomplete';
        }

        // RULE 4: Missing BOTH IN and OUT (single continuous shift with break deduction)
        // Only apply if auto-correction is enabled
        if (count($intervals) === 1 && ($settings['enable_auto_correction'] ?? true) && ($settings['auto_deduct_break'] ?? true)) {
            $interval = $intervals[0];

            // If the interval spans the entire shift, deduct break
            if ($interval['start']->lte($shiftStart) && $interval['end']->gte($shiftEnd)) {
                $totalMinutes -= $breakDuration;
                $isAutoCorrected = true;
                // Note: We don't create a log for break deduction, only flag the attendance
            }
        }

        // RULE 2 & 3: Check for missing segments
        if (count($intervals) > 0) {
            $firstInterval = $intervals[0];
            $lastInterval = $intervals[count($intervals) - 1];

            // Check for missing morning or afternoon segment
            $hasMorning = $firstInterval['start']->lte($breakStart);
            $hasAfternoon = $lastInterval['end']->gte($breakEnd);

            if (!$hasMorning || !$hasAfternoon) {
                // Missing segment - needs review (RULE 3)
                $needsReview = true;
                if ($status === 'present') {
                    $status = 'needs_review';
                }
            }
        }

        $hoursWorked = round($totalMinutes / 60, 2);

        // EDGE CASE: Validate calculated hours
        // If hours are negative (shouldn't happen, but protect against bugs), set to 0
        if ($hoursWorked < 0) {
            Log::warning('Negative hours calculated for attendance', [
                'employee_id' => $employeeId,
                'date' => $date,
                'calculated_hours' => $hoursWorked,
                'total_minutes' => $totalMinutes,
            ]);
            $hoursWorked = 0;
            $needsReview = true;
        }

        // EDGE CASE: Validate interval consistency
        // Check if intervals overlap (shouldn't happen with proper pairing)
        if (count($intervals) > 1) {
            for ($i = 0; $i < count($intervals) - 1; $i++) {
                $current = $intervals[$i];
                $next = $intervals[$i + 1];

                if ($current['end']->gt($next['start'])) {
                    Log::warning('Overlapping intervals detected in attendance processing', [
                        'employee_id' => $employeeId,
                        'date' => $date,
                        'interval_1' => $current['start']->format('H:i') . '-' . $current['end']->format('H:i'),
                        'interval_2' => $next['start']->format('H:i') . '-' . $next['end']->format('H:i'),
                    ]);
                    $needsReview = true;
                }
            }
        }

        return [
            'hours_worked' => max(0, $hoursWorked), // Ensure non-negative
            'status' => $status,
            'is_incomplete' => $isIncomplete,
            'needs_review' => $needsReview,
            'is_auto_corrected' => $isAutoCorrected,
        ];
    }

    /**
     * Create an auto-corrected log
     */
    protected function createAutoCorrectedLog(int $employeeId, Carbon $logTime, string $type, string $reason): AttendanceLog
    {
        return AttendanceLog::create([
            'employee_id' => $employeeId,
            'log_time' => $logTime,
            'type' => $type,
            'is_auto_corrected' => true,
            'correction_reason' => $reason,
        ]);
    }

    /**
     * Get company attendance settings or defaults
     */
    protected function getCompanySettings(?int $companyId): array
    {
        if ($companyId) {
            $settings = AttendanceSettings::where('company_id', $companyId)->first();
            if ($settings) {
                // Time fields come as strings from database
                $shiftStart = $settings->default_shift_start;
                $breakStart = $settings->default_break_start;
                $breakEnd = $settings->default_break_end;
                $shiftEnd = $settings->default_shift_end;

                if ($shiftStart instanceof \Carbon\Carbon || $shiftStart instanceof \DateTime) {
                    $shiftStart = $shiftStart->format('H:i:s');
                }
                if ($breakStart instanceof \Carbon\Carbon || $breakStart instanceof \DateTime) {
                    $breakStart = $breakStart->format('H:i:s');
                }
                if ($breakEnd instanceof \Carbon\Carbon || $breakEnd instanceof \DateTime) {
                    $breakEnd = $breakEnd->format('H:i:s');
                }
                if ($shiftEnd instanceof \Carbon\Carbon || $shiftEnd instanceof \DateTime) {
                    $shiftEnd = $shiftEnd->format('H:i:s');
                }

                return [
                    'default_shift_start' => $shiftStart,
                    'default_break_start' => $breakStart,
                    'default_break_end' => $breakEnd,
                    'default_shift_end' => $shiftEnd,
                    'max_shift_hours' => $settings->max_shift_hours,
                    'auto_close_missing_out' => $settings->auto_close_missing_out,
                    'auto_deduct_break' => $settings->auto_deduct_break,
                    'enable_auto_correction' => $settings->enable_auto_correction ?? true,
                ];
            }
        }

        return AttendanceSettings::getDefaults();
    }

    /**
     * Resolve attendance (admin action)
     * Marks as resolved and removes review flags
     */
    public function resolveAttendance(Attendance $attendance): Attendance
    {
        $attendance->update([
            'needs_review' => false,
            'status' => 'present', // Mark as present after resolution
        ]);

        return $attendance->fresh(['employee.user']);
    }
}
