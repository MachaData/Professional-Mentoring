<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function coordinator(): User
    {
        return User::where('email', 'coordinador@demo.test')->firstOrFail();
    }

    public function test_coordinator_can_access_supervision_pages(): void
    {
        $u = $this->coordinator();

        foreach ([
            '/admin',                 // dashboard + reports
            '/admin/programs',
            '/admin/sessions',
            '/admin/users',
            '/admin/assignments',
            '/admin/tools',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertSuccessful();
        }
    }

    public function test_coordinator_can_view_a_dupla_detail(): void
    {
        $u = $this->coordinator();
        $assignment = Assignment::firstOrFail();

        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}")->assertSuccessful();
    }

    public function test_coordinator_can_manage_duplas_and_users(): void
    {
        $u = $this->coordinator();
        $assignment = Assignment::firstOrFail();

        // Supervisors may create/edit duplas and mentors/mentees to follow up.
        $this->actingAs($u)->get('/admin/assignments/create')->assertSuccessful();
        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}/edit")->assertSuccessful();
        $this->actingAs($u)->get('/admin/users/create')->assertSuccessful();

        // But program configuration stays off-limits.
        $this->actingAs($u)->get('/admin/programs/create')->assertForbidden();
    }

    public function test_coordinator_cannot_access_configuration_resources(): void
    {
        $u = $this->coordinator();

        foreach ([
            '/admin/clients',
            '/admin/program-types',
            '/admin/form-templates',
            '/admin/email-templates',
            '/admin/reminders',
            '/admin/organizations',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertForbidden();
        }
    }

    public function test_facilitator_still_cannot_access_admin(): void
    {
        $facilitator = User::where('email', 'mentor@demo.test')->firstOrFail();
        $this->actingAs($facilitator)->get('/admin')->assertForbidden();
    }
}
