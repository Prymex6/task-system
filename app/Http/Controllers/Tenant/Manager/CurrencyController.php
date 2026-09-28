<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Currencies the workspace invoices in, and what they are worth against the base one.
 */
class CurrencyController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/Currencies', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $validated['code'] = strtoupper($validated['code']);

        $record = Currency::create($validated);

        if ($record->is_default) {
            $this->makeSoleDefault($record);
        }

        AuditService::log('currency_created', $record, [], $record->only(['code', 'rate_to_base', 'is_default', 'is_active']));

        return back()->with('success', __('messages.currency_created'));
    }

    public function update(Request $request, Currency $currency)
    {
        $this->admin();

        $validated = $request->validate($this->rules($currency->id));

        $old = $currency->only(['code', 'rate_to_base', 'is_default', 'is_active']);
        $currency->update($validated);

        if ($currency->is_default) {
            $this->makeSoleDefault($currency);
        }

        AuditService::log('currency_updated', $currency, $old, $currency->fresh()->only(['code', 'rate_to_base', 'is_default', 'is_active']));

        return back()->with('success', __('messages.currency_updated'));
    }

    public function destroy(Currency $currency)
    {
        $this->admin();

        if ($currency->is_default) {
            return back()->with('error', __('messages.cannot_delete_default_currency'));
        }

        $currency->delete();

        AuditService::log('currency_deleted', $currency, ['code' => $currency->code], []);

        return back()->with('success', __('messages.currency_deleted'));
    }

    /**
     * The base currency is the one every rate is quoted against, so there is
     * exactly one and its own rate is 1.
     */
    private function makeSoleDefault(Currency $currency): void
    {
        Currency::where('id', '!=', $currency->id)->where('is_default', true)->update(['is_default' => false]);
        $currency->update(['rate_to_base' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code,' . $ignore,
            'symbol' => 'required|string|max:5',
            'name' => 'required|string|max:50',
            'rate_to_base' => 'required|numeric|min:0.000001',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
