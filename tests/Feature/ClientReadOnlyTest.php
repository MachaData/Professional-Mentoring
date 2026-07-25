<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Filament\Pages\SendNotification;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The read-only "client" role: reaches the admin panel to see the desktop,
 * reports and indicators, but cannot create, edit, delete or configure.
 */
class ClientReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function client(): User
    {
        $orgId = User::where('email', 'coordinador@demo.test')->firstOrFail()->organization_id;

        return User::updateOrCreate(
            ['email' => 'cliente@demo.test'],
            [
                'name' => 'Cliente Demo',
                'role' => User::ROLE_CLIENT,
                'organization_id' => $orgId,
                'locale' => 'es', 'invitation_status' => 'active', 'status' => 'active',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
            ]
        );
    }

    public function test_client_can_view_desktop_reports_and_data(): void
    {
        $u = $this->client();

        foreach ([
            '/admin',              // desktop + indicators
            '/admin/reports',
            '/admin/calendar',
            '/admin/programs',
            '/admin/sessions',
            '/admin/assignments',
            '/admin/users',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertSuccessful();
        }
    }

    public function test_client_can_view_a_dupla_detail(): void
    {
        $u = $this->client();
        $assignment = Assignment::firstOrFail();

        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}")->assertSuccessful();
    }

    public function test_client_cannot_create_or_edit_anything(): void
    {
        $u = $this->client();
        $assignment = Assignment::firstOrFail();

        $this->actingAs($u)->get('/admin/assignments/create')->assertForbidden();
        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}/edit")->assertForbidden();
        $this->actingAs($u)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($u)->get('/admin/programs/create')->assertForbidden();
    }

    public function test_client_cannot_access_configuration_resources(): void
    {
        $u = $this->client();

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

    public function test_client_can_read_reports_but_not_send_notifications(): void
    {
        $this->actingAs($this->client());

        $this->assertTrue(Reports::canAccess());
        $this->assertFalse(SendNotification::canAccess());
    }

    public function test_client_has_no_write_capabilities(): void
    {
        $u = $this->client();

        $this->assertFalse($u->canManageContent());
        $this->assertFalse($u->canSuperviseDuplas());
        $this->assertTrue($u->isClient());
    }

    public function test_client_create_command_provisions_a_read_only_user(): void
    {
        $orgId = User::where('email', 'coordinador@demo.test')->firstOrFail()->organization_id;

        $this->artisan('client:create', [
            'name' => 'Cliente Comando',
            'email' => 'cmd-cliente@demo.test',
            '--org' => $orgId,
            '--password' => 'secret-pass-123',
        ])->assertSuccessful();

        $user = User::where('email', 'cmd-cliente@demo.test')->firstOrFail();
        $this->assertSame(User::ROLE_CLIENT, $user->role);
        $this->assertSame($orgId, $user->organization_id);
        $this->assertTrue($user->hasRole(User::ROLE_CLIENT));

        // Refuses to overwrite a non-client account with the same email.
        $this->artisan('client:create', [
            'name' => 'X',
            'email' => 'coordinador@demo.test',
            '--org' => $orgId,
        ])->assertFailed();
    }
}
