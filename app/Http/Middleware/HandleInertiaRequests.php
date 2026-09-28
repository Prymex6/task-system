<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Notification;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->getAuthUser(),
                'contact' => fn () => $this->getAuthContact(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'workspace' => fn () => $this->getWorkspaceData(),
            'unread_notifications' => fn () => $this->getUnreadNotifications(),
            'app_name' => config('app.name'),
            'current_locale' => fn () => app()->getLocale(),
        ];
    }

    protected function getAuthUser(): ?array
    {
        if ($user = auth('tenant')->user()) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'workspace_role' => $user->workspace_role,
                'avatar' => $user->avatar_url ?? null,
                'permissions' => $this->getPermissionsFor($user),
            ];
        }

        if ($user = auth('super_admin')->user()) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'workspace_role' => 'super_admin',
                'permissions' => ['*'],
            ];
        }

        return null;
    }

    protected function getAuthContact(): ?array
    {
        if ($contact = auth('customer')->user()) {
            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone ?? null,
                'position' => $contact->position ?? null,
                'client_id' => $contact->client_id,
            ];
        }

        return null;
    }

    protected function getPermissionsFor($user): array
    {
        try {
            if (in_array($user->workspace_role, ['owner', 'admin'])) {
                // Owners/admins have all permissions
                return ['*'];
            }

            return RolePermission::permissionsFor($user->workspace_role);
        } catch (\Exception) {
            return [];
        }
    }

    protected function getWorkspaceData(): ?array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return null;
            }

            $s = Setting::getAllAsArray();

            return [
                'id' => tenancy()->tenant->id,
                'name' => $s['company_name'] ?? config('app.name'),
                'email' => $s['company_email'] ?? null,
                'phone' => $s['company_phone'] ?? null,
                'address' => $s['company_address'] ?? null,
                'nip' => $s['company_nip'] ?? null,
                'currency' => $s['default_currency'] ?? 'PLN',
                'logo_url' => $s['logo_url'] ?? null,
                'favicon_url' => $s['favicon_url'] ?? null,
                'timezone' => $s['timezone'] ?? 'Europe/Warsaw',
                'date_format' => $s['date_format'] ?? 'd.m.Y',
            ];
        } catch (\Exception) {
            return ['name' => config('app.name')];
        }
    }

    protected function getUnreadNotifications(): int
    {
        try {
            if ($user = auth('tenant')->user()) {
                return Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count();
            }

            return 0;
        } catch (\Exception) {
            return 0;
        }
    }
}
