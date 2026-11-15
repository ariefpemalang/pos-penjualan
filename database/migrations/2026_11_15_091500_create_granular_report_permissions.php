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
        $guard = (string) config('auth.defaults.guard', 'web');

        $reportPermissions = [
            'financial report view',
            'asset report view',
            'sales report view',
            'item sales report view',
            'category sales report view',
            'purchase report view',
        ];

        foreach ($reportPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        // Pastikan Super Admin memiliki seluruh permission laporan
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($reportPermissions);
        }

        // Pastikan role Laporan juga disinkronisasikan dengan seluruh permission laporan ini
        $roleLaporan = Role::where('name', 'Laporan')->first();
        if ($roleLaporan) {
            $roleLaporan->givePermissionTo($reportPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
