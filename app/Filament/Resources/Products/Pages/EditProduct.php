<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $known = array_keys(config('deco.menu.dietary_tags', []));
        $tags = $data['tags'] ?? [];
        if (is_array($tags)) {
            $data['tags'] = array_values(array_intersect($tags, $known));
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $known = array_keys(config('deco.menu.dietary_tags', []));
        $tags = $data['tags'] ?? [];
        if (is_array($tags)) {
            $data['tags'] = array_values(array_intersect($tags, $known));
        }

        $data['promo_label'] = filled($data['promo_label'] ?? null) ? $data['promo_label'] : null;
        $data['promo_style'] = filled($data['promo_style'] ?? null) ? $data['promo_style'] : null;

        return $data;
    }
}
