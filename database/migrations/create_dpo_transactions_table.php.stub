<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpo_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('token')->nullable()->index();
            $table->string('trans_id')->nullable();
            $table->enum('type', ['one-time', 'recurring', 'subscription']);
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled', 'refunded']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('country', 2);
            $table->string('payment_method')->nullable();

            // Customer information
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_country', 2)->nullable();

            // Payment details
            $table->text('description')->nullable();
            $table->json('items')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->default(0);

            // DPO response data
            $table->json('dpo_response')->nullable();
            $table->string('dpo_result_code')->nullable();
            $table->text('dpo_result_explanation')->nullable();

            // Relationships
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('dpo_subscriptions')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_email', 'status']);
            $table->index(['country', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpo_transactions');
    }
};
