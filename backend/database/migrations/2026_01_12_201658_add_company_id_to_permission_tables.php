<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'company_id';

        // Add company_id to roles table if it doesn't exist
        if (Schema::hasTable($tableNames['roles']) && !Schema::hasColumn($tableNames['roles'], $teamForeignKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('id');
                $table->index($teamForeignKey, 'roles_company_foreign_key_index');

                // Drop existing unique constraint on name+guard_name
                $table->dropUnique(['name', 'guard_name']);
            });

            // Add unique constraint on company_id+name+guard_name
            DB::statement("ALTER TABLE {$tableNames['roles']} ADD UNIQUE ({$teamForeignKey}, name, guard_name)");
        }

        // Add company_id to model_has_roles table if it doesn't exist
        if (Schema::hasTable($tableNames['model_has_roles']) && !Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('model_id');
                $table->index($teamForeignKey, 'model_has_roles_company_foreign_key_index');
            });

            // Drop existing primary key (PostgreSQL requires constraint name)
            $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
            $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                // PostgreSQL: Find and drop the primary key constraint by name
                $pkConstraint = DB::selectOne("
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = '{$tableNames['model_has_roles']}'
                    AND constraint_type = 'PRIMARY KEY'
                ");
                if ($pkConstraint) {
                    DB::statement("ALTER TABLE {$tableNames['model_has_roles']} DROP CONSTRAINT {$pkConstraint->constraint_name}");
                }
            } else {
                // MySQL/SQLite: Use standard syntax
                DB::statement("ALTER TABLE {$tableNames['model_has_roles']} DROP PRIMARY KEY");
            }

            // Set default value for existing rows (use a default company_id or leave NULL for now)
            // Note: You may need to populate this with actual company_id values in a separate data migration
            // For now, we keep it nullable and can't make it part of the primary key yet

            // Add new primary key with company_id (only if column will not be NULL)
            // Since we're making it nullable for existing data, we need to handle this differently
            // For now, we'll create a unique index instead and handle NULL values
            DB::statement("CREATE UNIQUE INDEX {$tableNames['model_has_roles']}_unique_idx ON {$tableNames['model_has_roles']} (COALESCE({$teamForeignKey}, 0), {$pivotRole}, {$modelMorphKey}, model_type) WHERE {$teamForeignKey} IS NOT NULL");
            DB::statement("CREATE UNIQUE INDEX {$tableNames['model_has_roles']}_unique_null_idx ON {$tableNames['model_has_roles']} ({$pivotRole}, {$modelMorphKey}, model_type) WHERE {$teamForeignKey} IS NULL");
        }

        // Add company_id to model_has_permissions table if it doesn't exist
        if (Schema::hasTable($tableNames['model_has_permissions']) && !Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('model_id');
                $table->index($teamForeignKey, 'model_has_permissions_company_foreign_key_index');
            });

            // Drop existing primary key (PostgreSQL requires constraint name)
            $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
            $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                // PostgreSQL: Find and drop the primary key constraint by name
                $pkConstraint = DB::selectOne("
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = '{$tableNames['model_has_permissions']}'
                    AND constraint_type = 'PRIMARY KEY'
                ");
                if ($pkConstraint) {
                    DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} DROP CONSTRAINT {$pkConstraint->constraint_name}");
                }
            } else {
                // MySQL/SQLite: Use standard syntax
                DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} DROP PRIMARY KEY");
            }

            // Add new primary key with company_id
            DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} ADD PRIMARY KEY ({$teamForeignKey}, {$pivotPermission}, {$modelMorphKey}, model_type)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'company_id';

        // Reverse model_has_permissions changes
        if (Schema::hasTable($tableNames['model_has_permissions']) && Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey)) {
            $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
            $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                $pkConstraint = DB::selectOne("
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = '{$tableNames['model_has_permissions']}'
                    AND constraint_type = 'PRIMARY KEY'
                ");
                if ($pkConstraint) {
                    DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} DROP CONSTRAINT {$pkConstraint->constraint_name}");
                }
            } else {
                DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} DROP PRIMARY KEY");
            }
            DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} ADD PRIMARY KEY ({$pivotPermission}, {$modelMorphKey}, model_type)");

            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropIndex('model_has_permissions_company_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }

        // Reverse model_has_roles changes
        if (Schema::hasTable($tableNames['model_has_roles']) && Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey)) {
            $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
            $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                $pkConstraint = DB::selectOne("
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = '{$tableNames['model_has_roles']}'
                    AND constraint_type = 'PRIMARY KEY'
                ");
                if ($pkConstraint) {
                    DB::statement("ALTER TABLE {$tableNames['model_has_roles']} DROP CONSTRAINT {$pkConstraint->constraint_name}");
                }
            } else {
                DB::statement("ALTER TABLE {$tableNames['model_has_roles']} DROP PRIMARY KEY");
            }
            DB::statement("ALTER TABLE {$tableNames['model_has_roles']} ADD PRIMARY KEY ({$pivotRole}, {$modelMorphKey}, model_type)");

            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropIndex('model_has_roles_company_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }

        // Reverse roles changes
        if (Schema::hasTable($tableNames['roles']) && Schema::hasColumn($tableNames['roles'], $teamForeignKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey, $tableNames) {
                $table->dropUnique([$teamForeignKey, 'name', 'guard_name']);
                $table->unique(['name', 'guard_name']);
                $table->dropIndex('roles_company_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }
    }
};
