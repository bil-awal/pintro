<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->string('gateway_transaction_id');
            $table->enum('gateway_status', ['pending', 'settlement', 'capture', 'deny', 'cancel', 'expire', 'failure']);
            $table->json('raw_payload');
            $table->string('signature')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('transaction_id')->references('transaction_id')->on('transactions')->onDelete('cascade');
            $table->index(['transaction_id', 'received_at']);
            $table->index(['gateway_transaction_id']);
            $table->index(['gateway_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
    }
};
