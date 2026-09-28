<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentMethod;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $methods = PaymentMethod::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Settings/PaymentMethods', [
            'methods' => $methods,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $method = PaymentMethod::create($request->only(['name', 'description', 'is_active']));

        AuditService::log('payment_method_created', $method, [], $method->toArray());

        return back()->with('success', __('messages.payment_method_created'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $old = $paymentMethod->toArray();
        $paymentMethod->update($request->only(['name', 'description', 'is_active']));

        AuditService::log('payment_method_updated', $paymentMethod, $old, $paymentMethod->fresh()->toArray());

        return back()->with('success', __('messages.payment_method_updated'));
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $paymentMethod->delete();

        return back()->with('success', __('messages.payment_method_deleted'));
    }
}
