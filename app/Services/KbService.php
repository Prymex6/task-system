<?php

namespace App\Services;

use App\Models\Tenant\KbArticle;
use App\Models\Tenant\KbCategory;

class KbService
{
    public static function search(string $query, bool $publicOnly = false): array
    {
        $q = KbArticle::where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            })
            ->with('category')
            ->orderByDesc('views');

        if ($publicOnly) {
            $q->whereHas('category', fn ($c) => $c->where('is_public', true));
        }

        return $q->limit(20)->get()->toArray();
    }

    public static function incrementViews(KbArticle $article): void
    {
        $article->increment('views');
    }

    public static function publish(KbArticle $article): void
    {
        $article->update(['is_published' => true, 'published_at' => now()]);
    }

    public static function unpublish(KbArticle $article): void
    {
        $article->update(['is_published' => false]);
    }

    public static function getCategoryTree(): array
    {
        return KbCategory::orderBy('order')
            ->with(['articles' => fn ($q) => $q->where('is_published', true)->select('id', 'kb_category_id', 'title', 'views')])
            ->get()
            ->toArray();
    }
}
