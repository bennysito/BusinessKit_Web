<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\EmployeeInformation;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_can_clock_in_and_out_and_hours_are_calculated(): void
    {
        config()->set('hrms.attendance.start_time', '09:00');

        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');
        EmployeeInformation::factory()->create([
            'user_id' => $employeeUser->id,
            'email' => $employeeUser->email,
        ]);

        Sanctum::actingAs($employeeUser);

        Carbon::setTestNow('2026-06-24 08:55:00');

        $this->postJson('/api/attendance/clock-in', [
            'notes' => 'Starting shift',
        ])
            ->assertCreated()
            ->assertJsonPath('status', 'present');

        Carbon::setTestNow('2026-06-24 17:10:00');

        $this->postJson('/api/attendance/clock-out')
            ->assertOk()
            ->assertJsonPath('hours_worked', '8.25');
    }

    public function test_late_clock_in_marks_attendance_as_late_and_summary_returns_totals(): void
    {
        config()->set('hrms.attendance.start_time', '09:00');

        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');
        EmployeeInformation::factory()->create([
            'user_id' => $employeeUser->id,
            'email' => $employeeUser->email,
        ]);

        Sanctum::actingAs($employeeUser);

        Carbon::setTestNow('2026-06-24 09:30:00');
        $this->postJson('/api/attendance/clock-in')->assertCreated();

        Carbon::setTestNow('2026-06-24 18:00:00');
        $this->postJson('/api/attendance/clock-out')->assertOk();

        $this->getJson('/api/attendance/summary?group_by=daily')
            ->assertOk()
            ->assertJsonPath('data.0.period', '2026-06-24')
            ->assertJsonPath('data.0.status_counts.late', 1)
            ->assertJsonPath('data.0.hours_worked', '8.50');
    }

    public function test_employee_only_sees_their_own_attendance_records(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('employee');
        $viewerEmployee = EmployeeInformation::factory()->create([
            'user_id' => $viewer->id,
            'email' => $viewer->email,
        ]);

        $otherUser = User::factory()->create();
        $otherEmployee = EmployeeInformation::factory()->create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
        ]);

        Attendance::factory()->create([
            'employee_id' => $viewerEmployee->id,
            'date' => '2026-06-24',
        ]);
        Attendance::factory()->create([
            'employee_id' => $otherEmployee->id,
            'date' => '2026-06-24',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/attendance')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee.id', $viewerEmployee->id);
    }
}
