<?php

namespace App\Filament\Resources\Restaurants\Schemas;

use App\Models\Restaurant;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        $backgrounds = config('deco.menu.backgrounds', []);
        $fontPairs = config('deco.menu.font_pairs', []);
        $ribbons = config('deco.menu.ribbon_presets', []);

        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->helperText('URL pública: /carta/{slug}. Letras, números y guiones (ej. decoCentro o centro-norte).'),
                TextInput::make('address')
                    ->default(null),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),

                Section::make('Acceso carta por QR')
                    ->description('Opcional: contraseña para quien entra con el enlace de mesa (/menu/…).')
                    ->schema([
                        Toggle::make('menu_public_password_enabled')
                            ->label('Pedir contraseña al abrir la carta desde QR')
                            ->default(false),
                        TextInput::make('menu_public_password_plain')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->helperText('Solo al crear o cambiar: dejá vacío para mantener la contraseña actual al editar.'),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('Carta digital')
                    ->description('Aspecto de la carta pública. Los clientes acceden con /carta/{slug}.')
                    ->schema([
                        FileUpload::make('menu_logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/menu')
                            ->imageResizeMode('contain')
                            ->maxSize(2048),
                        FileUpload::make('menu_header_image')
                            ->label('Imagen de cabecera')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/menu')
                            ->maxSize(4096)
                            ->helperText('Banner opcional bajo el logo.'),
                        TextInput::make('menu_theme.tagline')
                            ->label('Subtítulo / lema')
                            ->maxLength(255),
                        Textarea::make('menu_theme.footer_note')
                            ->label('Nota al pie')
                            ->rows(2)
                            ->columnSpanFull(),
                        Select::make('menu_theme.background')
                            ->label('Fondo / textura')
                            ->options($backgrounds)
                            ->default(Restaurant::defaultMenuTheme()['background']),
                        ColorPicker::make('menu_theme.accent_color')
                            ->label('Color de acento')
                            ->default(Restaurant::defaultMenuTheme()['accent_color']),
                        Select::make('menu_theme.font_pair')
                            ->label('Tipografías')
                            ->options($fontPairs)
                            ->default(Restaurant::defaultMenuTheme()['font_pair']),
                        Radio::make('menu_theme.layout_columns')
                            ->label('Columnas de platos (en pantallas anchas)')
                            ->options([
                                1 => 'Una columna',
                                2 => 'Dos columnas',
                            ])
                            ->inline()
                            ->default(2),
                        Select::make('menu_theme.ribbon_preset')
                            ->label('Estilo de cintas de oferta')
                            ->options($ribbons)
                            ->default(Restaurant::defaultMenuTheme()['ribbon_preset']),
                        Toggle::make('menu_theme.show_category_images')
                            ->label('Mostrar imagen de categoría')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
