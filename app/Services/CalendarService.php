<?php

namespace App\Services;

use App\Models\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds calendar data (a month grid of events) for the mentor, mentee and
 * coordinator portals. Events are placed on each session's start and end dates
 * and tinted by their schedule/completion state.
 */
class CalendarService
{
    /**
     * The month to display, taken from a `?m=YYYY-MM` query param (falls back to
     * the current month). Always normalised to the first day of the month.
     */
    public function month(?string $param): Carbon
    {
        if ($param && preg_match('/^\d{4}-\d{2}$/', $param)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $param.'-01')->startOfMonth();
            } catch (\Throwable) {
                // fall through
            }
        }

        return Carbon::today()->startOfMonth();
    }

    /**
     * Build the events map keyed by 'Y-m-d'.
     *
     * @param  Collection<int,Session>  $sessions
     * @param  array<int,string>  $statusBySession  session_id => SessionRecord status (optional)
     * @param  callable|null  $urlFor  fn(Session): ?string — link target per session
     * @return array<string,array<int,array{label:string,tone:string,url:?string,kind:string}>>
     */
    public function events(Collection $sessions, array $statusBySession = [], ?callable $urlFor = null): array
    {
        $locale = app()->getLocale();
        $today = Carbon::today();
        $events = [];

        foreach ($sessions as $session) {
            $label = 'S'.$session->number.' · '.$session->getTranslation('name', $locale);
            $url = $urlFor ? $urlFor($session) : null;
            $status = $statusBySession[$session->id] ?? null;
            $tone = $this->tone($session, $status, $today);

            if ($session->start_date) {
                $events[$session->start_date->format('Y-m-d')][] = [
                    'label' => $label, 'tone' => $tone, 'url' => $url, 'kind' => 'inicio',
                ];
            }

            if ($session->end_date
                && (! $session->start_date || ! $session->end_date->isSameDay($session->start_date))) {
                $events[$session->end_date->format('Y-m-d')][] = [
                    'label' => $label, 'tone' => $tone, 'url' => $url, 'kind' => 'cierre',
                ];
            }
        }

        return $events;
    }

    /** done (green) · overdue (red) · open (indigo) · upcoming (slate). */
    protected function tone(Session $session, ?string $status, Carbon $today): string
    {
        if ($status === \App\Models\SessionRecord::STATUS_COMPLETED) {
            return 'done';
        }

        if ($session->end_date && $session->end_date->lt($today)) {
            return 'overdue';
        }

        if ($session->isOpen()) {
            return 'open';
        }

        return 'upcoming';
    }
}
