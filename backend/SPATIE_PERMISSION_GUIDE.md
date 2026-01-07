# Spatie Laravel Permission - RBAC Implementation Guide

This project uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for Role-Based Access Control (RBAC).

## Installation

The package has been installed and configured. The following files were created/modified:

- `config/permission.php` - Configuration file
- `database/migrations/2026_01_07_091308_create_permission_tables.php` - Database tables
- `app/Models/User.php` - Added `HasRoles` trait

## Database Setup

Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Or seed everything:

```bash
php artisan migrate:fresh --seed
```

## Roles and Permissions

### Roles
- **admin** - Full system access
- **staff** - Limited content management access
- **user** - Basic user access (no management permissions)

### Permissions
- `manage users` - Manage user accounts
- `create posts` - Create new posts
- `edit posts` - Edit existing posts
- `delete posts` - Delete posts

### Permission Assignments

| Role | Permissions |
|------|-------------|
| admin | All permissions (manage users, create posts, edit posts, delete posts) |
| staff | create posts, edit posts |
| user | None |

## Usage Examples

### 1. Assigning Roles to Users

```php
use App\Models\User;

$user = User::find(1);

// Assign a single role
$user->assignRole('admin');

// Assign multiple roles
$user->assignRole(['admin', 'staff']);

// Remove a role
$user->removeRole('admin');

// Sync roles (removes all existing roles and assigns new ones)
$user->syncRoles(['staff']);
```

### 2. Assigning Permissions to Users

```php
// Assign a single permission
$user->givePermissionTo('create posts');

// Assign multiple permissions
$user->givePermissionTo(['create posts', 'edit posts']);

// Remove a permission
$user->revokePermissionTo('create posts');

// Sync permissions
$user->syncPermissions(['create posts', 'edit posts']);
```

### 3. Assigning Permissions to Roles

```php
use Spatie\Permission\Models\Role;

$role = Role::findByName('staff');
$role->givePermissionTo(['create posts', 'edit posts']);
```

### 4. Checking Roles and Permissions

```php
// Check if user has a role
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'staff']);
$user->hasAllRoles(['admin', 'staff']);

// Check if user has a permission
$user->can('create posts');
$user->hasPermissionTo('create posts');
$user->hasAnyPermission(['create posts', 'edit posts']);
$user->hasAllPermissions(['create posts', 'edit posts']);

// Check if user has permission via role
$user->hasPermissionTo('create posts'); // Checks both direct permissions and role permissions
```

### 5. Using Middleware in Routes

#### Role Middleware

```php
// Single role
Route::middleware('role:admin')->group(function () {
    // Only admin can access
});

// Multiple roles (OR condition)
Route::middleware('role:admin|staff')->group(function () {
    // Admin OR staff can access
});
```

#### Permission Middleware

```php
// Single permission
Route::middleware('permission:manage users')->group(function () {
    // Only users with 'manage users' permission can access
});

// Multiple permissions (AND condition - user must have all)
Route::middleware('permission:create posts|edit posts')->group(function () {
    // User must have BOTH permissions
});
```

#### Role or Permission Middleware

```php
// User needs either role OR permission
Route::middleware('role_or_permission:admin|manage users')->group(function () {
    // Admin role OR manage users permission
});
```

### 6. Using in Controllers

#### Method 1: Using Middleware (Recommended)

Define in routes:

```php
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('permission:create posts');
```

#### Method 2: Using authorize() Method

```php
public function store(Request $request)
{
    $this->authorize('create posts');
    // Your logic here
}
```

#### Method 3: Using can() Check

```php
public function update(Request $request, $id)
{
    if (!$request->user()->can('edit posts')) {
        return $this->forbiddenResponse('You do not have permission to edit posts');
    }
    // Your logic here
}
```

#### Method 4: Using hasPermissionTo()

```php
public function destroy($id)
{
    if (!auth()->user()->hasPermissionTo('delete posts')) {
        return $this->forbiddenResponse('You do not have permission to delete posts');
    }
    // Your logic here
}
```

### 7. Using in Blade Views

```blade
@role('admin')
    <p>This is only visible to admins</p>
@endrole

@hasrole('staff')
    <p>This is visible to staff</p>
@endhasrole

@can('create posts')
    <a href="/posts/create">Create Post</a>
@endcan

@hasanyrole('admin|staff')
    <p>Visible to admin or staff</p>
@endhasanyrole
```

### 8. Using in Policies

You can combine Spatie Permission with Laravel Policies:

```php
public function update(User $user, Post $post)
{
    return $user->hasPermissionTo('edit posts');
}
```

## Example Routes

See `routes/api.php` for complete examples:

```php
// Posts with permission-based access
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']); // Public to authenticated
    Route::post('/', [PostController::class, 'store'])
        ->middleware('permission:create posts');
    Route::put('/{id}', [PostController::class, 'update'])
        ->middleware('permission:edit posts');
    Route::delete('/{id}', [PostController::class, 'destroy'])
        ->middleware('permission:delete posts');
});
```

## Testing Roles and Permissions

### Create Test Users

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create admin user
$admin = User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@test.com',
    'password' => Hash::make('password'),
]);
$admin->assignRole('admin');

// Create staff user
$staff = User::create([
    'first_name' => 'Staff',
    'last_name' => 'User',
    'email' => 'staff@test.com',
    'password' => Hash::make('password'),
]);
$staff->assignRole('staff');

// Create regular user
$user = User::create([
    'first_name' => 'Regular',
    'last_name' => 'User',
    'email' => 'user@test.com',
    'password' => Hash::make('password'),
]);
$user->assignRole('user');
```

## Clearing Cache

Spatie Permission caches roles and permissions for performance. Clear cache when making changes:

```php
php artisan permission:cache-reset
```

Or programmatically:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

## Best Practices

1. **Use Middleware for Route Protection**: Prefer middleware over controller checks for cleaner code
2. **Use Policies for Complex Logic**: Combine with Laravel Policies for resource-specific authorization
3. **Cache Management**: Remember to clear cache after role/permission changes
4. **Naming Conventions**: Use kebab-case for permissions (e.g., 'create posts', 'manage users')
5. **Role Hierarchy**: Consider using role hierarchy for complex scenarios
6. **Testing**: Always test permission checks in your test suite

## Migration from Legacy Role System

The User model maintains backward compatibility with the legacy `role` field:

```php
// Both work:
$user->isAdmin(); // Checks Spatie roles AND legacy role field
$user->hasRole('admin'); // Spatie Permission method
```

## Additional Resources

- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization Documentation](https://laravel.com/docs/authorization)
