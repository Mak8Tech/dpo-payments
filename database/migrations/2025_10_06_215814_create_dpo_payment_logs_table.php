<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpo_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('token')->nullable();
            $table->string('action'); // create_token, verify_token, refund, etc.
            $table->enum('type', ['request', 'response', 'callback']);
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->string('status_code')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['reference', 'created_at']);
            $table->index(['token', 'action']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('dpo_payment_logs');
    }
};