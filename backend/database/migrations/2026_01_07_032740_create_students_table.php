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
        $driver = DB::getDriverName();
        
        Schema::create('students', function (Blueprint $table) use ($driver) {
            $table->id();
            if ($driver === 'pgsql') {
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            } else {
                $table->uuid('uuid')->nullable()->unique();
            }
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('student_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
