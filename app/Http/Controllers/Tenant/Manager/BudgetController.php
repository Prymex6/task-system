<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function show(Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $summary = BudgetService::getSummary($project);

        return Inertia::render('Tenant/Manager/Projects/Budget', [
            'project' => $project->load('members.user'),
            'summary' => $summary,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'date' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        BudgetService::addEntry($project, [
            ...$request->only(['type', 'amount', 'description', 'date', 'category']),
            'created_by' => $user->id,
        ]);

        return back()->with('success', __('messages.budget_entry_created'));
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        $request->validate([
            'budget' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $project->update($request->only(['budget', 'hourly_rate']));

        return back()->with('success', __('messages.project_budget_updated'));
    }
}
