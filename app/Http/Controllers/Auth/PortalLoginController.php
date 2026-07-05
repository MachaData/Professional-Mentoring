<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PortalLoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->to(self::homeFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Las credenciales no coinciden con nuestros registros.'),
            ]);
        }

        // On a client subdomain, only members of that tenant may enter here
        // (superadmins of the operator can access any space).
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $user = Auth::user();
        if ($tenant && ! $user->isSuperadmin() && $user->organization_id !== $tenant->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('Esta cuenta no pertenece a este espacio.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(self::homeFor(Auth::user()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    /** Landing route by role. */
    public static function homeFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_SUPERADMIN, User::ROLE_ORG_ADMIN, User::ROLE_COORDINATOR => url('/admin'),
            User::ROLE_FACILITATOR => route('mentor.dashboard'),
            default => route('participant.dashboard'),
        };
    }
}
