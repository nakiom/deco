<?php

namespace App\Filament\Resources\Restaurants\Pages;

use App\Filament\Resources\Restaurants\RestaurantResource;
use App\Models\Restaurant;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['menu_theme'] = array_merge(Restaurant::defaultMenuTheme(), $data['menu_theme'] ?? []);

        if (! empty($data['menu_public_password_plain'])) {
            $data['menu_public_password_hash'] = password_hash($data['menu_public_password_plain'], PASSWORD_DEFAULT);
        } else {
            $data['menu_public_password_hash'] = null;
        }
        unset($data['menu_public_password_plain']);

        return $data;
    }
}
