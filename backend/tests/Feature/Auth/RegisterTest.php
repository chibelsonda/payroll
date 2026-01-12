<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

// Use RefreshDatabase trait to reset database between tests
uses(RefreshDatabase::class);

// Setup roles before each test
beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'staff']);
    Role::firstOrCreate(['name' => 'user']);
});

describe('User Registration', function () {
    // Test: User can register with valid data
    it('can register a new user with valid data', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        // Assert successful registration response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                    ],
                ],
            ]);

        // Assert user is created in database
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Assert user is authenticated after registration
        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->email)->toBe('john.doe@example.com');
    });

    // Test: Registration fails with missing fields
    it('fails registration when first_name is missing', function () {
        $userData = [
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    });

    it('fails registration when last_name is missing', function () {
        $userData = [
            'first_name' => 'John',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name']);
    });

    it('fails registration when email is missing', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails registration when password is missing', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    // Test: Registration fails with invalid email
    it('fails registration with invalid email format', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    // Test: Registration fails with weak password
    it('fails registration with weak password (less than 8 characters)', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('fails registration when password confirmation does not match', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    // Test: Registration fails if email already exists
    it('fails registration if email already exists', function () {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Assert only one user with this email exists
        $this->assertDatabaseCount('users', 1);
    });

    // Additional test: Verify user has default student role
    it('assigns default student role when no role is specified', function () {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $this->postJson('/register', $userData);

        $user = User::where('email', 'john.doe@example.com')->first();
        expect($user->hasRole('user'))->toBeTrue();
    });

    // Additional test: Verify admin role assignment
    it('can register a user with admin role', function () {
        $userData = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'admin@example.com')->first();
        expect($user->hasRole('admin'))->toBeTrue();
    });


    // Additional test: No student record for admin
    it('does not create student record when registering as admin', function () {
        $userData = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ];

        $response = $this->postJson('/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'admin@example.com')->first();
        expect($user->student)->toBeNull();
    });
});
