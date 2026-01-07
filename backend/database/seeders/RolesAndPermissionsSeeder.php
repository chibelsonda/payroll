<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage users',
            'create posts',
            'edit posts',
            'delete posts',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Staff gets create and edit posts
        $staffRole->givePermissionTo([
            'create posts',
            'edit posts',
        ]);

        // User gets no management permissions (already default, but explicit for clarity)
        // $userRole has no permissions assigned

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Roles: admin, staff, user');
        $this->command->info('Permissions: manage users, create posts, edit posts, delete posts');
    }
}
