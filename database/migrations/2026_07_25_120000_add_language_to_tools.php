<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            // Language the document itself is written in — es, en, pt, fr…
            // NULL means "sin idioma específico" and, unlike business_unit,
            // language never hides a material: it is shown as a reference badge
            // and used to filter/differentiate. Existing rows stay NULL.
            $table->string('language', 5)->nullable()->after('business_unit');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['language']);
            $table->dropColumn('language');
        });
    }
};
