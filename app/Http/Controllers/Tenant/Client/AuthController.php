<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ClientContact;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.portal.dashboard');
        }

        return Inertia::render('Tenant/Client/Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $contact = Auth::guard('customer')->user();

            if (!$contact->portal_access) {
                Auth::guard('customer')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                Log::warning('Auth[portal]: brak dostępu do portalu', ['email' => $credentials['email'], 'ip' => $request->ip()]);

                throw ValidationException::withMessages([
                    'email' => 'Twoje konto nie ma dostępu do portalu klienta.',
                ]);
            }

            Log::info('Auth[portal]: zalogowano', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'client_id' => $contact->client_id,
                'ip' => $request->ip(),
            ]);

            return redirect()->intended(route('tenant.portal.dashboard'));
        }

        Log::warning('Auth[portal]: nieudane logowanie', ['email' => $credentials['email'], 'ip' => $request->ip()]);

        throw ValidationException::withMessages([
            'email' => 'Podane dane logowania są nieprawidłowe.',
        ]);
    }

    public function logout(Request $request)
    {
        $contact = Auth::guard('customer')->user();
        Log::info('Auth[portal]: wylogowano', ['contact_id' => $contact?->id, 'email' => $contact?->email, 'ip' => $request->ip()]);
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.client.login');
    }

    public function showForgotPassword()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.portal.dashboard');
        }

        return Inertia::render('Tenant/Client/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::broker('client_contacts')->sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            Log::warning('Portal password reset email failed: ' . $e->getMessage());
            $status = Password::RESET_LINK_SENT;
        }

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Auth[portal]: link reset hasła wysłany', ['email' => $request->email]);

            return back()->with('success', __('messages.reset_link_sent'));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        $email = $request->email;
        $tokenValid = false;

        if ($email) {
            $contact = ClientContact::where('email', $email)->first();
            if ($contact) {
                $tokenValid = Password::broker('client_contacts')->tokenExists($contact, $token);
            }
        }

        return Inertia::render('Tenant/Client/Auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
            'tokenValid' => $tokenValid,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('client_contacts')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Auth[portal]: hasło zresetowane', ['email' => $request->email]);

            return redirect()->route('tenant.client.login')
                ->with('status', __('messages.password_changed_sign_in'));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
