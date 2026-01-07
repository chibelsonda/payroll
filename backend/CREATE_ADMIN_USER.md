# How to Create an Admin User

There are several ways to create an admin user in this application:

## Method 1: Using Database Seeder (Recommended for Development)

The application already includes an `AdminUserSeeder`. Run it with:

```bash
php artisan db:seed --class=AdminUserSeeder
```

This creates an admin user with:
- **Email**: `admin@example.com`
- **Password**: `password` (default from factory)
- **Name**: Admin User

To seed all data including admin user:
```bash
php artisan db:seed
```

## Method 2: Using Artisan Command (Recommended for Production)

Create an admin user interactively:

```bash
php artisan make:admin
```

This will prompt you for:
- First name
- Last name
- Email
- Password

You can also provide all information via command options:

```bash
php artisan make:admin \
  --first-name="Admin" \
  --last-name="User" \
  --email="admin@example.com" \
  --password="your-secure-password"
```

## Method 3: Using Tinker

```bash
php artisan tinker
```

Then run:
```php
\App\Models\User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@example.com',
    'password' => \Illuminate\Support\Facades\Hash::make('your-secure-password'),
    'role' => 'admin',
]);
```

## Method 4: Using API Registration Endpoint

You can register via the API with the `role` field:

```bash
curl -X POST http://your-domain/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Admin",
    "last_name": "User",
    "email": "admin@example.com",
    "password": "your-secure-password",
    "password_confirmation": "your-secure-password",
    "role": "admin"
  }'
```

**Note**: The registration endpoint accepts `role` as an optional parameter. If not provided, it defaults to `'student'`.

## Method 5: Direct Database Insert (Not Recommended)

Only use this if other methods don't work:

```sql
INSERT INTO users (uuid, first_name, last_name, email, password, role, created_at, updated_at)
VALUES (
    UUID(),
    'Admin',
    'User',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'admin',
    NOW(),
    NOW()
);
```

## Default Admin User (from Seeder)

If you've run the seeder, you can login with:
- **Email**: `admin@example.com`
- **Password**: `password`

**⚠️ Important**: Change the default password immediately in production!

## Verifying Admin User

Check if a user is an admin:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'admin@example.com')->first();
$user->isAdmin(); // Returns true if admin
```

## Updating Existing User to Admin

To change an existing user's role to admin:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->update(['role' => 'admin']);
```
