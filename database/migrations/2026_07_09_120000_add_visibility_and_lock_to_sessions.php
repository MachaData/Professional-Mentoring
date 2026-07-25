<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Visibility is per audience: a session hidden from a role never
            // shows up in that role's portal at all.
            $table->boolean('visible_to_facilitator')->default(true)->after('visible_to_participant');

            // A locked session may still be visible ("Próximamente"), but nobody
            // can open it. It opens when an admin flips this off, or on its own
            // once unlock_at arrives.
            $table->boolean('is_locked')->default(false)->after('visible_to_facilitator');
            $table->date('unlock_at')->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['visible_to_facilitator', 'is_locked', 'unlock_at']);
        });
    }
};
