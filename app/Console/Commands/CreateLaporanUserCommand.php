<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateLaporanUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:create-laporan-user 
                            {email=laporan@arcell.store : Alamat email user laporan}
                            {--name=Staff Laporan : Nama lengkap user}
                            {--password=password : Password untuk akun user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat atau daftarkan user dengan role Laporan (Keuangan, Kategori, Transaksi, Penjualan Barang, Penjualan Kategori, Data Pembelian)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');

        $this->info("Menyiapkan role dan user Laporan...");

        $guard = (string) config('auth.defaults.guard', 'web');

        $permissions = [
            'dashboard view',
            'dashboard finance view',
            'financial report view',
            'asset report view',
            'sales report view',
            'item sales report view',
            'category sales report view',
            'purchase report view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'Laporan',
            'guard_name' => $guard,
        ]);
        $role->syncPermissions($permissions);

        $this->info("Role [Laporan] berhasil disinkronisasi dengan permission:");
        foreach ($permissions as $p) {
            $this->line("  ✓ {$p}");
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;
        $user->name = $name;
        if ($isNew || $this->hasOption('password')) {
            $user->password = Hash::make($password);
        }
        $user->save();
        $user->syncRoles([$role]);

        $this->newLine();
        $this->info($isNew ? 'User Laporan baru BERHASIL DIBUAT!' : 'User Laporan BERHASIL DIPERBARUI!');
        $this->table(
            ['Field', 'Keterangan'],
            [
                ['Nama', $user->name],
                ['Email', $user->email],
                ['Password', $isNew ? $password : '(tidak berubah / sesuai input)'],
                ['Role', 'Laporan'],
                ['Akses Menu', "1. Dashboard (KPI Keuangan)\n2. Laporan Keuangan\n3. Laporan Kategori\n4. Laporan Transaksi\n5. Laporan Penjualan Barang\n6. Laporan Penjualan Kategori\n7. Laporan Data Pembelian"],
            ]
        );

        return Command::SUCCESS;
    }
}
