<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tag;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $tags = Tag::withCount('tasks')->orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Settings/Tags', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
            'color' => 'required|string|max:7',
        ]);

        $tag = Tag::create($request->only(['name', 'color']));

        AuditService::log('tag_created', $tag, [], $tag->toArray());

        return back()->with('success', __('messages.tag_created'));
    }

    public function update(Request $request, Tag $tag)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,' . $tag->id,
            'color' => 'required|string|max:7',
        ]);

        $old = $tag->toArray();
        $tag->update($request->only(['name', 'color']));

        AuditService::log('tag_updated', $tag, $old, $tag->fresh()->toArray());

        return back()->with('success', __('messages.tag_updated'));
    }

    public function destroy(Tag $tag)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $tag->tasks()->detach();
        $tag->delete();

        return back()->with('success', __('messages.tag_deleted'));
    }
}
