<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tenancy (null for superadmin)
            $table->foreignId('organization_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();

            // Base role: superadmin | organization_admin | facilitator | participant
            $table->string('role')->default('participant')->after('email');

            // Profile (mentors & mentees share the same rich profile)
            $table->string('phone')->nullable()->after('role');
            $table->string('photo')->nullable()->after('phone');
            $table->string('position')->nullable()->after('photo');     // cargo
            $table->string('area')->nullable()->after('position');
            $table->string('business_unit')->nullable()->after('area'); // unidad de negocio
            $table->string('company')->nullable()->after('business_unit');
            $table->text('bio')->nullable()->after('company');          // breve descripción

            $table->string('timezone')->default('America/Lima')->after('bio');
            $table->string('locale', 5)->default('es')->after('timezone');

            // Invitation lifecycle: pending | sent | active
            $table->string('invitation_status')->default('pending')->after('locale');
            $table->timestamp('invited_at')->nullable()->after('invitation_status');
            $table->boolean('must_change_password')->default(false)->after('invited_at');

            $table->string('status')->default('active')->after('must_change_password'); // active|inactive

            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'role', 'phone', 'photo', 'position', 'area', 'business_unit',
                'company', 'bio', 'timezone', 'locale', 'invitation_status',
                'invited_at', 'must_change_password', 'status',
            ]);
        });
    }
};
