<?php

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});




describe('Leave Requests API Routes', function () {
    it('requires authentication to access leave requests list', function () {
        $response = $this->getJson('/api/v1/leave-requests');
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list leave requests', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'status' => 'pending',
        ]);
        
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'status' => 'approved',
        ]);
        
        $response = $this->getJson('/api/v1/leave-requests');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'leave_type',
                        'start_date',
                        'end_date',
                        'status',
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

    it('allows authenticated user to create a leave request', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $user = $userData['user'];
        $employee = $userData['employee'];
        loginUser($user);
        
        $leaveRequestData = [
            'employee_uuid' => $employee->uuid,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
        ];
        
        $response = $this->postJson('/api/v1/leave-requests', $leaveRequestData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'leave_type' => 'vacation',
                    'status' => 'pending',
                ],
            ]);
        
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'status' => 'pending',
        ]);
    });

    it('validates required fields when creating leave request', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        loginUser($userData['user']);
        
        $response = $this->postJson('/api/v1/leave-requests', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_uuid', 'leave_type', 'start_date', 'end_date']);
    });

    it('allows authenticated user to view a specific leave request', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'status' => 'pending',
        ]);
        
        $response = $this->getJson("/api/v1/leave-requests/{$leaveRequest->uuid}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $leaveRequest->uuid,
                    'leave_type' => 'vacation',
                ],
            ]);
    });

    it('allows authenticated admin to approve a leave request', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'status' => 'pending',
        ]);
        
        $response = $this->postJson("/api/v1/leave-requests/{$leaveRequest->uuid}/approve");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                ],
            ]);
        
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
        ]);
    });

    it('allows authenticated admin to reject a leave request', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'status' => 'pending',
        ]);
        
        $response = $this->postJson("/api/v1/leave-requests/{$leaveRequest->uuid}/reject");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'rejected',
                ],
            ]);
        
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
        ]);
    });

    it('allows authenticated user to delete a leave request', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);
        
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'vacation',
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'status' => 'pending',
        ]);
        
        $response = $this->deleteJson("/api/v1/leave-requests/{$leaveRequest->uuid}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        $this->assertDatabaseMissing('leave_requests', [
            'id' => $leaveRequest->id,
        ]);
    });

    it('returns 404 when leave request not found', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);
        
        $response = $this->getJson('/api/v1/leave-requests/non-existent-uuid');
        
        $response->assertStatus(404);
    });
});
