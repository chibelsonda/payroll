<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});




describe('Attendance API Routes', function () {
    it('requires authentication to access attendance list', function () {
        $response = $this->getJson('/api/v1/attendances');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('allows authenticated user to list attendance records', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
            'time_in' => '09:00:00',
            'time_out' => '17:00:00',
            'hours_worked' => 8.0,
        ]);

        $response = $this->getJson('/api/v1/attendances');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'uuid',
                        'date',
                        'time_in',
                        'time_out',
                        'hours_worked',
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

    it('allows authenticated user to create an attendance record', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $user = $userData['user'];
        $employee = $userData['employee'];
        loginUser($user);

        $attendanceData = [
            'employee_uuid' => $employee->uuid,
            'date' => now()->format('Y-m-d'),
            'time_in' => '09:00:00',
            'time_out' => '17:00:00',
            'hours_worked' => 8.0,
        ];

        $response = $this->postJson('/api/v1/attendances', $attendanceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'date',
                    'time_in',
                    'time_out',
                    'hours_worked',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
        ]);
    });

    it('validates required fields when creating attendance', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        loginUser($userData['user']);

        $response = $this->postJson('/api/v1/attendances', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_uuid', 'date']);
    });

    it('allows authenticated user to view a specific attendance record', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
            'time_in' => '09:00:00',
            'time_out' => '17:00:00',
            'hours_worked' => 8.0,
        ]);

        $response = $this->getJson("/api/v1/attendances/{$attendance->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'date',
                    'time_in',
                    'time_out',
                    'hours_worked',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $attendance->uuid,
                ],
            ]);
    });

    it('allows authenticated user to update an attendance record', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
            'time_in' => '09:00:00',
            'time_out' => '17:00:00',
            'hours_worked' => 8.0,
        ]);

        $updateData = [
            'employee_uuid' => $employee->uuid,
            'date' => now()->format('Y-m-d'),
            'time_in' => '08:30:00',
            'time_out' => '17:30:00',
            'hours_worked' => 9.0,
        ];

        $response = $this->putJson("/api/v1/attendances/{$attendance->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'time_in' => '08:30:00',
            'time_out' => '17:30:00',
            'hours_worked' => 9.0,
        ]);
    });

    it('allows authenticated user to delete an attendance record', function () {
        $userData = createAuthenticatedEmployeeWithEmployee();
        $employee = $userData['employee'];
        loginUser($userData['user']);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
            'time_in' => '09:00:00',
            'time_out' => '17:00:00',
            'hours_worked' => 8.0,
        ]);

        $response = $this->deleteJson("/api/v1/attendances/{$attendance->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('attendance', [
            'id' => $attendance->id,
        ]);
    });

    it('returns 404 when attendance not found', function () {
        $admin = createAuthenticatedAdmin();
        loginUser($admin);

        $response = $this->getJson('/api/v1/attendances/non-existent-uuid');

        $response->assertStatus(404);
    });
});
