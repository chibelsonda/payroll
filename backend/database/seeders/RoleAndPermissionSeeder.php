<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'manage users',
            'view users',
            'create users',
            'update users',
            'delete users',

            // Employee management
            'manage employees',
            'view employees',
            'create employees',
            'update employees',
            'delete employees',

            // Attendance management
            'manage attendance',
            'view attendance',
            'create attendance',
            'update attendance',
            'delete attendance',
            'approve attendance',

            // Payroll management
            'manage payroll',
            'view payroll',
            'create payroll',
            'update payroll',
            'delete payroll',
            'process payroll',
            'export payroll',

            // Leave management
            'manage leaves',
            'view leaves',
            'create leaves',
            'update leaves',
            'delete leaves',
            'approve leaves',

            // Salary management
            'view salary',
            'update salary',

            // Reports
            'view reports',
            'export reports',

            // Company settings
            'manage company settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // COMPANY ROLES (scoped by company_id)
        // These roles will have company_id set when assigned

        // Owner role - all permissions
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo(Permission::all());

        // Admin role - manage users, employees, attendance, payroll
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([
            'manage users',
            'view users',
            'create users',
            'update users',
            'delete users',
            'manage employees',
            'view employees',
            'create employees',
            'update employees',
            'delete employees',
            'manage attendance',
            'view attendance',
            'create attendance',
            'update attendance',
            'delete attendance',
            'approve attendance',
            'manage payroll',
            'view payroll',
            'create payroll',
            'update payroll',
            'delete payroll',
            'process payroll',
            'export payroll',
            'manage leaves',
            'view leaves',
            'create leaves',
            'update leaves',
            'delete leaves',
            'approve leaves',
            'view salary',
            'update salary',
            'view reports',
            'export reports',
            'manage company settings',
        ]);

        // HR role - manage employees, attendance, leaves
        $hrRole = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hrRole->givePermissionTo([
            'manage employees',
            'view employees',
            'create employees',
            'update employees',
            'delete employees',
            'manage attendance',
            'view attendance',
            'create attendance',
            'update attendance',
            'delete attendance',
            'approve attendance',
            'manage leaves',
            'view leaves',
            'create leaves',
            'update leaves',
            'delete leaves',
            'approve leaves',
            'view reports',
        ]);

        // Payroll role - run payroll, view salary, export reports
        $payrollRole = Role::firstOrCreate(['name' => 'payroll', 'guard_name' => 'web']);
        $payrollRole->givePermissionTo([
            'manage payroll',
            'view payroll',
            'create payroll',
            'update payroll',
            'process payroll',
            'export payroll',
            'view salary',
            'update salary',
            'view reports',
            'export reports',
        ]);

        // Employee role - view own data only
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employeeRole->givePermissionTo([
            'view employees', // Only own employee record
            'view attendance', // Only own attendance
            'create attendance', // Only own attendance logs
            'view leaves', // Only own leaves
            'create leaves', // Only own leave requests
            'view salary', // Only own salary
        ]);

        // PLATFORM ROLES (global, no company_id)
        // These roles should NOT have company_id when assigned
        // They are assigned manually via database or admin panel only

        // Super Admin - all permissions across all companies
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Support - view access for troubleshooting
        $supportRole = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);
        $supportRole->givePermissionTo([
            'view users',
            'view employees',
            'view attendance',
            'view payroll',
            'view leaves',
            'view reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
