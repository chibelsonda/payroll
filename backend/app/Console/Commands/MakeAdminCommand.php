<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {--first-name= : First name of the admin user}
                            {--last-name= : Last name of the admin user}
                            {--email= : Email address of the admin user}
                            {--password= : Password for the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $this->info('Creating a new admin user...');
        $this->newLine();

        // Get user input
        $firstName = $this->option('first-name') ?: $this->ask('First name');
        $lastName = $this->option('last-name') ?: $this->ask('Last name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password');

        // Validate input
        $validator = Validator::make([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
        ], [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return Command::FAILURE;
        }

        try {
            // Create admin user
            $user = $userService->createUser([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
            ]);

            // Assign admin role using Spatie Permission
            $user->assignRole('admin');

            $this->info('Admin user created successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->id],
                    ['UUID', $user->uuid],
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Roles', $user->getRoleNames()->implode(', ')],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
