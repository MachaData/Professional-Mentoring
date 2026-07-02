<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\PortalLoginController;
use App\Http\Controllers\Mentor\MentorController;
use App\Http\Controllers\Participant\ParticipantController;
use App\Http\Middleware\SetLocale;
use App\Models\SessionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->to(PortalLoginController::homeFor(Auth::user()))
    : redirect()->route('portal.login'));

// --- Locale switch ----------------------------------------------------------
Route::get('/locale/{locale}', function (Request $request, string $locale) {
    if (in_array($locale, SetLocale::SUPPORTED, true)) {
        $request->session()->put('locale', $locale);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }
    }

    return back();
})->name('locale.switch');

// --- Authentication ---------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [PortalLoginController::class, 'show'])->name('portal.login');
    Route::post('/login', [PortalLoginController::class, 'login'])->name('portal.login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [PortalLoginController::class, 'logout'])->name('portal.logout');

    Route::post('/onboarding/seen', function (Request $request) {
        $request->user()->forceFill(['onboarding_seen_at' => now()])->save();

        return response()->noContent();
    })->name('onboarding.seen');
    Route::get('/password/change', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');
});

// --- Mentor portal ----------------------------------------------------------
Route::middleware(['auth', 'password.changed', 'role:facilitator'])
    ->prefix('mentor')->group(function () {
        Route::get('/', [MentorController::class, 'dashboard'])->name('mentor.dashboard');
        Route::get('/participants/{assignment}', [MentorController::class, 'participant'])->name('mentor.participant');
        Route::get('/participants/{assignment}/espacio', [MentorController::class, 'space'])->name('mentor.space');
        Route::get('/records/{record}/register', [MentorController::class, 'register'])->name('mentor.register');
    });

// --- Participant portal -----------------------------------------------------
Route::middleware(['auth', 'password.changed', 'role:participant'])
    ->prefix('me')->group(function () {
        Route::get('/', [ParticipantController::class, 'dashboard'])->name('participant.dashboard');
        Route::get('/espacio', [ParticipantController::class, 'space'])->name('participant.space');
        Route::get('/sessions/{session}', [ParticipantController::class, 'session'])->name('participant.session');
    });
