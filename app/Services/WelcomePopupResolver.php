<?php

namespace App\Services;

use App\Models\User;
use App\Models\WelcomePopup;

/**
 * Resolves the welcome popup a portal user should see, based on their program
 * (from their assignment) and role. Returns null when none is configured/enabled.
 */
class WelcomePopupResolver
{
    public function resolve(?User $user): ?WelcomePopup
    {
        if (! $user || ! in_array($user->role, [User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT], true)) {
            return null;
        }

        $programId = $this->programIdFor($user);
        if (! $programId) {
            return null;
        }

        return WelcomePopup::query()
            ->where('program_id', $programId)
            ->where('role', $user->role)
            ->where('enabled', true)
            ->first();
    }

    /** Should the popup be shown to this user right now (not yet seen since last update)? */
    public function shouldShow(?User $user, ?WelcomePopup $popup): bool
    {
        if (! $user || ! $popup) {
            return false;
        }

        $hasContent = filled($popup->getTranslation('body', app()->getLocale(), false))
            || filled($popup->video_url);

        if (! $hasContent) {
            return false;
        }

        // Show if never seen, or if the popup was updated after the last dismissal.
        return $user->onboarding_seen_at === null
            || $popup->updated_at?->gt($user->onboarding_seen_at);
    }

    protected function programIdFor(User $user): ?int
    {
        $relation = $user->role === User::ROLE_FACILITATOR
            ? $user->assignmentsAsFacilitator()
            : $user->assignmentsAsParticipant();

        return $relation->latest('id')->value('program_id');
    }
}
