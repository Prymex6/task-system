<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\Tenant\Invitation;
use App\Models\Tenant\User;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitationService) {}

    public function index()
    {
        $invitations = Invitation::with('invitedBy')
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Manager/HR/Invitations/Index', [
            'invitations' => $invitations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'workspace_role' => 'required|in:admin,manager,member,guest',
        ]);

        $user = Auth::guard('tenant')->user();

        // Sprawdź czy user z tym emailem już istnieje
        if (User::where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => __('messages.user_email_taken')]);
        }

        $invitation = $this->invitationService->invite(
            $validated['email'],
            $validated['workspace_role'],
            $user
        );

        return back()->with('success', __('messages.invitation_sent_to', ['email' => $invitation->email]));
    }

    public function destroy(Invitation $invitation)
    {
        $invitation->delete();

        return back()->with('success', __('messages.invitation_deleted'));
    }

    public function resend(Invitation $invitation)
    {
        if (!$this->invitationService->isValid($invitation)) {
            // Stwórz nowe zaproszenie
            $user = Auth::guard('tenant')->user();
            $invitation = $this->invitationService->invite(
                $invitation->email,
                $invitation->workspace_role,
                $user
            );
        } else {
            // Wyślij ponownie istniejące
            Mail::to($invitation->email)
                ->queue(new TeamInvitationMail($invitation, $invitation->invitedBy));
        }

        return back()->with('success', __('messages.invitation_resent'));
    }

    /**
     * Pokazuje formularz akceptacji zaproszenia (publiczny).
     */
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$this->invitationService->isValid($invitation)) {
            return Inertia::render('Tenant/Auth/InvitationExpired', [
                'email' => $invitation->email,
            ]);
        }

        return Inertia::render('Tenant/Auth/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'workspace_role' => $invitation->workspace_role,
            ],
        ]);
    }

    /**
     * Akceptuje zaproszenie i tworzy konto (publiczny).
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$this->invitationService->isValid($invitation)) {
            return back()->withErrors(['token' => __('messages.invitation_expired')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->invitationService->accept($invitation, $validated);

        Auth::guard('tenant')->login($user);
        $request->session()->regenerate();

        return redirect()->route('tenant.manager.dashboard')
            ->with('success', __('messages.account_activated'));
    }
}
