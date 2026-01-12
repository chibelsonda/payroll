<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_auto_corrected column
        Schema::table('attendance', function (Blueprint $table) {
            $table->boolean('is_auto_corrected')->default(false)->after('needs_review');
        });

        // Update status column to support new enum values
        // Laravel's enum() creates VARCHAR in PostgreSQL, so we use string with check constraint
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop old check constraint if exists, add new one
            DB::statement("ALTER TABLE attendance DROP CONSTRAINT IF EXISTS attendance_status_check");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT attendance_status_check CHECK (status IN ('present', 'absent', 'leave', 'incomplete', 'needs_review'))");
            DB::statement("ALTER TABLE attendance ALTER COLUMN status SET DEFAULT 'present'");
        } elseif ($driver === 'mysql') {
            // MySQL/MariaDB: Use MODIFY COLUMN
            DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'leave', 'incomplete', 'needs_review') DEFAULT 'present'");
        } else {
            // SQLite: enum() is already string, no constraint needed
            // Just ensure the column type is string (Laravel handles this)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('is_auto_corrected');
        });

        // Revert status column
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE attendance DROP CONSTRAINT IF EXISTS attendance_status_check");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT attendance_status_check CHECK (status IN ('present', 'absent', 'leave'))");
            DB::statement("ALTER TABLE attendance ALTER COLUMN status SET DEFAULT 'present'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'leave') DEFAULT 'present'");
        }
    }
};
