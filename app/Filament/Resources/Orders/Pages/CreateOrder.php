<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\ItemType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\TableStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Http\Controllers\CartaController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class CreateOrder extends Page
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'Nuevo pedido';

    protected string $view = 'filament.resources.orders.pages.create-order-touch';

    /** @var array<int, array{qty: int, notes: string}> */
    public array $cart = [];

    #[Url(as: 'table_id')]
    public ?int $tableId = null;

    public function mount(): void
    {
        abort_unless(static::getResource()::canAccess(), 403);
        abort_unless(auth()->user()?->can('orders.create'), 403);

        if (! $this->resolveRestaurant()) {
            Notification::make()->danger()->title('Sin restaurante asignado')->send();
            $this->redirect(OrderResource::getUrl('index'));
        }
    }

    protected function resolveRestaurant(): ?Restaurant
    {
        $rid = auth()->user()?->restaurant_id;

        return $rid ? Restaurant::query()->find($rid) : null;
    }

    public function getCategoriesProperty(): Collection
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return collect();
        }

        return app(CartaController::class)->categoriesForRestaurant($restaurant);
    }

    public function getTablesProperty(): Collection
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return collect();
        }

        return RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('number')
            ->get();
    }

    public function selectTable(int $id): void
    {
        $this->tableId = $id;
    }

    public function clearTableSelection(): void
    {
        $this->tableId = null;
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->find($productId);
        if (! $product || ! $this->canAddProduct($product)) {
            Notification::make()->warning()->title('No disponible')->body('Sin stock o producto no disponible.')->send();

            return;
        }

        $current = (int) ($this->cart[$productId]['qty'] ?? 0);
        $max = $this->maxQtyForProduct($product);
        if ($current + 1 > $max) {
            Notification::make()->warning()->title('Stock insuficiente')->send();

            return;
        }

        $this->cart[$productId] = [
            'qty' => $current + 1,
            'notes' => $this->cart[$productId]['notes'] ?? '',
        ];
    }

    public function removeProduct(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['qty']--;
        if ($this->cart[$productId]['qty'] < 1) {
            unset($this->cart[$productId]);
        }
    }

    public function getCartLineCountProperty(): int
    {
        return (int) collect($this->cart)->sum(fn (array $row) => (int) ($row['qty'] ?? 0));
    }

    public function getCartTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->cart as $productId => $row) {
            $p = Product::query()->find((int) $productId);
            if ($p) {
                $total += (float) $p->price * (int) ($row['qty'] ?? 0);
            }
        }

        return $total;
    }

    public function submitOrder(): void
    {
        if ($this->cart === []) {
            Notification::make()->warning()->title('Agregá al menos un producto')->send();

            return;
        }

        if ($this->tableId === null) {
            Notification::make()->warning()->title('Elegí una mesa')->send();

            return;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return;
        }

        $table = RestaurantTable::query()
            ->where('id', $this->tableId)
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if (! $table) {
            Notification::make()->danger()->title('Mesa inválida')->send();

            return;
        }

        try {
            $order = DB::transaction(function () use ($restaurant, $table) {
                $order = Order::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'source' => OrderSource::Staff,
                    'table_id' => $table->id,
                    'customer_id' => null,
                    'waiter_id' => auth()->id(),
                    'status' => OrderStatus::Pending,
                    'subtotal' => 0,
                    'total' => 0,
                    'notes' => null,
                    'opened_at' => now(),
                ]);

                $subtotal = 0.0;
                foreach ($this->cart as $productId => $row) {
                    $pid = (int) $productId;
                    $qty = (int) ($row['qty'] ?? 0);
                    if ($qty < 1) {
                        continue;
                    }

                    $product = Product::query()->whereKey($pid)->lockForUpdate()->first();
                    if (! $product || $product->status !== ProductStatus::Available) {
                        throw new \RuntimeException('Producto no disponible.');
                    }
                    if ($product->stock_control && $product->current_stock < $qty) {
                        throw new \RuntimeException('Stock insuficiente para '.$product->name);
                    }

                    $unit = (float) $product->price;
                    $line = $unit * $qty;
                    $subtotal += $line;

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'notes' => $row['notes'] !== '' ? $row['notes'] : null,
                        'target_station' => $this->targetStationForProduct($product),
                        'status' => OrderItemStatus::Pending,
                    ]);
                }

                $order->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]);

                $table->update(['status' => TableStatus::Occupied]);

                return $order->fresh();
            });

            Notification::make()->success()->title('Pedido creado')->send();

            $this->redirect(OrderResource::getUrl('index'));
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->danger()->title('No se pudo crear el pedido')->body($e->getMessage())->send();
        }
    }

    protected function canAddProduct(Product $product): bool
    {
        if ($product->status !== ProductStatus::Available) {
            return false;
        }
        if ($product->stock_control && $product->current_stock < 1) {
            return false;
        }

        return true;
    }

    protected function maxQtyForProduct(Product $product): int
    {
        if (! $product->stock_control) {
            return 999;
        }

        return max(0, (int) $product->current_stock);
    }

    protected function targetStationForProduct(Product $product): string
    {
        return match ($product->item_type) {
            ItemType::Bar => 'bar',
            ItemType::Kitchen, ItemType::Mixed => 'kitchen',
        };
    }

    public function stockLabel(Product $product): string
    {
        if (! $product->stock_control) {
            return 'Stock: —';
        }

        return 'Stock: '.$product->current_stock;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Lista de pedidos')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
