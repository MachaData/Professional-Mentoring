<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Assignments\Pages\ViewAssignment;
use App\Filament\Resources\Assignments\RelationManagers\FilesRelationManager;
use App\Filament\Resources\Assignments\RelationManagers\FollowupsRelationManager;
use App\Filament\Resources\Assignments\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\Sessions\SessionResource;
use App\Filament\Resources\Tools\ToolResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    /**
     * The coordinator supervises but never destroys: no resource offers them a
     * delete (Filament authorises DeleteAction/DeleteBulkAction through these
     * very methods), and program configuration stays read-only.
     */
    public function test_coordinator_cannot_delete_or_configure_anything(): void
    {
        $this->actingAs($this->coordinator());

        foreach ([
            AssignmentResource::class,
            UserResource::class,
            ProgramResource::class,
            SessionResource::class,
            ToolResource::class,
        ] as $resource) {
            $this->assertFalse($resource::canDeleteAny(), $resource.' no debe permitir eliminar.');
        }

        // Duplas and people: may edit (follow-up), never delete.
        $this->assertTrue(AssignmentResource::canEdit(Assignment::firstOrFail()));
        $this->assertFalse(AssignmentResource::canDelete(Assignment::firstOrFail()));

        // Program configuration: read-only.
        $this->assertFalse(ProgramResource::canCreate());
        $this->assertFalse(SessionResource::canCreate());
        $this->assertFalse(ToolResource::canCreate());
    }

    /** Mailbox, shared files and follow-up history are open for supervision. */
    public function test_coordinator_can_review_mailbox_files_and_followups(): void
    {
        $u = $this->coordinator();
        $assignment = Assignment::firstOrFail();

        $this->assertTrue(MessagesRelationManager::canViewForRecord($assignment, ViewAssignment::class));
        $this->assertTrue(FilesRelationManager::canViewForRecord($assignment, ViewAssignment::class));

        foreach ([MessagesRelationManager::class, FilesRelationManager::class, FollowupsRelationManager::class] as $manager) {
            Livewire::actingAs($u)
                ->test($manager, ['ownerRecord' => $assignment, 'pageClass' => ViewAssignment::class])
                ->assertOk();
        }
    }

    /** Someone else's conversation is for reading only — never for writing. */
    public function test_nobody_can_write_in_a_duplas_mailbox_from_the_panel(): void
    {
        $assignment = Assignment::firstOrFail();

        foreach ([$this->coordinator(), User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail()] as $user) {
            $manager = Livewire::actingAs($user)
                ->test(MessagesRelationManager::class, ['ownerRecord' => $assignment, 'pageClass' => ViewAssignment::class]);

            $this->assertTrue($manager->instance()->isReadOnly());
        }
    }

    public function test_facilitator_still_cannot_access_admin(): void
    {
        $facilitator = User::where('email', 'mentor@demo.test')->firstOrFail();
        $this->actingAs($facilitator)->get('/admin')->assertForbidden();
    }
}
