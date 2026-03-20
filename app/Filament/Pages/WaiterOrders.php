<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WaiterOrders extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $title = 'Pedidos del salón';

    protected static ?int $navigationSort = 1;

    /**
     * Usar todo el ancho del área principal (por defecto Filament limita a ~7xl).
     */
    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?string $subheading = 'Pedidos abiertos, llamados de mesa y prioridad cocina — se actualiza solo cada pocos segundos.';

    protected string $view = 'filament.pages.waiter-orders';

    /** @var 'mine'|'salon' */
    public string $tab = 'mine';

    /** @var 'all'|'urgent'|'pickup' */
    public string $filterPriority = 'all';

    public string $search = '';

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        if (! $u?->can('orders.view')) {
            return false;
        }

        return $u->hasAnyRole([
            Role::Waiter->value,
            Role::Manager->value,
            Role::Owner->value,
            Role::Dev->value,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user?->restaurant_id) {
            return null;
        }

        $n = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
            ->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pedidos abiertos en el restaurante';
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('newOrder')
                ->label('Nuevo pedido')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->url(OrderResource::getUrl('create'))
                ->visible(fn () => auth()->user()?->can('orders.create')),
            Action::make('map')
                ->label('Mapa operativo')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(SalonOperations::getUrl())
                ->visible(fn () => auth()->user()?->can('tables.view')),
            Action::make('kitchen')
                ->label('Cocina')
                ->icon(Heroicon::OutlinedFire)
                ->url(KitchenQueue::getUrl())
                ->visible(fn () => auth()->user()?->can('kitchen.view')),
            Action::make('bar')
                ->label('Barra')
                ->icon(Heroicon::OutlinedBeaker)
                ->url(BarQueue::getUrl())
                ->visible(fn () => auth()->user()?->can('bar.view')),
        ];
    }

    protected function openOrdersQuery(): Builder
    {
        $q = Order::query()
            ->with(['table', 'waiter', 'items.product'])
            ->whereNotIn('status', [
                OrderStatus::Closed,
                OrderStatus::Cancelled,
            ])
            ->orderByDesc('opened_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $rid = auth()->user()?->restaurant_id;
        if ($rid) {
            $q->where('restaurant_id', $rid);
        }

        return $q;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getMyOpenOrders(): Collection
    {
        $orders = $this->openOrdersQuery()
            ->where('waiter_id', auth()->id())
            ->get();

        return $this->applyFilters($orders);
    }

    /**
     * @return Collection<int, Order>
     */
    public function getAllOpenOrders(): Collection
    {
        $orders = $this->openOrdersQuery()->get();

        return $this->applyFilters($orders);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, Order>
     */
    protected function applyFilters(Collection $orders): Collection
    {
        $s = trim($this->search);
        if ($s !== '') {
            $orders = $orders->filter(function (Order $order) use ($s): bool {
                $num = (string) ($order->table?->number ?? '');
                $name = (string) ($order->table?->name ?? '');
                $display = (string) ($order->table?->display_name ?? '');

                return Str::contains(Str::lower($num), Str::lower($s))
                    || Str::contains(Str::lower($name), Str::lower($s))
                    || Str::contains(Str::lower($display), Str::lower($s));
            });
        }

        return (match ($this->filterPriority) {
            'urgent' => $orders->filter(fn (Order $o): bool => $this->isUrgent($o)),
            'pickup' => $orders->filter(fn (Order $o): bool => $this->needsPickup($o)),
            default => $orders,
        })->values();
    }

    protected function isUrgent(Order $order): bool
    {
        return $order->kitchen_completed_at !== null
            || $order->status === OrderStatus::KitchenDone;
    }

    protected function needsPickup(Order $order): bool
    {
        return $order->items->contains(
            fn (OrderItem $i): bool => $i->status === OrderItemStatus::Ready
        );
    }

    /**
     * @return array{mine: int, salon: int, urgent: int, pickup: int, calls: int}
     */
    public function getSalonStats(): array
    {
        $rid = auth()->user()?->restaurant_id;
        $base = $this->openOrdersQuery()->get();

        $mine = $base->where('waiter_id', auth()->id());
        $calls = 0;
        if ($rid) {
            $calls = (int) RestaurantTable::query()
                ->where('restaurant_id', $rid)
                ->whereNotNull('waiter_call_at')
                ->count();
        }

        return [
            'mine' => $mine->count(),
            'salon' => $base->count(),
            'urgent' => $base->filter(fn (Order $o): bool => $this->isUrgent($o))->count(),
            'pickup' => $base->filter(fn (Order $o): bool => $this->needsPickup($o))->count(),
            'calls' => $calls,
        ];
    }

    /**
     * Mesas con llamado pendiente (más reciente primero).
     *
     * @return \Illuminate\Support\Collection<int, RestaurantTable>
     */
    public function getPendingTableCalls()
    {
        $rid = auth()->user()?->restaurant_id;
        if (! $rid) {
            return collect();
        }

        return RestaurantTable::query()
            ->where('restaurant_id', $rid)
            ->whereNotNull('waiter_call_at')
            ->orderByDesc('waiter_call_at')
            ->limit(25)
            ->get();
    }

    public function takeOrder(int $orderId): void
    {
        abort_unless(auth()->user()?->can('orders.update'), 403);

        $order = Order::query()
            ->whereKey($orderId)
            ->where('restaurant_id', auth()->user()?->restaurant_id)
            ->first();

        if (! $order) {
            Notification::make()->title('Pedido no encontrado')->warning()->send();

            return;
        }

        $order->update(['waiter_id' => auth()->id()]);

        Notification::make()
            ->title('Pedido asignado')
            ->body('Quedó asignado a vos.')
            ->success()
            ->send();
    }

    public function deliverReadyItems(int $orderId): void
    {
        abort_unless(auth()->user()?->can('orders.update'), 403);

        $order = Order::query()
            ->with('items')
            ->whereKey($orderId)
            ->where('restaurant_id', auth()->user()?->restaurant_id)
            ->first();

        if (! $order) {
            Notification::make()->title('Pedido no encontrado')->warning()->send();

            return;
        }

        $n = 0;
        DB::transaction(function () use ($order, &$n): void {
            foreach ($order->items as $item) {
                if ($item->status === OrderItemStatus::Ready) {
                    $item->update([
                        'status' => OrderItemStatus::Delivered,
                        'delivered_at' => now(),
                    ]);
                    $n++;
                }
            }
        });

        if ($n === 0) {
            Notification::make()
                ->title('Nada para entregar')
                ->body('No hay ítems en estado “listo” en este pedido.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Entrega registrada')
            ->body($n === 1 ? '1 ítem marcado como entregado.' : "{$n} ítems marcados como entregados.")
            ->success()
            ->send();
    }

    public function dismissTableCall(int $tableId): void
    {
        abort_unless(auth()->user()?->can('tables.update'), 403);

        $rid = auth()->user()?->restaurant_id;
        if (! $rid) {
            return;
        }

        RestaurantTable::query()
            ->where('restaurant_id', $rid)
            ->whereKey($tableId)
            ->update(['waiter_call_at' => null]);

        Notification::make()
            ->title('Llamado atendido')
            ->success()
            ->send();
    }
}
