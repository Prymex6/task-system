<?php

namespace Tests\Feature;

use Tests\TenantTestCase;

class ExampleTest extends TenantTestCase
{
    /**
     * Verify the login page is accessible.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.login'));

        $response->assertStatus(200);
    }
}
