<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // New organizations should start with the default welcome popup off; it is
    // opt-in via the organization form and the per-program popups.
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('welcome_enabled')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('welcome_enabled')->default(true)->change();
        });
    }
};
