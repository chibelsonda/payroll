<?php

use App\Models\Employee;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});




describe('Loans API Routes', function () {
    it('requires authentication to access loans list', function () {
        $response = $this->getJson('/api/v1/loans');
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list loans', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        Loan::create([
            'employee_id' => $employee->id,
            'amount' => 10000.00,
            'balance' => 10000.00,
            'start_date' => now(),
        ]);
        
        $response = $this->getJson('/api/v1/loans');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'amount',
                        'balance',
                        'start_date',
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

    it('allows authenticated user to create a loan', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $user = $userData['user'];
        $employee = $userData['employee'];
        loginUser($user);
        
        $loanData = [
            'employee_uuid' => $employee->uuid,
            'amount' => 15000.00,
            'balance' => 15000.00,
            'start_date' => now()->format('Y-m-d'),
        ];
        
        $response = $this->postJson('/api/v1/loans', $loanData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'amount',
                    'balance',
                    'start_date',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseHas('loans', [
            'employee_id' => $employee->id,
            'amount' => 15000.00,
            'balance' => 15000.00,
        ]);
    });

    it('validates required fields when creating loan', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        loginUser($userData['user']);
        
        $response = $this->postJson('/api/v1/loans', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_uuid', 'amount', 'balance', 'start_date']);
    });

    it('allows authenticated user to view a specific loan', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $loan = Loan::create([
            'employee_id' => $employee->id,
            'amount' => 20000.00,
            'balance' => 20000.00,
            'start_date' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/loans/{$loan->uuid}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'amount',
                    'balance',
                    'start_date',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $loan->uuid,
                ],
            ]);
    });

    it('allows authenticated user to update a loan', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $loan = Loan::create([
            'employee_id' => $employee->id,
            'amount' => 25000.00,
            'balance' => 25000.00,
            'start_date' => now(),
        ]);
        
        $updateData = [
            'employee_uuid' => $employee->uuid,
            'amount' => 25000.00,
            'balance' => 20000.00,
            'start_date' => now()->format('Y-m-d'),
        ];
        
        $response = $this->putJson("/api/v1/loans/{$loan->uuid}", $updateData);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'balance' => 20000.00,
        ]);
    });

    it('allows authenticated user to delete a loan', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $loan = Loan::create([
            'employee_id' => $employee->id,
            'amount' => 30000.00,
            'balance' => 30000.00,
            'start_date' => now(),
        ]);
        
        $response = $this->deleteJson("/api/v1/loans/{$loan->uuid}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseMissing('loans', [
            'id' => $loan->id,
        ]);
    });

    it('allows authenticated user to view loan payments', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $loan = Loan::create([
            'employee_id' => $employee->id,
            'amount' => 40000.00,
            'balance' => 40000.00,
            'start_date' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/loans/{$loan->uuid}/payments");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('returns 404 when loan not found', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $response = $this->getJson('/api/v1/loans/non-existent-uuid');
        
        $response->assertStatus(404);
    });
});
