<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('emergency_phone', 20)->nullable()->after('email');
            $table->timestamp('emergency_phone_verified_at')->nullable()->after('emergency_phone');
            $table->boolean('alert_via_whatsapp')->default(true)->after('emergency_phone_verified_at');
            $table->boolean('alert_via_email')->default(true)->after('alert_via_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'emergency_phone',
                'emergency_phone_verified_at',
                'alert_via_whatsapp',
                'alert_via_email',
            ]);
        });
    }
};