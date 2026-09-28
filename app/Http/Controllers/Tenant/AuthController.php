<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
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
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.staff.redirect');
        }

        return Inertia::render('Tenant/Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('tenant')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::guard('tenant')->user();

            if (!$user->is_active) {
                Auth::guard('tenant')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                Log::warning('Auth[staff]: logowanie na dezaktywowane konto', ['email' => $credentials['email'], 'ip' => $request->ip()]);

                throw ValidationException::withMessages([
                    'email' => 'Twoje konto zostało dezaktywowane.',
                ]);
            }

            Log::info('Auth[staff]: zalogowano', ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'ip' => $request->ip()]);

            return redirect()->intended(route('tenant.staff.redirect'));
        }

        Log::warning('Auth[staff]: nieudane logowanie', ['email' => $credentials['email'], 'ip' => $request->ip()]);

        throw ValidationException::withMessages([
            'email' => 'Podane dane logowania są nieprawidłowe.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        Log::info('Auth[staff]: wylogowano', ['user_id' => $user?->id, 'email' => $user?->email, 'ip' => $request->ip()]);
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }

    /**
     * Redirect user to appropriate panel based on role.
     */
    public function redirectByRole()
    {
        $user = Auth::guard('tenant')->user();

        return match ($user->role) {
            'manager' => redirect()->route('tenant.manager.dashboard'),
            'warehouse' => redirect()->route('tenant.staff.warehouse'),
            default => redirect()->route('tenant.staff.fulfillment'),
        };
    }

    public function showForgotPassword()
    {
        return Inertia::render('Tenant/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::broker('tenant_users')->sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            Log::warning('Staff password reset email failed: ' . $e->getMessage());
            $status = Password::RESET_LINK_SENT;
        }

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Auth[staff]: link reset hasła wysłany', ['email' => $request->email]);

            return back()->with('success', __('messages.reset_link_sent_check_inbox'));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        $email = $request->email;
        $tokenValid = false;

        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $tokenValid = Password::broker('tenant_users')->tokenExists($user, $token);
            }
        }

        return Inertia::render('Tenant/Auth/ResetPassword', [
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

        $status = Password::broker('tenant_users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Auth[staff]: hasło zresetowane', ['email' => $request->email]);

            return redirect()->route('tenant.login')->with('status', __('messages.password_changed_sign_in'));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
