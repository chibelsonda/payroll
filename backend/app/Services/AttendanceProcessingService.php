<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceProcessingService
{
    /**
     * Process attendance logs and compute summary for a specific employee and date
     * Implements detailed rules for log pairing and auto-correction
     */
    public function processAttendance(int $employeeId, string $date): Attendance
    {
        $employee = Employee::find($employeeId);
        $companyId = $employee?->company_id;
        $settings = $this->getCompanySettings($companyId);

        // Delete existing auto-corrected logs for this date (they will be regenerated)
        AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('log_time', $date)
            ->where('is_auto_corrected', true)
            ->delete();

        // Get all real logs for the date, ordered chronologically
        $logs = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('log_time', $date)
            ->orderBy('log_time', 'asc')
            ->get();

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

        // Process logs
        foreach ($logs as $log) {
            $logTime = $log->log_time; // Already a Carbon instance

            if ($log->type === 'IN') {
                // RULE 6: Consecutive INs
                if ($inTime !== null) {
                    // Auto-close previous IN at break start
                    $this->createAutoCorrectedLog($employeeId, $breakStart, 'OUT', 'Missing time-out auto-closed');
                    $intervals[] = ['start' => $inTime, 'end' => $breakStart];
                    $totalMinutes += $inTime->diffInMinutes($breakStart);
                    $isAutoCorrected = true;
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
        if ($inTime !== null) {
            if ($settings['auto_close_missing_out']) {
                $this->createAutoCorrectedLog($employeeId, $shiftEnd, 'OUT', 'Missing time-out auto-closed');
                $intervals[] = ['start' => $inTime, 'end' => $shiftEnd];
                $totalMinutes += $inTime->diffInMinutes($shiftEnd);
                $isAutoCorrected = true;
            } else {
                $isIncomplete = true;
                $needsReview = true;
                $status = 'incomplete';
            }
        }

        // RULE 4: Missing BOTH IN and OUT (single continuous shift with break deduction)
        if (count($intervals) === 1 && $settings['auto_deduct_break']) {
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
