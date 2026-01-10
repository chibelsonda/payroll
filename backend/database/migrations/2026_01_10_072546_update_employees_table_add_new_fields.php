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
        // Make employee_id nullable first using raw SQL (for PostgreSQL compatibility)
        DB::statement('ALTER TABLE employees ALTER COLUMN employee_id DROP NOT NULL');
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_employee_id_unique');
        
        Schema::table('employees', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained('companies')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained('departments')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->after('department_id')->constrained('positions')->onDelete('set null');
            
            // Add new fields
            $table->string('employee_no')->nullable()->after('employee_id');
            $table->string('employment_type')->nullable()->after('employee_no'); // regular, contractual, probationary
            $table->date('hire_date')->nullable()->after('employment_type');
            $table->date('termination_date')->nullable()->after('hire_date');
            $table->string('status')->default('active')->after('termination_date'); // active, inactive, terminated
        });
        
        // Copy data from employee_id to employee_no for existing records
        $employees = DB::table('employees')->whereNotNull('employee_id')->get();
        foreach ($employees as $emp) {
            if (empty($emp->employee_no) && !empty($emp->employee_id)) {
                DB::table('employees')
                    ->where('id', $emp->id)
                    ->update(['employee_no' => $emp->employee_id]);
            }
        }
        
        // Make employee_no unique and required (not nullable)
        Schema::table('employees', function (Blueprint $table) {
            $table->unique('employee_no');
        });
        
        // Set employee_no as NOT NULL for new records (update existing nulls first)
        DB::statement('UPDATE employees SET employee_no = employee_id WHERE employee_no IS NULL AND employee_id IS NOT NULL');
        DB::statement('ALTER TABLE employees ALTER COLUMN employee_no SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique(['employee_no']);
            
            // Drop foreign keys
            $table->dropForeign(['company_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);
            
            // Drop columns
            $table->dropColumn(['company_id', 'department_id', 'position_id', 'employee_no', 'employment_type', 'hire_date', 'termination_date', 'status']);
        });
    }
};
