<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Currency;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TaskLabel;
use App\Models\Tenant\TaskStatus;
use App\Models\Tenant\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private const COMPANY_KEYS = [
        'company_name', 'company_nip', 'company_regon', 'company_address', 'company_zip',
        'company_city', 'company_country', 'company_phone', 'company_email', 'company_bank_account',
    ];

    private const FINANCE_KEYS = [
        'currency', 'invoice_prefix', 'invoice_footer', 'invoice_due_days',
        'invoice_number_format', 'default_tax_rate',
    ];

    private const NOTIFICATION_KEYS = [
        'notify_task_assigned', 'notify_task_commented', 'notify_task_due_soon',
        'notify_invoice_paid', 'notify_ticket_created', 'notify_daily_digest',
    ];

    /**
     * The settings hub. It only links onward: each area owns its own screen,
     * and loading all of their data here would cost a handful of queries for
     * something the page never shows.
     */
    public function index()
    {
        return Inertia::render('Tenant/Manager/Settings/Index');
    }

    // ── Task Statuses ─────────────────────────────────────────────────────────

    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:task_statuses,name',
            'color' => 'required|string|max:7',
            'is_default' => 'boolean',
            'is_done' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            TaskStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $order = TaskStatus::max('order') + 1;
        TaskStatus::create(array_merge($validated, ['order' => $order]));

        return back()->with('success', __('messages.task_status_created'));
    }

    public function updateStatus(Request $request, TaskStatus $status)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:task_statuses,name,' . $status->id,
            'color' => 'required|string|max:7',
            'is_default' => 'boolean',
            'is_done' => 'boolean',
        ]);

        $status->update($validated);

        return back()->with('success', __('messages.task_status_updated'));
    }

    public function destroyStatus(TaskStatus $status)
    {
        if ($status->tasks()->exists()) {
            return back()->withErrors(['error' => __('messages.status_has_tasks')]);
        }
        $status->delete();

        return back()->with('success', __('messages.task_status_deleted'));
    }

    // ── Task Labels ───────────────────────────────────────────────────────────

    public function storeLabel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ]);
        TaskLabel::create($validated);

        return back()->with('success', __('messages.label_created'));
    }

    public function destroyLabel(TaskLabel $label)
    {
        $label->delete();

        return back()->with('success', __('messages.label_deleted'));
    }

    // ── Tax Rates ─────────────────────────────────────────────────────────────

    public function storeTaxRate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);
        TaxRate::create($validated);

        return back()->with('success', __('messages.tax_rate_created'));
    }

    public function destroyTaxRate(TaxRate $taxRate)
    {
        $taxRate->delete();

        return back()->with('success', __('messages.tax_rate_deleted'));
    }

    // ── Role Permissions ──────────────────────────────────────────────────────

    public function rolePermissions()
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        $permissions = RolePermission::all()
            ->groupBy('role');

        return Inertia::render('Tenant/Manager/Settings/RolePermissions', [
            'permissions' => $permissions,
        ]);
    }

    // ── Sub-pages ─────────────────────────────────────────────────────────────

    public function notifications()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/Notifications', [
            'settings' => $this->group(self::NOTIFICATION_KEYS),
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $this->authorizeAdmin();

        $this->save($request->validate([
            'notify_task_assigned' => 'required|boolean',
            'notify_task_commented' => 'required|boolean',
            'notify_task_due_soon' => 'required|boolean',
            'notify_invoice_paid' => 'required|boolean',
            'notify_ticket_created' => 'required|boolean',
            'notify_daily_digest' => 'required|boolean',
        ]));

        return back()->with('success', __('messages.notification_settings_saved'));
    }

    public function company()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/Company', [
            'settings' => $this->group(self::COMPANY_KEYS),
        ]);
    }

    public function updateCompany(Request $request)
    {
        $this->authorizeAdmin();

        $this->save($request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_nip' => 'nullable|string|max:20',
            'company_regon' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:500',
            'company_zip' => 'nullable|string|max:10',
            'company_city' => 'nullable|string|max:100',
            'company_country' => 'nullable|string|max:100',
            'company_phone' => 'nullable|string|max:30',
            'company_email' => 'nullable|email|max:255',
            'company_bank_account' => 'nullable|string|max:50',
        ]));

        return back()->with('success', __('messages.company_details_saved'));
    }

    public function finance()
    {
        $this->authorizeAdmin();

        return Inertia::render('Tenant/Manager/Settings/Finance', [
            'settings' => $this->group(self::FINANCE_KEYS),
            'currencies' => Currency::orderBy('code')->get(),
            'taxRates' => TaxRate::orderBy('rate')->get(),
        ]);
    }

    public function updateFinance(Request $request)
    {
        $this->authorizeAdmin();

        $this->save($request->validate([
            'currency' => 'required|string|max:5',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_footer' => 'nullable|string|max:1000',
            'invoice_due_days' => 'required|integer|min:0|max:365',
            'invoice_number_format' => 'nullable|string|max:50',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
        ]));

        return back()->with('success', __('messages.finance_settings_saved'));
    }

    /**
     * Shared image upload for the settings screens: stores the file and
     * returns its path, so the page can save it under whichever key it wants.
     */
    public function upload(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'file' => 'required|image|max:2048',
            'key' => 'required|string|in:logo_url,invoice_logo_url,favicon_url',
        ]);

        $previous = Setting::get($validated['key']);
        if (is_string($previous) && str_starts_with($previous, 'logos/')) {
            Storage::disk('public')->delete($previous);
        }

        $path = $request->file('file')->store('logos', 'public');
        Setting::set($validated['key'], $path);

        return back()->with('success', __('messages.file_uploaded'));
    }

    public function updateRolePermissions(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.role' => 'required|in:admin,manager,member,guest',
            'permissions.*.action' => 'required|string',
            'permissions.*.allowed' => 'required|boolean',
        ]);

        foreach ($validated['permissions'] as $perm) {
            RolePermission::updateOrInsert(
                ['role' => $perm['role'], 'action' => $perm['action']],
                ['allowed' => $perm['allowed'], 'updated_at' => now()]
            );
        }

        return back()->with('success', __('messages.role_permissions_updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function group(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all();
    }

    /**
     * @param array<string, mixed> $values
     */
    private function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::set($key, $value);
        }
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && $user->isAdmin(), 403);
    }
}
