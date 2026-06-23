<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\EmployeeInformation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $humanResources = Department::query()->firstOrCreate(
            ['name' => 'Human Resources'],
            ['description' => 'Handles recruitment and employee records.', 'is_active' => true],
        );

        $sales = Department::query()->firstOrCreate(
            ['name' => 'Sales'],
            ['description' => 'Owns business development and sales operations.', 'is_active' => true],
        );

        $adminPosition = Position::query()->firstOrCreate(
            ['name' => 'System Administrator'],
            ['description' => 'Platform-wide administrative access.', 'is_active' => true],
        );

        $managerPosition = Position::query()->firstOrCreate(
            ['name' => 'Operations Manager'],
            ['description' => 'Team leadership and approvals.', 'is_active' => true],
        );

        $staffPosition = Position::query()->firstOrCreate(
            ['name' => 'HR Specialist'],
            ['description' => 'General HR operational role.', 'is_active' => true],
        );

        $this->seedUserWithProfile(
            name: 'System Admin',
            email: 'admin@businesskit.test',
            role: 'admin',
            employeeId: 'EMP-0001',
            department: $humanResources,
            position: $adminPosition,
            firstName: 'System',
            lastName: 'Admin',
            salary: '75000.00',
        );

        $this->seedUserWithProfile(
            name: 'HR Lead',
            email: 'hr@businesskit.test',
            role: 'hr',
            employeeId: 'EMP-0002',
            department: $humanResources,
            position: $staffPosition,
            firstName: 'HR',
            lastName: 'Lead',
            salary: '52000.00',
        );

        $this->seedUserWithProfile(
            name: 'Ops Manager',
            email: 'manager@businesskit.test',
            role: 'manager',
            employeeId: 'EMP-0003',
            department: $sales,
            position: $managerPosition,
            firstName: 'Ops',
            lastName: 'Manager',
            salary: '61000.00',
        );

        $this->seedUserWithProfile(
            name: 'Employee One',
            email: 'employee@businesskit.test',
            role: 'employee',
            employeeId: 'EMP-0004',
            department: $sales,
            position: $staffPosition,
            firstName: 'Employee',
            lastName: 'One',
            salary: '32000.00',
        );

        $integrationUser = User::query()->updateOrCreate(
            ['email' => 'pos.integration@businesskit.test'],
            ['name' => 'POS Integration', 'password' => 'password'],
        );

        $integrationUser->syncRoles([]);
        $integrationUser->syncPermissions([
            Permission::findOrCreate('pos.sync', 'web'),
        ]);
    }

    private function seedUserWithProfile(
        string $name,
        string $email,
        string $role,
        string $employeeId,
        Department $department,
        Position $position,
        string $firstName,
        string $lastName,
        string $salary,
    ): void {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password'],
        );

        $user->syncRoles([Role::findOrCreate($role, 'web')]);

        EmployeeInformation::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_id' => $employeeId,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => null,
                'address' => null,
                'date_of_hire' => now()->subMonths(6)->toDateString(),
                'employment_status' => EmploymentStatus::Active,
                'salary' => $salary,
            ],
        );
    }
}
