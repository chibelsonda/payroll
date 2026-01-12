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
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->time('default_shift_start')->default('08:00:00')->after('company_id');
            $table->time('default_shift_end')->default('17:00:00')->after('default_break_end');
            $table->boolean('auto_deduct_break')->default(true)->after('auto_close_missing_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn(['default_shift_start', 'default_shift_end', 'auto_deduct_break']);
        });
    }
};
