<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionGroups;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return [
            'dashboard view', 'dashboard finance view', 'dashboard low stock view',
            'dashboard items view', 'dashboard customers view',
            'dashboard technicians view', 'dashboard services view', 'dashboard commission view',
            'user view', 'user create', 'user edit', 'user delete',
            'role view', 'role create', 'role edit', 'role delete',
            'customer view', 'customer create', 'customer edit', 'customer delete',
            'supplier view', 'supplier create', 'supplier edit', 'supplier delete',
            'technician view', 'technician create', 'technician edit', 'technician delete',
            'item category view', 'item category create', 'item category edit', 'item category delete',
            'item unit view', 'item unit create', 'item unit edit', 'item unit delete',
            'item view', 'item create', 'item edit', 'item delete',
            'item import', 'item export',
            'stock in view', 'stock in create',
            'stock out view', 'stock out create',
            'stock report view',
            'stock opname view', 'stock opname create', 'stock opname adjust',
            'workshop service view', 'workshop service create', 'workshop service edit', 'workshop service delete',
            'service ticket view', 'service ticket create', 'service ticket edit', 'service ticket delete',
            'transaction view', 'transaction create', 'transaction edit', 'transaction delete',
            'purchase view', 'purchase create', 'purchase edit', 'purchase delete',
            'financial report view',
            'service report view',
            'technician report view',
            'asset report view',
            'manual income view', 'manual income create', 'manual income cancel',
            'manual expense view', 'manual expense create', 'manual expense cancel',
            'bank account view', 'bank account create', 'bank account edit', 'bank account delete',
            'settings view', 'settings edit',
        ];
    }

    public function syncPermissionsAndRoles(): void
    {
        foreach (array_unique([...self::permissionNames(), ...PermissionGroups::names()]) as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $kasir = Role::firstOrCreate(['name' => 'Kasir']);
        $kasir->syncPermissions([
            'dashboard view',
            'customer view', 'customer create', 'customer edit',
            'supplier view', 'supplier create', 'supplier edit',
            'technician view',
            'item view', 'item category view', 'item unit view',
            'item export',
            'stock in view', 'stock in create',
            'stock out view', 'stock out create',
            'stock report view',
            'stock opname view', 'stock opname create', 'stock opname adjust',
            'service ticket view', 'service ticket create', 'service ticket edit',
            'transaction view', 'transaction create',
            'purchase view', 'purchase create',
            'financial report view',
            'service report view',
            'technician report view',
            'asset report view',
            'manual income view', 'manual income create', 'manual income cancel',
            'manual expense view', 'manual expense create', 'manual expense cancel',
        ]);

        Role::where('name', 'Teknisi')->each(function (Role $role) {
            foreach ($role->users as $user) {
                $user->removeRole($role);
            }
            $role->delete();
        });
    }

    public function run(): void
    {
        $this->syncPermissionsAndRoles();

        Role::where('name', 'Admin')->each(function (Role $role) {
            $kasir = Role::firstOrCreate(['name' => 'Kasir']);
            foreach ($role->users as $user) {
                $user->syncRoles([$kasir]);
            }
            $role->delete();
        });

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

        $saepul = User::where('email', 'saepulramdan244@gmail.com')->first();
        if ($saepul) {
            if ($saepul->id !== $admin->id) {
                \Illuminate\Support\Facades\DB::table('stock_movements')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                \Illuminate\Support\Facades\DB::table('transactions')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                \Illuminate\Support\Facades\DB::table('purchases')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                \Illuminate\Support\Facades\DB::table('manual_cash_entries')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                \Illuminate\Support\Facades\DB::table('service_tickets')->where('user_id', $saepul->id)->update(['user_id' => $admin->id]);
                \Illuminate\Support\Facades\DB::table('technicians')->where('user_id', $saepul->id)->update(['user_id' => null]);
                \Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', $saepul->id)->delete();
                \Illuminate\Support\Facades\DB::table('model_has_permissions')->where('model_id', $saepul->id)->delete();
                $saepul->delete();
            } else {
                $saepul->update([
                    'name' => 'Super Admin',
                    'email' => 'admin@arcell.store',
                ]);
            }
        }
    }
}
