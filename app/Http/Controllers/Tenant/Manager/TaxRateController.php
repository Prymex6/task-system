<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TaxRate;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * VAT rates an invoice line can be charged at.
 */
class TaxRateController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/TaxRates', [
            'rates' => TaxRate::orderBy('rate')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = TaxRate::create($validated);

        if ($record->is_default) {
            $this->makeSoleDefault($record);
        }

        AuditService::log('tax_rate_created', $record, [], $record->only(['name', 'rate', 'is_default', 'is_active']));

        return back()->with('success', __('messages.tax_rate_created'));
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $old = $taxRate->only(['name', 'rate', 'is_default', 'is_active']);
        $taxRate->update($validated);

        if ($taxRate->is_default) {
            $this->makeSoleDefault($taxRate);
        }

        AuditService::log('tax_rate_updated', $taxRate, $old, $taxRate->fresh()->only(['name', 'rate', 'is_default', 'is_active']));

        return back()->with('success', __('messages.tax_rate_updated'));
    }

    public function destroy(TaxRate $taxRate)
    {
        $this->admin();

        $taxRate->delete();

        AuditService::log('tax_rate_deleted', $taxRate, ['name' => $taxRate->name], []);

        return back()->with('success', __('messages.tax_rate_deleted'));
    }

    /**
     * One rate is the one new invoice lines start on.
     */
    private function makeSoleDefault(TaxRate $rate): void
    {
        TaxRate::where('id', '!=', $rate->id)->where('is_default', true)->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'country' => 'nullable|string|size:2',
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
