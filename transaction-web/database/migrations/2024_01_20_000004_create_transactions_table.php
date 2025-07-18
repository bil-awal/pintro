<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 100)->unique();
            $table->string('reference', 100)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_account_id')->nullable()->constrained('users');
            $table->foreignId('to_account_id')->nullable()->constrained('users');
            $table->enum('type', ['topup', 'payment', 'transfer', 'withdrawal'])->default('payment');
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('IDR');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_gateway_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Performance indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['from_account_id', 'created_at']);
            $table->index(['to_account_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
            $table->index(['payment_gateway_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
