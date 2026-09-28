<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\KbArticle;
use App\Models\Tenant\KbCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KbArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = KbArticle::with(['category', 'author']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('kb_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        $articles = $query->latest()->paginate(20)->withQueryString();
        $categories = KbCategory::withCount('articles')->orderBy('name')->get();

        return Inertia::render('Tenant/Manager/KnowledgeBase/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/KnowledgeBase/Form', [
            'categories' => KbCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'required|string',
            'kb_category_id' => 'nullable|exists:kb_categories,id',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'visibility' => 'nullable|in:public,private,clients_only,internal',
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['author_id'] = Auth::guard('tenant')->id();

        // Unikalny slug
        $baseSlug = $validated['slug'];
        $counter = 2;
        while (KbArticle::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        KbArticle::create($validated);

        return redirect()->route('tenant.manager.kb.index')
            ->with('success', __('messages.article_published'));
    }

    public function edit(KbArticle $article)
    {
        return Inertia::render('Tenant/Manager/KnowledgeBase/Form', [
            'article' => $article,
            'categories' => KbCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, KbArticle $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'required|string',
            'kb_category_id' => 'nullable|exists:kb_categories,id',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'visibility' => 'nullable|in:public,private,clients_only,internal',
        ]);

        if (!empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $article->update($validated);

        return back()->with('success', __('messages.article_updated'));
    }

    public function destroy(KbArticle $article)
    {
        $article->delete();

        return back()->with('success', __('messages.article_deleted'));
    }
}
