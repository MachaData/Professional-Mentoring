<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions can now be either program-wide (assignment_id NULL, shared by every
 * dupla — the standard curriculum) or specific to a single dupla (assignment_id
 * set — an extra session a coordinator/admin adds for that pair only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('assignment_id')->nullable()->after('program_id')
                ->constrained()->cascadeOnDelete();
            $table->index(['assignment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignment_id');
        });
    }
};
