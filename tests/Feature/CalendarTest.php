<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_mentor_can_open_calendar(): void
    {
        $mentor = User::where('email', 'mentor@demo.test')->firstOrFail();
        $this->actingAs($mentor)->get('/mentor/calendario')
            ->assertSuccessful()
            ->assertSee('Cronograma')
            ->assertSee('Indicadores por dupla');
    }

    public function test_participant_can_open_calendar(): void
    {
        $mentee = User::where('email', 'mentee@demo.test')->firstOrFail();
        $this->actingAs($mentee)->get('/me/calendario')
            ->assertSuccessful()
            ->assertSee('Cronograma')
            ->assertSee('Próxima sesión');
    }

    public function test_coordinator_can_open_calendar_page(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();
        $this->actingAs($coordinator)->get('/admin/calendar')
            ->assertSuccessful()
            ->assertSee('Cronograma');
    }

    public function test_facilitator_cannot_open_calendar_page(): void
    {
        $facilitator = User::where('email', 'mentor@demo.test')->firstOrFail();
        $this->actingAs($facilitator)->get('/admin/calendar')->assertForbidden();
    }

    public function test_month_param_is_parsed_and_defaults(): void
    {
        $service = new CalendarService;

        $this->assertSame('2026-03-01', $service->month('2026-03')->format('Y-m-d'));
        $this->assertSame(Carbon::today()->startOfMonth()->format('Y-m-d'), $service->month('garbage')->format('Y-m-d'));
        $this->assertSame(Carbon::today()->startOfMonth()->format('Y-m-d'), $service->month(null)->format('Y-m-d'));
    }

    public function test_events_are_keyed_by_date(): void
    {
        $service = new CalendarService;
        $sessions = Session::query()->whereNotNull('start_date')->orderBy('sort_order')->get();

        $this->assertNotEmpty($sessions, 'demo seeder should create dated sessions');

        $events = $service->events($sessions);
        $firstKey = array_key_first($events);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $firstKey);
        $this->assertArrayHasKey('tone', $events[$firstKey][0]);
        $this->assertArrayHasKey('label', $events[$firstKey][0]);
    }
}
