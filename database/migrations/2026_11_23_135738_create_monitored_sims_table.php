<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_sims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable(); // e.g. "Old Zenith Line"
            $table->string('phone_number', 15);      // Normalized E.164: +2348031234567
            $table->enum('telco', ['mtn', 'airtel', 'glo', '9mobile']);
            $table->enum('mode', ['keep_alive', 'watchdog'])->default('keep_alive');
            $table->enum('status', [
                'active',             
                'dormant',            
                'compromised_woken_up' 
            ])->default('active');

            // Lifecycle & probe timestamps
            $table->timestamp('last_rga_at')->nullable();
            $table->timestamp('next_rga_due_at')->nullable();
            $table->timestamp('last_probe_at')->nullable();
            $table->string('last_network_state', 40)->nullable();
            $table->timestamps();

            // Prevent the same user from registering the same SIM twice
            $table->unique(['user_id', 'phone_number']);
            $table->index(['mode', 'status', 'next_rga_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_sims');
    }
};