<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'employees.view',
        'employees.manage',
        'departments.manage',
        'positions.manage',
        'leave.request',
        'leave.approve',
        'attendance.manage',
        'payroll.manage',
        'inventory.manage',
        'pos.sync',
        'reports.view',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => self::PERMISSIONS,
        'hr' => [
            'employees.view',
            'employees.manage',
            'departments.manage',
            'positions.manage',
            'leave.request',
            'leave.approve',
            'attendance.manage',
            'payroll.manage',
            'reports.view',
        ],
        'manager' => [
            'employees.view',
            'leave.request',
            'leave.approve',
            'attendance.manage',
            'reports.view',
        ],
        'employee' => [
            'employees.view',
            'leave.request',
            'attendance.manage',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
