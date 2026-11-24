<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitored_sim_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 80)->unique();
            $table->enum('type', ['credit', 'debit']);
            $table->unsignedBigInteger('amount_kobo');
            $table->string('description');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};