<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpo_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_reference')->unique();
            $table->string('dpo_subscription_id')->nullable()->unique();
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired', 'pending']);
            $table->enum('frequency', ['monthly', 'weekly', 'quarterly', 'yearly']);
            
            // Subscription details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('country', 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->integer('billing_cycle')->default(1);
            
            // Customer information
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_country', 2)->nullable();
            
            // Payment method
            $table->string('payment_method')->nullable();
            $table->string('payment_token')->nullable();
            $table->json('card_details')->nullable(); // Masked card details
            
            // Subscription metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->integer('retry_attempts')->default(0);
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Statistics
            $table->integer('successful_payments')->default(0);
            $table->integer('failed_payments')->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            
            // Relationships
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->timestamps();
            
            $table->index(['status', 'next_billing_date']);
            $table->index(['customer_email', 'status']);
            $table->index(['country', 'currency']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('dpo_subscriptions');
    }
};
