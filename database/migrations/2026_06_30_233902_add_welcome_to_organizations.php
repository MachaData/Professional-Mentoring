<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('welcome_enabled')->default(true)->after('welcome_text');
            $table->string('welcome_video_url')->nullable()->after('welcome_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['welcome_enabled', 'welcome_video_url']);
        });
    }
};
