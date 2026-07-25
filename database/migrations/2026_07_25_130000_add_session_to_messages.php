<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Optional session a message refers to ("sobre la Sesión 3…"). NULL —
            // the value every existing message keeps — means a general message of
            // the dupla. nullOnDelete so removing a session never deletes history.
            $table->foreignId('session_id')->nullable()->after('assignment_id')
                ->constrained()->nullOnDelete();
            $table->index(['assignment_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['assignment_id', 'session_id']);
            $table->dropConstrainedForeignId('session_id');
        });
    }
};
