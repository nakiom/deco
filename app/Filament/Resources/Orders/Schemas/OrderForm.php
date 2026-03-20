<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\RestaurantTable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $restaurantId = auth()->user()?->restaurant_id ?? 1;

        return $schema
            ->components([
                Hidden::make('restaurant_id')
                    ->default($restaurantId),
                Select::make('table_id')
                    ->label('Mesa')
                    ->options(function () use ($restaurantId) {
                        return RestaurantTable::query()
                            ->where('restaurant_id', $restaurantId)
                            ->orderBy('number')
                            ->get()
                            ->mapWithKeys(fn (RestaurantTable $t) => [$t->id => "Mesa {$t->number}"])
                            ->all();
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) use ($restaurantId) {
                        return RestaurantTable::query()
                            ->where('restaurant_id', $restaurantId)
                            ->where(function ($q) use ($search) {
                                $q->where('number', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            })
                            ->orderBy('number')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (RestaurantTable $t) => [$t->id => "Mesa {$t->number}"])
                            ->all();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (blank($value)) {
                            return null;
                        }
                        $t = RestaurantTable::query()->find($value);

                        return $t ? "Mesa {$t->number}" : null;
                    })
                    ->preload(),
                Select::make('customer_id')
                    ->label('Cliente')
                    ->options(function () {
                        return Customer::query()
                            ->orderBy('name')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn (Customer $c) => [
                                $c->id => $c->name ?: $c->email ?: $c->phone ?: "Cliente #{$c->id}",
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Customer::query()
                            ->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orderBy('name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Customer $c) => [
                                $c->id => $c->name ?: $c->email ?: $c->phone ?: "Cliente #{$c->id}",
                            ])
                            ->all();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (blank($value)) {
                            return null;
                        }
                        $c = Customer::query()->find($value);

                        return $c ? ($c->name ?: $c->email ?: $c->phone ?: "Cliente #{$c->id}") : null;
                    })
                    ->preload(),
                Hidden::make('waiter_id')
                    ->default(fn () => auth()->id()),
                Select::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])->all())
                    ->default('pending')
                    ->required(),
                TextInput::make('subtotal')
                    ->numeric()
                    ->default(0)
                    ->hiddenOn('create'),
                TextInput::make('total')
                    ->numeric()
                    ->default(0)
                    ->hiddenOn('create'),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
                DateTimePicker::make('opened_at')
                    ->hiddenOn('create'),
                DateTimePicker::make('sent_at')
                    ->hiddenOn('create'),
                DateTimePicker::make('closed_at')
                    ->hiddenOn('create'),
            ]);
    }
}
