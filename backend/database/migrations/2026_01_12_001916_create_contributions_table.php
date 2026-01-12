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
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->index();
            $table->string('name'); // SSS, PhilHealth, Pag-IBIG
            $table->decimal('employee_share', 5, 2); // Percentage
            $table->decimal('employer_share', 5, 2); // Percentage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
