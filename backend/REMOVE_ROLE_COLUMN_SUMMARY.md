# Remove Role Column - Migration to Spatie Permission Only

## Summary

The `role` column has been removed from the `users` table. The system now uses **only** Spatie Permission for role management.

## Changes Made

### 1. Database Migration ✅
- Created migration: `2026_01_07_092956_remove_role_from_users_table.php`
- Removes the `role` column from `users` table
- Includes rollback support

### 2. User Model ✅
- Removed `'role'` from `$fillable` array
- Updated `isAdmin()` to use only `$this->hasRole('admin')`
- Updated `isStudent()` to use only `$this->hasRole('user')` (students use 'user' role in Spatie)

### 3. UserService ✅
- Removed `'role'` from `createUser()` method
- No longer saves role to database

### 4. AuthService ✅
- Still accepts `role` parameter in registration (for assigning Spatie role)
- Extracts role from data before creating user
- Assigns Spatie role after user creation
- Role is not stored in users table

### 5. UserResource ✅
- Changed from `'role' => $this->role` to `'roles' => $this->getRoleNames()`
- Now returns array of role names from Spatie Permission

### 6. MakeAdminCommand ✅
- Removed `'role' => 'admin'` from user creation
- Now assigns role using `$user->assignRole('admin')` after creation
- Updated display to show `getRoleNames()` instead of `$user->role`

### 7. Seeders ✅
- **AdminUserSeeder**: Removed `'role' => 'admin'` from factory create
- **StudentUserSeeder**: Removed `'role' => 'student'` from factory create
- Both now only assign Spatie roles

## Migration Steps

### 1. Run the Migration

```bash
php artisan migrate
```

This will remove the `role` column from the `users` table.

### 2. Migrate Existing Users (if any)

If you have existing users with roles in the database, you'll need to migrate them:

```php
php artisan tinker

// Migrate existing users to Spatie roles
use App\Models\User;

// Get all users with admin role
$adminUsers = User::where('role', 'admin')->get();
foreach ($adminUsers as $user) {
    $user->assignRole('admin');
}

// Get all users with student role
$studentUsers = User::where('role', 'student')->get();
foreach ($studentUsers as $user) {
    $user->assignRole('user'); // Students use 'user' role in Spatie
}

// Get all users with staff role (if any)
$staffUsers = User::where('role', 'staff')->get();
foreach ($staffUsers as $user) {
    $user->assignRole('staff');
}
```

**Note**: Run this migration script BEFORE running the migration that removes the column, or the data will be lost.

### 3. Verify Changes

```php
php artisan tinker

$user = User::first();
$user->getRoleNames(); // Should return collection of role names
$user->hasRole('admin'); // Check if user has admin role
$user->isAdmin(); // Should work with Spatie roles
```

## API Response Changes

### Before (with role column):
```json
{
  "user": {
    "uuid": "...",
    "email": "user@example.com",
    "role": "admin"
  }
}
```

### After (with Spatie roles):
```json
{
  "user": {
    "uuid": "...",
    "email": "user@example.com",
    "roles": ["admin"]
  }
}
```

## Registration Flow

When a user registers:

1. User provides `role` in registration request (admin, staff, or student)
2. `role` is extracted from data
3. User is created **without** role field
4. Spatie role is assigned based on registration role:
   - `admin` → assigns `'admin'` role
   - `staff` → assigns `'staff'` role
   - `student` → assigns `'user'` role
5. If student, student record is created

## Role Mapping

| Registration Role | Spatie Role | Notes |
|------------------|-------------|-------|
| `admin` | `admin` | Full access |
| `staff` | `staff` | Limited access |
| `student` | `user` | Basic user access |

## Breaking Changes

1. **UserResource**: Changed from `role` (string) to `roles` (array)
2. **User Model**: No longer has `$user->role` property
3. **Database**: `role` column no longer exists

## Backward Compatibility

The following methods still work but now use Spatie Permission:
- `$user->isAdmin()` - Checks Spatie 'admin' role
- `$user->isStudent()` - Checks Spatie 'user' role

## Testing

After migration, test:

```bash
# Test registration
curl -X POST http://localhost/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "admin"
  }'

# Check user roles in response
# Should see "roles": ["admin"] instead of "role": "admin"
```

## Files Modified

- ✅ `database/migrations/2026_01_07_092956_remove_role_from_users_table.php` (new)
- ✅ `app/Models/User.php`
- ✅ `app/Services/UserService.php`
- ✅ `app/Services/AuthService.php`
- ✅ `app/Http/Resources/UserResource.php`
- ✅ `app/Console/Commands/MakeAdminCommand.php`
- ✅ `database/seeders/AdminUserSeeder.php`
- ✅ `database/seeders/StudentUserSeeder.php`

## Important Notes

1. **Run migration script BEFORE removing column** if you have existing users
2. **Update frontend** to expect `roles` (array) instead of `role` (string)
3. **Clear cache** after migration: `php artisan permission:cache-reset`
4. **Test thoroughly** before deploying to production
