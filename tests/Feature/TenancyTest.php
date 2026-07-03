<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_subdomain_resolves_the_tenant_by_slug(): void
    {
        config(['tenancy.base_domain' => 'pro-mentoring.com']);

        $request = Request::create('http://crosspartners-group.pro-mentoring.com/login');
        $resolved = null;

        (new ResolveTenant)->handle($request, function () use (&$resolved) {
            $resolved = app('tenant');

            return response('ok');
        });

        $this->assertNotNull($resolved);
        $this->assertSame('crosspartners-group', $resolved->slug);
    }

    public function test_central_subdomain_has_no_tenant(): void
    {
        config(['tenancy.base_domain' => 'pro-mentoring.com']);

        $request = Request::create('http://app.pro-mentoring.com/login');
        $bound = true;

        (new ResolveTenant)->handle($request, function () use (&$bound) {
            $bound = app()->bound('tenant');

            return response('ok');
        });

        $this->assertFalse($bound);
    }

    public function test_login_page_shows_the_tenant_name(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->firstOrFail();

        $this->get('/login?tenant=crosspartners-group')
            ->assertSuccessful()
            ->assertSee($org->name);
    }

    public function test_member_can_log_in_on_their_tenant_space(): void
    {
        $mentee = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->post('/login?tenant=crosspartners-group', [
            'email' => 'mentee@demo.test',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($mentee);
    }

    public function test_outsider_is_rejected_on_a_foreign_tenant_space(): void
    {
        Organization::create(['name' => 'Otra Empresa', 'slug' => 'otra-empresa']);

        $this->post('/login?tenant=otra-empresa', [
            'email' => 'mentee@demo.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
