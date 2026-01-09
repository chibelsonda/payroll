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
        // First, rename the column in students table
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('student_id', 'employee_id');
        });

        // Then rename the table
        Schema::rename('students', 'employees');

        // Update foreign key constraint in enrollments table
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->renameColumn('student_id', 'employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse foreign key in enrollments table
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->renameColumn('employee_id', 'student_id');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });

        // Reverse table rename
        Schema::rename('employees', 'students');

        // Reverse column rename
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('employee_id', 'student_id');
        });
    }
};
