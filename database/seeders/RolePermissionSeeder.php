<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createPermissions();
        $this->createRoles();
    }

    private function createPermissions(): void
    {
        $permissions = [
            'restaurants.view', 'restaurants.create', 'restaurants.update', 'restaurants.delete',
            'sectors.view', 'sectors.create', 'sectors.update', 'sectors.delete',
            'tables.view', 'tables.create', 'tables.update', 'tables.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'orders.view', 'orders.create', 'orders.update', 'orders.delete',
            'kitchen.view', 'kitchen.update',
            'bar.view', 'bar.update',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    private function createRoles(): void
    {
        $dev = SpatieRole::firstOrCreate(['name' => Role::Dev->value, 'guard_name' => 'web']);
        $dev->givePermissionTo(Permission::all());

        $owner = SpatieRole::firstOrCreate(['name' => Role::Owner->value, 'guard_name' => 'web']);
        $owner->givePermissionTo([
            'restaurants.view', 'restaurants.update',
            'sectors.view', 'sectors.create', 'sectors.update', 'sectors.delete',
            'tables.view', 'tables.create', 'tables.update', 'tables.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'orders.view', 'orders.create', 'orders.update',
            'users.view', 'users.create', 'users.update',
            'kitchen.view', 'bar.view',
        ]);

        $manager = SpatieRole::firstOrCreate(['name' => Role::Manager->value, 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'sectors.view', 'sectors.create', 'sectors.update',
            'tables.view', 'tables.update',
            'categories.view', 'categories.update', 'products.view', 'products.update',
            'orders.view', 'orders.create', 'orders.update',
            'kitchen.view', 'kitchen.update', 'bar.view', 'bar.update',
            'users.view',
        ]);

        $kitchen = SpatieRole::firstOrCreate(['name' => Role::Kitchen->value, 'guard_name' => 'web']);
        $kitchen->givePermissionTo(['kitchen.view', 'kitchen.update', 'orders.view', 'products.view']);

        $bar = SpatieRole::firstOrCreate(['name' => Role::Bar->value, 'guard_name' => 'web']);
        $bar->givePermissionTo(['bar.view', 'bar.update', 'orders.view', 'products.view']);

        $waiter = SpatieRole::firstOrCreate(['name' => Role::Waiter->value, 'guard_name' => 'web']);
        $waiter->givePermissionTo([
            'tables.view', 'tables.update', 'orders.view', 'orders.create', 'orders.update',
            'products.view', 'kitchen.view', 'bar.view',
        ]);

        $client = SpatieRole::firstOrCreate(['name' => Role::Client->value, 'guard_name' => 'web']);
        // Cliente: sin permisos en panel admin
    }
}
