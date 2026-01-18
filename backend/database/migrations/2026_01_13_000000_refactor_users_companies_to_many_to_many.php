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
        // Create company_user pivot table
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint: a user can only be attached to a company once
            $table->unique(['company_id', 'user_id']);

            // Indexes for performance
            $table->index('company_id');
            $table->index('user_id');
        });

        // Migrate existing data from users.company_id to company_user pivot table
        // Only migrate users that have a company_id set
        $usersWithCompany = DB::table('users')
            ->whereNotNull('company_id')
            ->get();

        foreach ($usersWithCompany as $user) {
            // Insert into pivot table if not already exists
            DB::table('company_user')->insertOrIgnore([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Remove company_id column from users table
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropIndex('users_company_id_index'); // Drop index if it exists
                $table->dropColumn('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add company_id column to users table
        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
                $table->index('company_id');
            });
        }

        // Migrate data back from pivot table to users.company_id
        // Use the first company for each user (if multiple exist)
        $pivotData = DB::table('company_user')
            ->select('user_id', 'company_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id');

        foreach ($pivotData as $userId => $companies) {
            // Use the first company
            $firstCompany = $companies->first();
            DB::table('users')
                ->where('id', $userId)
                ->update(['company_id' => $firstCompany->company_id]);
        }

        // Drop pivot table
        Schema::dropIfExists('company_user');
    }
};
