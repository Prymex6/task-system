<?php

namespace Tests\Feature\Landlord;

use Tests\LandlordTestCase;

class StatisticsTest extends LandlordTestCase
{
    public function test_statistics_page_is_accessible(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('landlord.statistics.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Landlord/Statistics/Index')
            ->has('stats')
            ->has('planDistribution')
            ->has('growthData')
            ->has('recentTenants')
        );
    }

    public function test_statistics_page_requires_authentication(): void
    {
        $response = $this->get(route('landlord.statistics.index'));

        $response->assertRedirect();
    }

    public function test_stats_contain_expected_keys(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('landlord.statistics.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_tenants', fn ($v) => is_int($v) || is_numeric($v))
        );
    }
}
