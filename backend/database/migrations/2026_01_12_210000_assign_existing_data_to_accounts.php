<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\User;
use App\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration assigns existing users and companies to a default account.
     */
    public function up(): void
    {
        // Create a default account if none exists
        $defaultAccount = Account::firstOrCreate(
            ['name' => 'Default Account'],
            [
                'status' => 'active',
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        );

        // Assign existing users to the default account (if they don't have an account_id)
        User::whereNull('account_id')
            ->update(['account_id' => $defaultAccount->id]);

        // Assign existing companies to the default account (if they don't have an account_id)
        Company::whereNull('account_id')
            ->update(['account_id' => $defaultAccount->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set account_id to NULL for users and companies
        // Note: This might fail if there are foreign key constraints with CASCADE
        User::whereNotNull('account_id')->update(['account_id' => null]);
        Company::whereNotNull('account_id')->update(['account_id' => null]);

        // Optionally delete the default account (be careful with this)
        // Account::where('name', 'Default Account')->delete();
    }
};
