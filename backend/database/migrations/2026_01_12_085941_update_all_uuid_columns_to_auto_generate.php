<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // List of all tables with UUID columns
        $tables = [
            'users',
            'companies',
            'departments',
            'positions',
            'employees', // renamed from students
            'payroll_runs',
            'payrolls',
            'payroll_earnings',
            'payroll_deductions',
            'salaries',
            'deductions',
            'taxes',
            'contributions',
            'loans',
            'leave_requests',
            'leaves',
            'attendance',
            'attendance_logs',
            'payslips',
            'salary_payments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'uuid')) {
                try {
                    if ($driver === 'pgsql') {
                        // PostgreSQL: Use gen_random_uuid() as default
                        // First, update any NULL UUIDs to generated values
                        DB::statement("UPDATE {$table} SET uuid = gen_random_uuid() WHERE uuid IS NULL");

                        // Remove nullable and add default
                        DB::statement("ALTER TABLE {$table} ALTER COLUMN uuid SET DEFAULT gen_random_uuid()");
                        DB::statement("ALTER TABLE {$table} ALTER COLUMN uuid SET NOT NULL");
                    } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                        // MySQL/MariaDB: UUIDs are generated at application level via HasUuid trait
                        // Just ensure NOT NULL (default will be handled by application)
                        DB::statement("UPDATE {$table} SET uuid = UUID() WHERE uuid IS NULL");
                        DB::statement("ALTER TABLE {$table} MODIFY COLUMN uuid CHAR(36) NOT NULL");
                    }
                    // SQLite doesn't support UUID defaults, so we rely on HasUuid trait
                } catch (\Exception $e) {
                    // Log error but continue with other tables
                    Log::warning("Failed to update UUID column for table {$table}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        $tables = [
            'users',
            'companies',
            'departments',
            'positions',
            'employees',
            'payroll_runs',
            'payrolls',
            'payroll_earnings',
            'payroll_deductions',
            'salaries',
            'deductions',
            'taxes',
            'contributions',
            'loans',
            'leave_requests',
            'leaves',
            'attendance',
            'attendance_logs',
            'payslips',
            'salary_payments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'uuid')) {
                if ($driver === 'pgsql') {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN uuid DROP DEFAULT");
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN uuid DROP NOT NULL");
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement("ALTER TABLE {$table} MODIFY COLUMN uuid CHAR(36) NULL");
                }
            }
        }
    }
};
