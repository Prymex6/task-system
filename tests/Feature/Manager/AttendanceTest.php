<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Attendance;
use Tests\TenantTestCase;

class AttendanceTest extends TenantTestCase
{
    public function test_manager_can_clock_in(): void
    {
        $user = $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.hr.clock-in'));

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance', [
            'user_id' => $user->id,
            'clock_out' => null,
        ]);
    }

    public function test_clock_in_closes_previous_open_entry(): void
    {
        $user = $this->actingAsManager();

        // First clock-in
        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        // Second clock-in should close the first
        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.hr.clock-in'));

        // The old open entry should now be closed
        $this->assertDatabaseMissing('attendance', [
            'user_id' => $user->id,
            'clock_out' => null,
            'clock_in' => now()->subHours(2)->toDateTimeString(),
        ]);
    }

    public function test_manager_can_clock_out(): void
    {
        $user = $this->actingAsManager();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'clock_out' => null,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.hr.clock-out'));

        $response->assertRedirect();

        $this->assertDatabaseMissing('attendance', [
            'user_id' => $user->id,
            'clock_out' => null,
        ]);
    }

    public function test_clock_out_without_clock_in_redirects_back(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.hr.clock-out'));

        $response->assertRedirect();
    }
}
