<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
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




describe('Employees API Routes', function () {
    it('requires authentication to access employees list', function () {
        $response = $this->getJson('/api/v1/employees');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated admin to list employees', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        // Create some employees
        $user1 = User::factory()->create();
        $user1->assignRole('user');
        Employee::create([
            'user_id' => $user1->id,
            'employee_no' => 'EMP001',
            'status' => 'active',
        ]);

        $user2 = User::factory()->create();
        $user2->assignRole('user');
        Employee::create([
            'user_id' => $user2->id,
            'employee_no' => 'EMP002',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'employee_no',
                        'user' => [
                            'uuid',
                            'first_name',
                            'last_name',
                            'email',
                        ],
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('allows authenticated admin to create an employee', function () {
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

        $position = Position::create([
            'department_id' => $department->id,
            'title' => 'Software Developer',
        ]);

        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'employee_no' => 'EMP003',
            'company_uuid' => $company->uuid,
            'department_uuid' => $department->uuid,
            'position_uuid' => $position->uuid,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'employee_no',
                    'user' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'employee_no' => 'EMP003',
                    'user' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john.doe@example.com',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('employees', [
            'employee_no' => 'EMP003',
        ]);
    });

    it('validates required fields when creating employee', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $response = $this->postJson('/api/v1/employees', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'employee_no']);
    });

    it('allows authenticated admin to view a specific employee', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $user = User::factory()->create();
        $user->assignRole('user');
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP004',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/employees/{$employee->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'employee_no',
                    'user',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $employee->uuid,
                    'employee_no' => 'EMP004',
                ],
            ]);
    });

    it('returns 404 when employee not found', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $response = $this->getJson('/api/v1/employees/non-existent-uuid');

        $response->assertStatus(404);
    });

    it('allows authenticated admin to update an employee', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);
        $user->assignRole('user');
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP005',
            'status' => 'active',
        ]);

        $updateData = [
            'first_name' => 'Jane Updated',
            'last_name' => 'Smith Updated',
            'employee_no' => 'EMP005-UPDATED',
            'status' => 'inactive',
        ];

        $response = $this->putJson("/api/v1/employees/{$employee->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'employee_no' => 'EMP005-UPDATED',
                    'user' => [
                        'first_name' => 'Jane Updated',
                        'last_name' => 'Smith Updated',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane Updated',
            'last_name' => 'Smith Updated',
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employee_no' => 'EMP005-UPDATED',
            'status' => 'inactive',
        ]);
    });

    it('allows authenticated admin to delete an employee', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $user = User::factory()->create();
        $user->assignRole('user');
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP006',
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/employees/{$employee->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);
    });
});
