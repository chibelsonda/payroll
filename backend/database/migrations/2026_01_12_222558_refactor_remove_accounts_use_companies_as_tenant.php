<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add company_id to users table (nullable initially)
        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
                $table->index('company_id');
            });
        }

        // Remove account_id from users table
        if (Schema::hasColumn('users', 'account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }

        // Remove account_id from companies table
        if (Schema::hasColumn('companies', 'account_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }

        // Drop accounts table if it exists
        if (Schema::hasTable('accounts')) {
            Schema::dropIfExists('accounts');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // Recreate accounts table
        Schema::create('accounts', function (Blueprint $table) use ($driver) {
            $table->id();
            if ($driver === 'pgsql') {
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique()->index();
            } else {
                $table->uuid('uuid')->nullable()->unique()->index();
            }
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Add account_id back to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('account_id')->after('id')->constrained('accounts')->onDelete('cascade');
        });

        // Add account_id back to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_id')->after('id')->constrained('accounts')->onDelete('cascade');
        });

        // Remove company_id from users
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
