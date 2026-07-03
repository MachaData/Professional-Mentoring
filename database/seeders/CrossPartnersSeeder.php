<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\ProgramType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Initial state for Professional Mentoring:
 *  - CrossPartners Group as operator organization
 *  - Las Bambas as client
 *  - Mentoring program type (Mentor / Mentee)
 *  - A superadmin and an organization admin
 *
 * Everything seeded here is editable from the admin panel afterwards.
 */
class CrossPartnersSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::updateOrCreate(
            ['slug' => 'crosspartners-group'],
            [
                'name' => 'CrossPartners Group',
                'domain' => 'pro-mentoring.com',
                'primary_color' => '#E30613',
                'secondary_color' => '#1A1A1A',
                'is_operator' => true,
                'default_locale' => 'es',
                'welcome_text' => [
                    'es' => "Te damos la bienvenida a Professional Mentoring.\nAquí encontrarás tus sesiones, materiales y acuerdos. Revisa el video para conocer cómo aprovechar la plataforma.",
                    'en' => "Welcome to Professional Mentoring.\nHere you'll find your sessions, materials and agreements. Watch the video to learn how to make the most of the platform.",
                ],
                'welcome_enabled' => true,
                'welcome_video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'footer_text' => [
                    'es' => 'Professional Mentoring © by CrossPartners Group',
                    'en' => 'Professional Mentoring © by CrossPartners Group',
                ],
                'status' => 'active',
            ]
        );

        Client::updateOrCreate(
            ['organization_id' => $org->id, 'slug' => 'las-bambas'],
            [
                'name' => 'Las Bambas',
                'primary_color' => '#D2232A',
                'secondary_color' => '#00843D',
                'welcome_text' => [
                    'es' => 'Programa Professional Mentoring — Las Bambas',
                    'en' => 'Professional Mentoring Program — Las Bambas',
                ],
                'status' => 'active',
            ]
        );

        $mentoring = ProgramType::where('organization_id', $org->id)
            ->whereRaw("name->>'es' = ?", ['Mentoring'])
            ->first() ?? new ProgramType(['organization_id' => $org->id]);

        $mentoring->fill([
            'organization_id' => $org->id,
            'name' => ['es' => 'Mentoring', 'en' => 'Mentoring'],
            'description' => [
                'es' => 'Programa de acompañamiento basado en sesiones entre mentor y mentee.',
                'en' => 'Session-based accompaniment program between mentor and mentee.',
            ],
            'facilitator_label' => ['es' => 'Mentor', 'en' => 'Mentor'],
            'participant_label' => ['es' => 'Mentee', 'en' => 'Mentee'],
            'status' => 'active',
        ])->save();

        $this->superadmin();
        $this->organizationAdmin($org);
    }

    protected function superadmin(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'superadmin@pro-mentoring.com'],
            [
                'name' => 'Super Admin',
                'role' => User::ROLE_SUPERADMIN,
                'organization_id' => null,
                'locale' => 'es',
                'invitation_status' => 'active',
                'status' => 'active',
                'password' => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([User::ROLE_SUPERADMIN]);
    }

    protected function organizationAdmin(Organization $org): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@crosspartnersgroup.com'],
            [
                'name' => 'CrossPartners Admin',
                'role' => User::ROLE_ORG_ADMIN,
                'organization_id' => $org->id,
                'company' => 'CrossPartners Group',
                'locale' => 'es',
                'invitation_status' => 'active',
                'status' => 'active',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([User::ROLE_ORG_ADMIN]);
    }
}
