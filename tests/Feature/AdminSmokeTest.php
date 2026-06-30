<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_superadmin_can_open_admin_pages(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();

        foreach ([
            '/admin',
            '/admin/organizations', '/admin/organizations/create',
            '/admin/users', '/admin/users/create',
            '/admin/program-types', '/admin/program-types/create',
            '/admin/clients', '/admin/clients/create',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertSuccessful();
        }
    }

    public function test_org_admin_cannot_open_organizations(): void
    {
        $u = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $this->actingAs($u)->get('/admin/organizations')->assertForbidden();
    }

    public function test_facilitator_cannot_access_admin_panel(): void
    {
        $org = \App\Models\Organization::first();
        $facilitator = User::factory()->create([
            'organization_id' => $org->id,
            'role' => User::ROLE_FACILITATOR,
        ]);

        $this->actingAs($facilitator)->get('/admin')->assertForbidden();
    }
}
