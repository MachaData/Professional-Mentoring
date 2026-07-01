<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('reminder_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('session_id')->nullable()->after('reminder_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reminder_id');
            $table->dropConstrainedForeignId('session_id');
        });
    }
};
