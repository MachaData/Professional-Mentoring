<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'invitation_status' => 'active',
        ])->save();

        return redirect()->to(PortalLoginController::homeFor($user))
            ->with('status', __('Tu contraseña ha sido actualizada.'));
    }
}
