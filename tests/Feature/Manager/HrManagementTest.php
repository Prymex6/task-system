<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Announcement;
use App\Models\Tenant\DepartmentHr;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Position;
use Tests\TenantTestCase;

class HrManagementTest extends TenantTestCase
{
    public function test_manager_can_create_holiday(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.hr.holidays.store'), [
            'name' => 'Święto Niepodległości',
            'date' => '2026-11-11',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('holidays', ['name' => 'Święto Niepodległości']);
    }

    public function test_manager_can_delete_holiday(): void
    {
        $this->actingAsManager();
        $holiday = Holiday::create(['name' => 'Test', 'date' => '2026-12-24']);

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.hr.holidays.destroy', $holiday));

        $response->assertRedirect();
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_manager_can_create_leave_type(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.leave-types.store'), [
            'name' => 'Urlop wypoczynkowy',
            'days_per_year' => 26,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_types', ['name' => 'Urlop wypoczynkowy', 'days_per_year' => 26]);
    }

    public function test_manager_can_update_leave_type(): void
    {
        $this->actingAsManager();
        $type = LeaveType::create(['name' => 'Old']);

        $response = $this->withoutTenantMiddleware()->put(route('tenant.manager.leave-types.update', $type), [
            'name' => 'Urlop na żądanie',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'name' => 'Urlop na żądanie']);
    }

    public function test_manager_can_create_department(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.departments.store'), [
            'name' => 'Dział IT',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments_hr', ['name' => 'Dział IT']);
    }

    public function test_manager_can_create_position(): void
    {
        $this->actingAsManager();
        $dept = DepartmentHr::create(['name' => 'HR']);

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.positions.store'), [
            'name' => 'Specjalista ds. rekrutacji',
            'department_hr_id' => $dept->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('positions', ['name' => 'Specjalista ds. rekrutacji', 'department_hr_id' => $dept->id]);
    }

    public function test_manager_can_delete_position(): void
    {
        $this->actingAsManager();
        $position = Position::create(['name' => 'Temp']);

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.positions.destroy', $position));

        $response->assertRedirect();
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_manager_can_publish_announcement(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()->post(route('tenant.manager.announcements.store'), [
            'title' => 'Nowe biuro',
            'body' => 'Od poniedziałku pracujemy z nowego biura.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('announcements', ['title' => 'Nowe biuro']);
    }

    public function test_manager_can_delete_announcement(): void
    {
        $manager = $this->actingAsManager();
        $announcement = Announcement::create([
            'created_by' => $manager->id,
            'title' => 'Temp',
            'body' => 'Do usunięcia',
            'target' => 'all',
        ]);

        $response = $this->withoutTenantMiddleware()->delete(route('tenant.manager.announcements.destroy', $announcement));

        $response->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }
}
