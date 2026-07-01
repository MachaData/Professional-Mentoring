<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_records', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
        });
    }
};
