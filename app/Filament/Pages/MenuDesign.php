<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Restaurant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class MenuDesign extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Carta digital';

    protected static ?string $title = 'Carta digital';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.menu-design';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Carta';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('restaurants.view') ?? false;
    }

    public function getRestaurants(): Collection
    {
        return Restaurant::query()
            ->orderBy('name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Cómo editar')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->color('gray')
                ->modalHeading('Diseño de carta')
                ->modalDescription('Editá cada restaurante en Restaurantes → Editar: sección «Carta digital» (logo, fondos, colores). Los platos y etiquetas se cargan en Productos. La URL pública es /carta/{slug}.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),
        ];
    }
}
