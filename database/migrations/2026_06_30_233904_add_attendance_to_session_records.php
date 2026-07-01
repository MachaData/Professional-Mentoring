<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            // attended | absent | rescheduled | pending
            $table->string('attendance')->default('pending')->after('status');
            // in_person | virtual
            $table->string('modality')->nullable()->after('attendance');
        });
    }

    public function down(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            $table->dropColumn(['attendance', 'modality']);
        });
    }
};
