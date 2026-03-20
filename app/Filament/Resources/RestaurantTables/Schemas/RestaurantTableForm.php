<?php

namespace App\Filament\Resources\RestaurantTables\Schemas;

use App\Enums\QrAccessStatus;
use App\Enums\TableStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required(),
                Select::make('sector_id')
                    ->relationship('sector', 'name')
                    ->default(null),
                TextInput::make('number')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->default(null),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(4),
                Select::make('shape')
                    ->options([
                        'square' => 'Cuadrada',
                        'rectangle' => 'Rectangular',
                        'round' => 'Redonda',
                        'oval' => 'Ovalada',
                    ])
                    ->required()
                    ->default('rectangle'),
                Select::make('status')
                    ->options(TableStatus::class)
                    ->default('free')
                    ->required(),
                Select::make('qr_access_status')
                    ->label('Estado acceso QR')
                    ->options(collect(QrAccessStatus::cases())->mapWithKeys(fn (QrAccessStatus $s) => [$s->value => $s->label()])->all())
                    ->default(QrAccessStatus::Active->value)
                    ->required()
                    ->helperText('Solo «Activa» permite usar el QR de mesa. Inactiva o suspendida bloquea pedidos desde QR.'),
                TextInput::make('pos_x')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('pos_y')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('width')
                    ->required()
                    ->numeric()
                    ->default(80.0),
                TextInput::make('height')
                    ->required()
                    ->numeric()
                    ->default(60.0),
                TextInput::make('rotation')
                    ->numeric()
                    ->default(null),
                TextInput::make('internal_notes')
                    ->default(null),
            ]);
    }
}
