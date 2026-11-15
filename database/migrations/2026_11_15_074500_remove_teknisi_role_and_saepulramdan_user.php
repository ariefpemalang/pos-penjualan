<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
            DB::table('model_has_roles')->where('role_id', $teknisi->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $teknisi->id)->delete();
            $teknisi->delete();
        }

        // 2. Pastikan ada Super Admin aktif admin@arcell.store
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = User::firstOrCreate(
            ['email' => 'admin@arcell.store'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        if (! $admin->hasRole('Super Admin')) {
            $admin->syncRoles([$superAdminRole]);
        }

        // 3. Re-assign foreign key dari saepulramdan ke admin@arcell.store, lalu hapus saepulramdan
        $saepul = User::where('email', 'saepulramdan244@gmail.com')->first();
        if ($saepul) {
            if ($saepul->id !== $admin->id) {
                DB::table('stock_movements')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('transactions')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('purchases')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('manual_cash_entries')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('service_tickets')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('technicians')->where('user_id', $saepul->id)->update(['user_id' => null]);

                if (DB::getSchemaBuilder()->hasTable('passkeys')) {
                    DB::table('passkeys')->where('user_id', $saepul->id)->delete();
                }
                if (DB::getSchemaBuilder()->hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $saepul->id)->delete();
                }

                DB::table('model_has_roles')->where('model_id', $saepul->id)->delete();
                DB::table('model_has_permissions')->where('model_id', $saepul->id)->delete();

                $saepul->delete();
            } else {
                // Jika ID-nya sama, cukup update email dan nama
                $saepul->update([
                    'name' => 'Super Admin',
                    'email' => 'admin@arcell.store',
                ]);
            }
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
