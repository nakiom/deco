<?php

namespace App\Filament\Resources\Restaurants\Pages;

use App\Filament\Resources\Restaurants\RestaurantResource;
use App\Models\Restaurant;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewCarta')
                ->label('Ver carta pública')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->url(fn (Restaurant $record): string => route('carta.show', ['slug' => $record->slug]))
                ->openUrlInNewTab()
                ->visible(fn (Restaurant $record): bool => $record->is_active),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['menu_theme'] = array_merge(Restaurant::defaultMenuTheme(), $data['menu_theme'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['menu_theme'] = array_merge(Restaurant::defaultMenuTheme(), $data['menu_theme'] ?? []);

        if (! empty($data['menu_public_password_plain'])) {
            $data['menu_public_password_hash'] = password_hash($data['menu_public_password_plain'], PASSWORD_DEFAULT);
        }
        unset($data['menu_public_password_plain']);

        return $data;
    }
}
