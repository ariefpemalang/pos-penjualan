<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PurgeLegacyUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:clean-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus superadmin saepulramdan dan role Teknisi secara aman dan tuntas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembersihan data superadmin lama & role teknisi...');

        // 1. Pastikan Role Super Admin ada
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // 2. Pastikan user admin@arcell.store ada sebagai Super Admin
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
        $this->info("Super Admin aktif: {$admin->name} ({$admin->email}) [ID: {$admin->id}]");

        // Pastikan Role Laporan tersedia
        $laporanRole = Role::firstOrCreate(['name' => 'Laporan']);
        $laporanPermissions = [
            'dashboard view',
            'dashboard finance view',
            'financial report view',
            'sales report view',
            'asset report view',
        ];
        foreach ($laporanPermissions as $p) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p]);
        }
        $laporanRole->syncPermissions($laporanPermissions);
        $this->info("Role Laporan siap digunakan dengan akses: " . implode(', ', $laporanPermissions));

        // 3. Hapus Role Teknisi
        $teknisi = Role::where('name', 'Teknisi')->first();
        if ($teknisi) {
            $this->warn("Role Teknisi ditemukan (ID: {$teknisi->id}). Menghapus...");
            foreach ($teknisi->users as $u) {
                $u->removeRole($teknisi);
            }
            DB::table('model_has_roles')->where('role_id', $teknisi->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $teknisi->id)->delete();
            $teknisi->delete();
            $this->info('Role Teknisi BERHASIL DIHAPUS.');
        } else {
            $this->info('Role Teknisi sudah tidak ada.');
        }

        // 4. Cari dan hapus saepulramdan244@gmail.com
        $saepul = User::where('email', 'saepulramdan244@gmail.com')->first();
        if ($saepul) {
            $this->warn("User Saepul Ramdan ditemukan (ID: {$saepul->id}). Memproses penghapusan...");

            if ($saepul->id !== $admin->id) {
                // Reassign foreign keys to admin
                $stockCount = DB::table('stock_movements')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                $txCount = DB::table('transactions')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                $purchCount = DB::table('purchases')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                $cashCount = DB::table('manual_cash_entries')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                $serviceCount = DB::table('service_tickets')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                DB::table('technicians')->where('user_id', $saepul->id)->update(['user_id' => null]);

                $this->line("- Mengalihkan {$stockCount} riwayat stok ke admin.");
                $this->line("- Mengalihkan {$txCount} transaksi ke admin.");
                $this->line("- Mengalihkan {$purchCount} pembelian ke admin.");
                $this->line("- Mengalihkan {$cashCount} mutasi kas ke admin.");
                $this->line("- Mengalihkan {$serviceCount} service ticket ke admin.");

                if (DB::getSchemaBuilder()->hasTable('passkeys')) {
                    DB::table('passkeys')->where('user_id', $saepul->id)->delete();
                }
                if (DB::getSchemaBuilder()->hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $saepul->id)->delete();
                }

                DB::table('model_has_roles')->where('model_id', $saepul->id)->delete();
                DB::table('model_has_permissions')->where('model_id', $saepul->id)->delete();

                $saepul->delete();
                $this->info('User saepulramdan244@gmail.com BERHASIL DIHAPUS!');
            } else {
                $saepul->update([
                    'name' => 'Super Admin',
                    'email' => 'admin@arcell.store',
                ]);
                $this->info('User saepulramdan244@gmail.com telah diubah menjadi admin@arcell.store.');
            }
        } else {
            $this->info('User saepulramdan244@gmail.com sudah tidak ada di database.');
        }

        // 5. Tampilkan daftar user saat ini
        $this->newLine();
        $this->info('=== DAFTAR USER AKTIF SAAT INI ===');
        $users = User::with('roles')->get();
        foreach ($users as $u) {
            $roles = $u->roles->pluck('name')->implode(', ');
            $this->line("ID: {$u->id} | {$u->name} | {$u->email} | Roles: [{$roles}]");
        }

        $this->newLine();
        $this->info('Pembersihan selesai sempurna!');

        return Command::SUCCESS;
    }
}
