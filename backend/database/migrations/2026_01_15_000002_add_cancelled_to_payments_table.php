<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','paid','failed','expired','cancelled') DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            // Use a text column to simplify enum extension on PostgreSQL
            DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(20)");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','paid','failed','expired') DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(10)");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('status', ['pending','paid','failed','expired'])->default('pending')->change();
            });
        }
    }
};
