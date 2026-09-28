<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Webhook;
use App\Models\Tenant\WebhookLog;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Webhooks: the settings screen and the delivery behind it.
 *
 * Every method on the controller used to answer "Feature coming soon", so
 * the page listed nothing and the buttons did nothing. The dispatcher was in
 * a similar state: it queried scopes the model did not have, wrote to two
 * columns the table did not have, and never recorded a delivery anywhere,
 * although webhook_logs existed to hold them.
 */
class WebhookTest extends TenantTestCase
{
    public function test_the_page_lists_the_webhooks(): void
    {
        $this->actingAsManager();
        Webhook::create(['url' => 'https://example.com/hook', 'events' => ['task.created'], 'is_active' => true]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.webhooks.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Manager/Settings/Webhooks')
                ->has('webhooks', 1)
                ->has('availableEvents')
            );
    }

    public function test_a_webhook_is_created_and_named_after_its_host(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.webhooks.store'), [
                'url' => 'https://hooks.example.com/incoming',
                'events' => ['task.created', 'invoice.paid'],
            ])
            ->assertRedirect();

        $webhook = Webhook::sole();
        $this->assertSame('hooks.example.com', $webhook->name);
        $this->assertSame(['task.created', 'invoice.paid'], $webhook->events);
        $this->assertTrue($webhook->is_active);
    }

    public function test_an_event_nobody_publishes_is_refused(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.webhooks.store'), [
                'url' => 'https://example.com/hook',
                'events' => ['task.exploded'],
            ])
            ->assertSessionHasErrors('events.0');
    }

    public function test_only_subscribers_of_an_event_are_delivered_to(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $subscriber = Webhook::create(['url' => 'https://a.example.com', 'events' => ['invoice.paid'], 'is_active' => true]);
        Webhook::create(['url' => 'https://b.example.com', 'events' => ['task.created'], 'is_active' => true]);
        Webhook::create(['url' => 'https://c.example.com', 'events' => ['invoice.paid'], 'is_active' => false]);

        app(WebhookDispatcherService::class)->dispatch('invoice.paid', ['id' => 7]);

        Http::assertSentCount(1);
        $this->assertSame(1, WebhookLog::count());
        $this->assertSame($subscriber->id, WebhookLog::sole()->webhook_id);
    }

    public function test_a_delivery_is_recorded_and_the_failure_count_resets(): void
    {
        Http::fake(['*' => Http::response('thanks', 200)]);

        $webhook = Webhook::create([
            'url' => 'https://example.com/hook',
            'events' => ['task.created'],
            'is_active' => true,
            'failure_count' => 4,
        ]);

        $log = app(WebhookDispatcherService::class)->deliver($webhook, 'task.created', []);

        $this->assertTrue($log->success);
        $this->assertSame(200, $log->response_status);
        $this->assertSame(0, $webhook->fresh()->failure_count);
        $this->assertNotNull($webhook->fresh()->last_triggered_at);
    }

    public function test_the_tenth_failure_in_a_row_switches_the_webhook_off(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $webhook = Webhook::create([
            'url' => 'https://example.com/hook',
            'events' => ['task.created'],
            'is_active' => true,
            'failure_count' => 9,
        ]);

        app(WebhookDispatcherService::class)->deliver($webhook, 'task.created', []);

        $this->assertFalse($webhook->fresh()->is_active);
        $this->assertFalse(WebhookLog::sole()->success);
    }

    public function test_switching_one_back_on_clears_the_failures_that_stopped_it(): void
    {
        $this->actingAsManager();
        $webhook = Webhook::create([
            'url' => 'https://example.com/hook',
            'events' => ['task.created'],
            'is_active' => false,
            'failure_count' => 10,
        ]);

        $this->withoutTenantMiddleware()
            ->patch(route('tenant.manager.webhooks.update', $webhook->id), ['is_active' => true])
            ->assertRedirect();

        $this->assertTrue($webhook->fresh()->is_active);
        $this->assertSame(0, $webhook->fresh()->failure_count);
    }

    public function test_a_webhook_is_deleted(): void
    {
        $this->actingAsManager();
        $webhook = Webhook::create(['url' => 'https://example.com/hook', 'events' => ['task.created']]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.webhooks.destroy', $webhook->id))
            ->assertRedirect();

        $this->assertSame(0, Webhook::count());
    }
}
