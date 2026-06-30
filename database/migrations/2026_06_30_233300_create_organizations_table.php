<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();

            // Branding
            $table->string('logo')->nullable();
            $table->string('login_background')->nullable();
            $table->string('primary_color')->default('#E30613');   // CrossPartners red
            $table->string('secondary_color')->default('#1A1A1A');

            // Editable copy (translatable JSON: {"es": "...", "en": "..."})
            $table->json('welcome_text')->nullable();
            $table->json('footer_text')->nullable();

            $table->string('default_locale', 5)->default('es');
            $table->boolean('is_operator')->default(false); // CrossPartners = operadora
            $table->string('status')->default('active');    // active|inactive
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
