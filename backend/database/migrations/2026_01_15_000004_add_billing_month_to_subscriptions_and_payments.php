<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add billing_month columns
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'billing_month')) {
                $table->date('billing_month')->nullable()->after('plan_id');
                $table->index('billing_month');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'billing_month')) {
                $table->date('billing_month')->nullable()->after('currency');
                $table->index('billing_month');
            }
        });

        // Backfill billing_month with the first day of the month based on created_at (or starts_at/paid_at when available)
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("UPDATE subscriptions SET billing_month = date_trunc('month', COALESCE(starts_at, created_at))::date WHERE billing_month IS NULL");
            DB::statement("UPDATE payments SET billing_month = date_trunc('month', COALESCE(paid_at, created_at))::date WHERE billing_month IS NULL");

            // Deduplicate before adding unique index (keep latest created_at)
            DB::statement("
                DELETE FROM subscriptions s
                USING subscriptions s2
                WHERE s.company_id = s2.company_id
                  AND s.billing_month = s2.billing_month
                  AND s.id < s2.id
            ");
            DB::statement("
                DELETE FROM payments p
                USING payments p2
                WHERE p.company_id = p2.company_id
                  AND p.billing_month = p2.billing_month
                  AND p.id < p2.id
            ");

            // Enforce unique per company per billing month
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS subscriptions_company_billing_month_idx ON subscriptions (company_id, billing_month)');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_company_billing_month_idx ON payments (company_id, billing_month)');
        } else {
            // Fallback for non-PG: use Laravel unique index
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unique(['company_id', 'billing_month'], 'subscriptions_company_billing_month_idx');
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->unique(['company_id', 'billing_month'], 'payments_company_billing_month_idx');
            });

            DB::statement("UPDATE subscriptions SET billing_month = DATE_FORMAT(COALESCE(starts_at, created_at), '%Y-%m-01') WHERE billing_month IS NULL");
            DB::statement("UPDATE payments SET billing_month = DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m-01') WHERE billing_month IS NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS subscriptions_company_billing_month_idx');
            DB::statement('DROP INDEX IF EXISTS payments_company_billing_month_idx');
        } else {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropUnique('subscriptions_company_billing_month_idx');
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_company_billing_month_idx');
            });
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'billing_month')) {
                $table->dropColumn('billing_month');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'billing_month')) {
                $table->dropColumn('billing_month');
            }
        });
    }
};
