<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de demostración: uno por rol para probar permisos y vistas del panel.
 * Contraseña común: "password" (cambiar en producción).
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::query()->where('slug', 'deco-bar')->first();

        /** @var list<array{Role, string, string, bool}> $rows role, email, name, attach_restaurant */
        $rows = [
            [Role::Owner, 'owner@deco.local', 'Dueño Demo', true],
            [Role::Manager, 'manager@deco.local', 'Gerente Demo', true],
            [Role::Kitchen, 'kitchen@deco.local', 'Cocina Demo', true],
            [Role::Bar, 'bar@deco.local', 'Barra Demo', true],
            [Role::Waiter, 'waiter@deco.local', 'Mozo Demo', true],
            [Role::Client, 'client@deco.local', 'Cliente Demo', false],
        ];

        foreach ($rows as [$role, $email, $name, $attachRestaurant]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'restaurant_id' => $attachRestaurant ? $restaurant?->id : null,
                ]
            );

            $user->syncRoles([$role->value]);
        }
    }
}
