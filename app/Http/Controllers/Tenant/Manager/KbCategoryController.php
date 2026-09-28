<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\KbCategory;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Sections the knowledge base is divided into.
 */
class KbCategoryController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Settings/KbCategories', [
            'categories' => KbCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Both are edited in a modal on the list, so there is no separate page.
     */
    public function create()
    {
        return redirect()->route('tenant.manager.kb.categories.index');
    }

    public function edit(KbCategory $category)
    {
        return redirect()->route('tenant.manager.kb.categories.index');
    }

    public function store(Request $request)
    {
        $this->admin();

        $validated = $request->validate($this->rules());

        $record = KbCategory::create($validated);

        AuditService::log('kb_category_created', $record, [], $record->only(['name', 'is_public']));

        return back()->with('success', __('messages.category_created'));
    }

    public function update(Request $request, KbCategory $category)
    {
        $this->admin();

        $validated = $request->validate($this->rules($category->id));

        $old = $category->only(['name', 'is_public']);
        $category->update($validated);

        AuditService::log('kb_category_updated', $category, $old, $category->fresh()->only(['name', 'is_public']));

        return back()->with('success', __('messages.category_updated'));
    }

    public function destroy(KbCategory $category)
    {
        $this->admin();

        $category->delete();

        AuditService::log('kb_category_deleted', $category, ['name' => $category->name], []);

        return back()->with('success', __('messages.category_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignore = null): array
    {
        return [
            'name' => 'required|string|max:100|unique:kb_categories,name,' . $ignore,
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
        ];
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
