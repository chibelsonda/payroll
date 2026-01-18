<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Use RefreshDatabase trait to reset database between tests
uses(RefreshDatabase::class);

// Setup roles before each test
beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'staff']);
    Role::firstOrCreate(['name' => 'user']);
});

describe('User Login', function () {
    // Test: User can login with correct credentials
    it('can login with correct credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        // Assert successful login response
        $response->assertStatus(200)
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

        // Assert user is authenticated after login
        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->email)->toBe('test@example.com');
    });

    // Test: Login fails with wrong password
    it('fails login with wrong password', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correctpassword'),
        ]);
        $user->assignRole('user');

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);

        // Assert user is not authenticated
        expect(auth()->check())->toBeFalse();
    });

    // Test: Login fails with non-existing email
    it('fails login with non-existing email', function () {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);

        // Assert user is not authenticated
        expect(auth()->check())->toBeFalse();
    });

    // Test: Login fails with missing fields
    it('fails login when email is missing', function () {
        $credentials = [
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails login when password is missing', function () {
        $credentials = [
            'email' => 'test@example.com',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('fails login with invalid email format', function () {
        $credentials = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    // Test: Assert authenticated user can access protected route
    it('allows authenticated user to access protected route', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        // Login first
        $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Access protected route
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'first_name',
                    'last_name',
                    'email',
                    'role',
                ],
            ])
            ->assertJson([
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);
    });

    // Test: Assert guest cannot access protected route
    it('prevents guest from accessing protected route', function () {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    // Additional tests: Role-based login
    it('can login as admin user', function () {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('admin');

        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'admin');

        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->hasRole('admin'))->toBeTrue();
    });

    it('can login as employee user', function () {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        $credentials = [
            'email' => 'employee@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'employee');

        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->hasRole('user'))->toBeTrue();
    });

    // Additional test: Employee relationship loading
    it('returns user with employee relationship when user is an employee', function () {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        // Create employee record
        $user->employee()->create([
            'employee_no' => 'EMP001',
            'status' => 'active',
        ]);

        $credentials = [
            'email' => 'employee@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'employee' => [
                            'uuid',
                            'employee_no',
                        ],
                    ],
                ],
            ]);
    });
});
