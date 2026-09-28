<?php

namespace App\Services;

use App\Mail\TeamInvitationMail;
use App\Models\Tenant\Invitation;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Creates an invitation and sends it.
     */
    public function invite(string $email, string $role, User $invitedBy): Invitation
    {
        // Drop an earlier invitation to the same address that was never accepted
        Invitation::where('email', $email)->whereNull('accepted_at')->delete();

        $invitation = Invitation::create([
            'email' => $email,
            'workspace_role' => $role,
            'token' => Str::random(64),
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->queue(new TeamInvitationMail($invitation, $invitedBy));

        return $invitation;
    }

    /**
     * Accepts an invitation, creating the user or moving an existing one across.
     */
    public function accept(Invitation $invitation, array $userData): User
    {
        // They may already have an account, from another workspace
        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $invitation->email,
                'password' => bcrypt($userData['password']),
                'workspace_role' => $invitation->workspace_role,
                'is_active' => true,
            ]);
        } else {
            // An existing account takes the role the invitation carried
            $user->update(['workspace_role' => $invitation->workspace_role]);
        }

        $invitation->update(['accepted_at' => now()]);

        return $user;
    }

    /**
     * Whether an invitation can still be used.
     */
    public function isValid(Invitation $invitation): bool
    {
        return $invitation->accepted_at === null
            && $invitation->expires_at->isFuture();
    }

    /**
     * Clears out invitations nobody used in time.
     */
    public function cleanExpired(): int
    {
        return Invitation::where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->delete();
    }
}
