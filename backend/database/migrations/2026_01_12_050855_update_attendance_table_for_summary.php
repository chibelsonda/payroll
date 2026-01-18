<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Remove time_in and time_out columns
            $table->dropColumn(['time_in', 'time_out']);
            
            // Add status column
            $table->enum('status', ['present', 'absent', 'leave'])->default('present')->after('hours_worked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Restore time_in and time_out columns
            $table->time('time_in')->nullable()->after('date');
            $table->time('time_out')->nullable()->after('time_in');
            
            // Remove status column
            $table->dropColumn('status');
        });
    }
};
