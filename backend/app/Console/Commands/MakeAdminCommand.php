<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Company;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\PermissionRegistrar;

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
                            {--password= : Password for the admin user}
                            {--company-id= : Company ID to attach and scope the admin role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService, PermissionRegistrar $permissionRegistrar): int
    {
        $this->info('Creating a new admin user...');
        $this->newLine();

        // Get user input
        $firstName = $this->option('first-name') ?: $this->ask('First name');
        $lastName = $this->option('last-name') ?: $this->ask('Last name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password');
        $companyIdOption = $this->option('company-id');

        $company = null;
        if ($companyIdOption) {
            $company = Company::find($companyIdOption);
            if (! $company) {
                $this->error('Company not found for ID: ' . $companyIdOption);
                return Command::FAILURE;
            }
        } else {
            $company = Company::first();
        }

        if (! $company) {
            $this->error('No company found. Please create a company first or pass --company-id.');
            return Command::FAILURE;
        }

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

            // Attach user to company and scope role to that company (teams enabled)
            $user->companies()->syncWithoutDetaching([$company->id]);
            $permissionRegistrar->setPermissionsTeamId($company->id);
            $user->assignRole('admin', $company->id);

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
