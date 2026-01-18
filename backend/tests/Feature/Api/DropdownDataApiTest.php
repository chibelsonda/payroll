<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});



describe('Dropdown Data API Routes', function () {
    it('requires authentication to access companies list', function () {
        $response = $this->getJson('/api/v1/companies');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list companies', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        Company::create([
            'name' => 'Company 1',
            'registration_no' => 'REG001',
        ]);

        Company::create([
            'name' => 'Company 2',
            'registration_no' => 'REG002',
        ]);

        $response = $this->getJson('/api/v1/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'registration_no',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('requires authentication to access departments list', function () {
        $response = $this->getJson('/api/v1/departments');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list departments', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $company = Company::create([
            'name' => 'Test Company',
            'registration_no' => 'REG001',
        ]);

        Department::create([
            'company_id' => $company->id,
            'name' => 'IT Department',
        ]);

        Department::create([
            'company_id' => $company->id,
            'name' => 'HR Department',
        ]);

        $response = $this->getJson('/api/v1/departments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'company',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('requires authentication to access positions list', function () {
        $response = $this->getJson('/api/v1/positions');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list positions', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $company = Company::create([
            'name' => 'Test Company',
            'registration_no' => 'REG001',
        ]);

        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'IT Department',
        ]);

        Position::create([
            'department_id' => $department->id,
            'title' => 'Software Developer',
        ]);

        Position::create([
            'department_id' => $department->id,
            'title' => 'Senior Developer',
        ]);

        $response = $this->getJson('/api/v1/positions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'title',
                        'department',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });
});
