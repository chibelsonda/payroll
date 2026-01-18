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

        Schema::create('leaves', function (Blueprint $table) use ($driver) {
            $table->id();
            if ($driver === 'pgsql') {
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique()->index();
            } else {
                $table->uuid('uuid')->nullable()->unique()->index();
            }
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // vacation, sick
            $table->decimal('balance', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
