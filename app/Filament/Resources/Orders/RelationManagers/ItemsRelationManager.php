<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderItemStatus;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        $restaurantId = $this->getOwnerRecord()->restaurant_id;

        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Producto')
                    ->options(function () use ($restaurantId) {
                        return Product::query()
                            ->where('restaurant_id', $restaurantId)
                            ->orderBy('name')
                            ->limit(500)
                            ->get()
                            ->mapWithKeys(fn (Product $p) => [
                                $p->id => $p->name.' ($'.number_format((float) $p->price, 0, ',', '.').' ARS)',
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) use ($restaurantId) {
                        return Product::query()
                            ->where('restaurant_id', $restaurantId)
                            ->where('name', 'like', '%'.$search.'%')
                            ->orderBy('name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Product $p) => [
                                $p->id => $p->name.' ($'.number_format((float) $p->price, 0, ',', '.').' ARS)',
                            ])
                            ->all();
                    })
                    ->getOptionLabelUsing(function ($value) use ($restaurantId) {
                        if (blank($value)) {
                            return null;
                        }
                        $p = Product::query()
                            ->where('restaurant_id', $restaurantId)
                            ->find($value);

                        return $p ? $p->name.' ($'.number_format((float) $p->price, 0, ',', '.').' ARS)' : null;
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('unit_price', (float) $product->price);
                            }
                        }
                    }),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),
                TextInput::make('unit_price')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->hint('ARS'),
                Select::make('target_station')
                    ->options([
                        'kitchen' => 'Cocina',
                        'bar' => 'Barra',
                    ])
                    ->default('kitchen')
                    ->required(),
                Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto'),
                TextColumn::make('quantity')
                    ->label('Cant.')
                    ->suffix('x'),
                TextColumn::make('unit_price')
                    ->money(config('deco.currency', 'ARS'))
                    ->label('Precio'),
                TextColumn::make('target_station')
                    ->label('Estación')
                    ->formatStateUsing(fn ($state) => $state === 'kitchen' ? 'Cocina' : 'Barra'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(function ($state): ?string {
                        if ($state instanceof OrderItemStatus) {
                            return $state->label();
                        }
                        if (is_string($state)) {
                            return OrderItemStatus::tryFrom($state)?->label() ?? $state;
                        }

                        return null;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['status'] = OrderItemStatus::Pending;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
