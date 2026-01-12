<?php

use App\Models\Employee;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});





describe('Salaries API Routes (Admin Only)', function () {
    it('requires authentication to access salaries list', function () {
        $response = $this->getJson('/api/v1/salaries');
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('requires admin role to access salaries list', function () {
        $employee = createAuthenticatedEmployee();
        loginUser($employee);
        
        $response = $this->getJson('/api/v1/salaries');
        
        $response->assertStatus(403);
    });

    it('allows authenticated admin to list salaries', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        Salary::create([
            'employee_id' => $employee->id,
            'amount' => 50000.00,
            'effective_from' => now(),
        ]);
        
        $response = $this->getJson('/api/v1/salaries');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'amount',
                        'effective_from',
                        'employee',
                    ],
                ],
                'meta' => [
                    'pagination',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('allows authenticated admin to create a salary', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $salaryData = [
            'employee_uuid' => $employee->uuid,
            'amount' => 60000.00,
            'effective_from' => now()->format('Y-m-d'),
        ];
        
        $response = $this->postJson('/api/v1/salaries', $salaryData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'amount',
                    'effective_from',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseHas('salaries', [
            'employee_id' => $employee->id,
            'amount' => 60000.00,
        ]);
    });

    it('validates required fields when creating salary', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $response = $this->postJson('/api/v1/salaries', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_uuid', 'amount', 'effective_from']);
    });

    it('requires admin role to create salary', function () {
        $employee = createAuthenticatedEmployee();
        loginUser($employee);
        
        $salaryData = [
            'employee_uuid' => 'test-uuid',
            'amount' => 60000.00,
            'effective_from' => now()->format('Y-m-d'),
        ];
        
        $response = $this->postJson('/api/v1/salaries', $salaryData);
        
        $response->assertStatus(403);
    });

    it('allows authenticated admin to view a specific salary', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $salary = Salary::create([
            'employee_id' => $employee->id,
            'amount' => 70000.00,
            'effective_from' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/salaries/{$salary->uuid}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'amount',
                    'effective_from',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $salary->uuid,
                ],
            ]);
    });

    it('allows authenticated admin to update a salary', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $salary = Salary::create([
            'employee_id' => $employee->id,
            'amount' => 75000.00,
            'effective_from' => now(),
        ]);
        
        $updateData = [
            'amount' => 80000.00,
            'effective_from' => now()->format('Y-m-d'),
        ];
        
        $response = $this->putJson("/api/v1/salaries/{$salary->uuid}", $updateData);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseHas('salaries', [
            'id' => $salary->id,
            'amount' => 80000.00,
        ]);
    });

    it('allows authenticated admin to delete a salary', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $salary = Salary::create([
            'employee_id' => $employee->id,
            'amount' => 90000.00,
            'effective_from' => now(),
        ]);
        
        $response = $this->deleteJson("/api/v1/salaries/{$salary->uuid}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseMissing('salaries', [
            'id' => $salary->id,
        ]);
    });

    it('allows authenticated admin to view employee salary history', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        Salary::create([
            'employee_id' => $employee->id,
            'amount' => 50000.00,
            'effective_from' => now()->subMonths(3),
        ]);
        
        Salary::create([
            'employee_id' => $employee->id,
            'amount' => 55000.00,
            'effective_from' => now()->subMonths(1),
        ]);
        
        $response = $this->getJson("/api/v1/employees/{$employee->uuid}/salaries");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'amount',
                        'effective_from',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('returns 404 when salary not found', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $response = $this->getJson('/api/v1/salaries/non-existent-uuid');
        
        $response->assertStatus(404);
    });
});
