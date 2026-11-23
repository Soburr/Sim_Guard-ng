<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_bank_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_sim_id')->constrained()->cascadeOnDelete();
            $table->string('bank_key', 30); 
            $table->timestamps();

            $table->unique(['monitored_sim_id', 'bank_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_bank_links');
    }
};