<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\PortalLoginController;
use App\Http\Controllers\Mentor\MentorController;
use App\Http\Controllers\Participant\ParticipantController;
use App\Models\SessionRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->to(PortalLoginController::homeFor(Auth::user()))
    : redirect()->route('portal.login'));

// --- Authentication ---------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [PortalLoginController::class, 'show'])->name('portal.login');
    Route::post('/login', [PortalLoginController::class, 'login'])->name('portal.login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [PortalLoginController::class, 'logout'])->name('portal.logout');
    Route::get('/password/change', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');
});

// --- Mentor portal ----------------------------------------------------------
Route::middleware(['auth', 'password.changed', 'role:facilitator'])
    ->prefix('mentor')->group(function () {
        Route::get('/', [MentorController::class, 'dashboard'])->name('mentor.dashboard');
        Route::get('/participants/{assignment}', [MentorController::class, 'participant'])->name('mentor.participant');
        Route::get('/records/{record}/register', function (SessionRecord $record) {
            return view('mentor.register', compact('record'));
        })->name('mentor.register');
    });

// --- Participant portal -----------------------------------------------------
Route::middleware(['auth', 'password.changed', 'role:participant'])
    ->prefix('me')->group(function () {
        Route::get('/', [ParticipantController::class, 'dashboard'])->name('participant.dashboard');
    });
