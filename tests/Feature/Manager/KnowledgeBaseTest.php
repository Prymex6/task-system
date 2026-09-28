<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\KbArticle;
use Tests\TenantTestCase;

class KnowledgeBaseTest extends TenantTestCase
{
    private function makeArticle(array $attrs = []): KbArticle
    {
        return KbArticle::create(array_merge([
            'title' => 'Artykuł testowy',
            'slug' => 'artykul-testowy-' . uniqid(),
            'content' => 'Treść artykułu testowego.',
            'is_published' => true,
            'sort_order' => 0,
            'visibility' => 'internal',
        ], $attrs));
    }

    public function test_kb_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.kb.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/KnowledgeBase/Index'));
    }

    public function test_manager_can_create_kb_article(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.kb.store'), [
                'title' => 'Jak złożyć reklamację',
                'slug' => 'jak-zlozyc-reklamacje',
                'content' => 'Treść artykułu o reklamacjach...',
                'category' => 'Reklamacje',
                'is_published' => true,
                'sort_order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_articles', [
            'title' => 'Jak złożyć reklamację',
            'slug' => 'jak-zlozyc-reklamacje',
        ]);
    }

    public function test_manager_can_update_kb_article(): void
    {
        $this->actingAsManager();
        $article = $this->makeArticle();

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.kb.update', $article), [
                'title' => 'Zaktualizowany tytuł',
                'slug' => $article->slug,
                'content' => 'Nowa treść artykułu.',
                'is_published' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'title' => 'Zaktualizowany tytuł']);
    }

    public function test_manager_can_delete_kb_article(): void
    {
        $this->actingAsManager();
        $article = $this->makeArticle();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.kb.destroy', $article));

        $response->assertRedirect();
        $this->assertDatabaseMissing('kb_articles', ['id' => $article->id]);
    }

    public function test_manager_can_toggle_publish_status(): void
    {
        $this->actingAsManager();
        $article = $this->makeArticle(['is_published' => true]);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.kb.update', $article), [
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'is_published' => false,
                'sort_order' => 0,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'is_published' => false]);
    }

    public function test_kb_index_lists_articles_with_pagination(): void
    {
        $this->actingAsManager();
        for ($i = 0; $i < 5; $i++) {
            $this->makeArticle(['title' => "Artykuł $i"]);
        }

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.kb.index'));

        $response->assertInertia(fn ($page) => $page->has('articles'));
    }
}
