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

        Schema::create('payments', function (Blueprint $table) use ($driver) {
            $table->id();
            if ($driver === 'pgsql') {
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique()->index();
            } else {
                $table->uuid('uuid')->nullable()->unique()->index();
            }
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->string('provider')->default('paymongo'); // paymongo, stripe, etc
            $table->string('method'); // gcash, card, maya, etc
            $table->string('provider_reference_id')->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('subscription_id');
            $table->index('provider_reference_id');
            $table->index('status');
            $table->index(['provider', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
