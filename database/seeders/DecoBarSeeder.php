<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\FloorPlan;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DecoBarSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::firstOrCreate(
            ['slug' => 'deco-bar'],
            [
                'name' => 'Deco Bar',
                'address' => 'Av. Principal 123',
                'is_active' => true,
            ]
        );

        $devName = config('deco.dev_name');
        $devEmail = config('deco.dev_email');

        $dev = User::firstOrCreate(
            ['email' => $devEmail],
            [
                'name' => $devName,
                'password' => Hash::make('password'),
                'restaurant_id' => $restaurant->id,
            ]
        );
        $dev->assignRole(Role::Dev->value);

        if (! $restaurant->owner_id) {
            $restaurant->update(['owner_id' => $dev->id]);
        }

        if ($restaurant->wasRecentlyCreated) {
            $this->seedSectors($restaurant);
            $this->seedCategories($restaurant);
            $this->seedProducts($restaurant);
            $this->seedTables($restaurant);
        }
    }

    private function seedSectors(Restaurant $restaurant): void
    {
        $sectors = [
            ['name' => 'Salón principal', 'sort_order' => 1],
            ['name' => 'Patio', 'sort_order' => 2],
            ['name' => 'Barra', 'sort_order' => 3],
        ];

        foreach ($sectors as $data) {
            Sector::firstOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => $data['name']],
                ['sort_order' => $data['sort_order']]
            );
        }
    }

    private function seedCategories(Restaurant $restaurant): void
    {
        $categories = [
            ['name' => 'Entradas', 'sort_order' => 1],
            ['name' => 'Principales', 'sort_order' => 2],
            ['name' => 'Postres', 'sort_order' => 3],
            ['name' => 'Bebidas', 'sort_order' => 4],
            ['name' => 'Tragos', 'sort_order' => 5],
            ['name' => 'Cafetería', 'sort_order' => 6],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'slug' => Str::slug($data['name']),
                ],
                [
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedProducts(Restaurant $restaurant): void
    {
        $entradas = Category::where('restaurant_id', $restaurant->id)->where('slug', 'entradas')->first();
        $principales = Category::where('restaurant_id', $restaurant->id)->where('slug', 'principales')->first();
        $bebidas = Category::where('restaurant_id', $restaurant->id)->where('slug', 'bebidas')->first();
        $tragos = Category::where('restaurant_id', $restaurant->id)->where('slug', 'tragos')->first();

        $products = [
            ['category' => $entradas, 'name' => 'Bruschetta', 'price' => 8.50, 'item_type' => ItemType::Kitchen],
            ['category' => $entradas, 'name' => 'Empanadas', 'price' => 12.00, 'item_type' => ItemType::Kitchen],
            ['category' => $principales, 'name' => 'Milanesa napolitana', 'price' => 18.00, 'item_type' => ItemType::Kitchen],
            ['category' => $principales, 'name' => 'Ensalada César', 'price' => 14.50, 'item_type' => ItemType::Kitchen],
            ['category' => $bebidas, 'name' => 'Agua mineral', 'price' => 3.00, 'item_type' => ItemType::Bar],
            ['category' => $bebidas, 'name' => 'Coca Cola', 'price' => 4.00, 'item_type' => ItemType::Bar],
            ['category' => $tragos, 'name' => 'Fernet con Coca', 'price' => 9.00, 'item_type' => ItemType::Bar],
            ['category' => $tragos, 'name' => 'Gin Tonic', 'price' => 12.00, 'item_type' => ItemType::Bar],
        ];

        foreach ($products as $data) {
            if ($data['category']) {
                Product::firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $data['category']->id,
                        'slug' => Str::slug($data['name']),
                    ],
                    [
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'item_type' => $data['item_type'],
                        'status' => ProductStatus::Available,
                        'short_description' => $data['name'],
                    ]
                );
            }
        }
    }

    private function seedTables(Restaurant $restaurant): void
    {
        $sector = Sector::where('restaurant_id', $restaurant->id)->first();
        $floorPlan = FloorPlan::firstOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ],
            [
                'name' => 'Salón principal',
                'version' => 1,
                'width' => 1080,
                'height' => 680,
                'base_shape' => 'rectangle',
                'grid_size' => 20,
                'show_grid' => true,
            ]
        );

        for ($i = 1; $i <= 8; $i++) {
            RestaurantTable::firstOrCreate(
                ['restaurant_id' => $restaurant->id, 'number' => $i],
                [
                    'floor_plan_id' => $floorPlan->id,
                    'sector_id' => $sector?->id,
                    'capacity' => 4,
                    'shape' => match ($i % 4) {
                        1 => 'square',
                        2 => 'rectangle',
                        3 => 'round',
                        default => 'oval',
                    },
                    'status' => TableStatus::Free,
                    'pos_x' => (($i - 1) % 4) * 120,
                    'pos_y' => floor(($i - 1) / 4) * 100,
                    'width' => 80,
                    'height' => 60,
                ]
            );
        }
    }
}
