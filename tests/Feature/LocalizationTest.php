<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_login_page_is_spanish_by_default(): void
    {
        $this->get('/login')->assertSuccessful()->assertSee('Ingresar');
    }

    public function test_switching_locale_translates_the_ui(): void
    {
        $this->get('/locale/en')->assertRedirect();
        $this->get('/login')->assertSuccessful()->assertSee('Sign in')->assertDontSee('Ingresar');
    }

    public function test_locale_switch_persists_on_user(): void
    {
        $user = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->actingAs($user)->get('/locale/en');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_unsupported_locale_is_ignored(): void
    {
        $this->get('/locale/fr')->assertRedirect();
        $this->get('/login')->assertSee('Ingresar'); // stays default
    }
}
