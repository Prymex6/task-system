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
     * Tworzy i wysyła zaproszenie do workspace.
     */
    public function invite(string $email, string $role, User $invitedBy): Invitation
    {
        // Usuń poprzednie nieprzyjęte zaproszenie dla tego emaila
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
     * Akceptuje zaproszenie i tworzy użytkownika lub przypisuje do workspace.
     */
    public function accept(Invitation $invitation, array $userData): User
    {
        // Sprawdź czy user już istnieje (zmiana workspace)
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
            // Aktualizuj rolę dla istniejącego użytkownika
            $user->update(['workspace_role' => $invitation->workspace_role]);
        }

        $invitation->update(['accepted_at' => now()]);

        return $user;
    }

    /**
     * Sprawdza czy zaproszenie jest ważne.
     */
    public function isValid(Invitation $invitation): bool
    {
        return $invitation->accepted_at === null
            && $invitation->expires_at->isFuture();
    }

    /**
     * Usuwa wygasłe zaproszenia.
     */
    public function cleanExpired(): int
    {
        return Invitation::where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->delete();
    }
}
