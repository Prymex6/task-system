<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseCategory;
use App\Models\Tenant\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        $query = Expense::with(['category', 'user', 'project']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();
        $totalAmount = $query->sum('amount');

        return Inertia::render('Tenant/Manager/Finance/Expenses/Index', [
            'expenses' => $expenses,
            'totalAmount' => (float) $totalAmount,
            'categories' => ExpenseCategory::orderBy('name')->get(['id', 'name']),
            'projects' => Project::where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['category_id', 'project_id', 'date_from', 'date_to']),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:5',
            'expense_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'is_billable' => 'boolean',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $validated['user_id'] = $user->id;
        Expense::create($validated);

        return back()->with('success', __('messages.expense_created'));
    }

    public function update(Request $request, Expense $expense)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $expense->user_id === $user->id, 403);

        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:5',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:500',
            'is_billable' => 'boolean',
        ]);

        $expense->update($validated);

        return back()->with('success', __('messages.expense_updated'));
    }

    public function destroy(Expense $expense)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $expense->user_id === $user->id, 403);

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return back()->with('success', __('messages.expense_deleted'));
    }

    public function approve(Expense $expense)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        $expense->update([
            'is_approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', __('messages.expense_approved'));
    }

    public function reject(Expense $expense)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin(), 403);

        $expense->update([
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('success', __('messages.expense_rejected'));
    }
}
