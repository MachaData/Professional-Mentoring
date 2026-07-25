<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            // Matches users.business_unit. NULL means "every business unit".
            $table->string('business_unit')->nullable()->after('visibility');
            $table->index('business_unit');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['business_unit']);
            $table->dropColumn('business_unit');
        });
    }
};
