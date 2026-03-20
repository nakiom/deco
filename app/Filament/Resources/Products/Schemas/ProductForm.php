<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ItemType;
use App\Enums\ProductStatus;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $dietary = config('deco.menu.dietary_tags', []);
        $ribbons = config('deco.menu.ribbon_presets', []);

        return $schema
            ->components([
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('short_description')
                    ->default(null),
                Textarea::make('long_description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('menu_note')
                    ->label('Comentario en carta')
                    ->helperText('Texto breve solo para la carta pública (sugerencia del chef, maridaje, etc.).')
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->hint('Pesos argentinos (ARS)'),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('products'),
                TextInput::make('sku')
                    ->label('SKU')
                    ->default(null),
                Select::make('item_type')
                    ->options(ItemType::class)
                    ->default('kitchen')
                    ->required(),
                Select::make('status')
                    ->options(ProductStatus::class)
                    ->default('available')
                    ->required(),
                Toggle::make('highlighted')
                    ->required(),

                Section::make('Carta digital')
                    ->description('Ofertas y etiquetas visibles en /carta/{slug}.')
                    ->schema([
                        TextInput::make('promo_label')
                            ->label('Cinta de oferta')
                            ->placeholder('Ej: 2×1, Nuevo, Happy hour')
                            ->maxLength(40),
                        Select::make('promo_style')
                            ->label('Estilo de la cinta')
                            ->options($ribbons)
                            ->placeholder('Por defecto del restaurante'),
                        CheckboxList::make('tags')
                            ->label('Etiquetas dietéticas / informativas')
                            ->options($dietary)
                            ->columns(2)
                            ->bulkToggleable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                TextInput::make('prep_time_minutes')
                    ->numeric()
                    ->default(null),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('stock_control')
                    ->required(),
                TextInput::make('current_stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('minimum_stock')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
