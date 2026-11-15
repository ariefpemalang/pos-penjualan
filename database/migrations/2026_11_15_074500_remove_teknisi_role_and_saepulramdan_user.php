<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hapus role Teknisi dan detach dari semua user
        $teknisi = Role::where('name', 'Teknisi')->first();
        if ($teknisi) {
            foreach ($teknisi->users as $user) {
                $user->removeRole($teknisi);
            }
            $teknisi->delete();
        }

        // 2. Hapus user superadmin saepulramdan
        $saepul = User::where('email', 'saepulramdan244@gmail.com')->first();
        if ($saepul) {
            $saepul->delete();
        }

        // 3. Pastikan ada Super Admin aktif jika saepul terhapus
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $hasSuperAdmin = User::role('Super Admin')->exists();

        if (! $hasSuperAdmin) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@arcell.store'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                ]
            );
            $admin->syncRoles([$superAdminRole]);
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
