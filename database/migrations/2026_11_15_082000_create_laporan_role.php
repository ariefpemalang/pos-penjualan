<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'dashboard view',
            'dashboard finance view',
            'financial report view',
            'sales report view',
            'asset report view',
        ];

        $guard = (string) config('auth.defaults.guard', 'web');

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'Laporan',
            'guard_name' => $guard,
        ]);

        $role->syncPermissions($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'Laporan')->first();
        if ($role) {
            $role->syncPermissions([]);
            $role->delete();
        }
    }
};
