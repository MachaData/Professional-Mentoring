<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            // Per-dupla join link the mentor coordinates with the participant.
            $table->string('meeting_url')->nullable()->after('real_session_date');
        });
    }

    public function down(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            $table->dropColumn('meeting_url');
        });
    }
};
