<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Concerns\DeductsStockWhenOrderItemReady;
use App\Models\KitchenQueueAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\KitchenOrderReadyForWaiter;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class KitchenQueue extends Page
{
    use DeductsStockWhenOrderItemReady;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Cocina';

    protected static ?string $title = 'Cola de Cocina';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.kitchen-queue';

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('kitchen.view') ?? false;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getKitchenItems()
    {
        $restaurantId = auth()->user()?->restaurant_id;
        $query = OrderItem::with(['order.table', 'product'])
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Sent->value,
                OrderStatus::InProgress->value,
                OrderStatus::KitchenDone->value,
            ]))
            ->where('target_station', 'kitchen')
            ->whereIn('status', [
                OrderItemStatus::Pending->value,
                OrderItemStatus::Sent->value,
                OrderItemStatus::Preparing->value,
            ]);

        if ($restaurantId) {
            $query->whereHas('order', fn (Builder $q) => $q->where('restaurant_id', $restaurantId));
        }

        return $query->orderBy('created_at')->get();
    }

    /**
     * Historial reciente (acciones no deshechas).
     *
     * @return Collection<int, KitchenQueueAction>
     */
    public function getRecentHistory()
    {
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return KitchenQueueAction::query()->whereRaw('1 = 0')->get();
        }

        return KitchenQueueAction::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('undone_at')
            ->whereHas('orderItem', fn (Builder $q) => $q->where('target_station', 'kitchen'))
            ->with(['orderItem.product', 'orderItem.order.table', 'user'])
            ->orderByDesc('id')
            ->limit(30)
            ->get();
    }

    public function markReady(int $orderItemId): void
    {
        $user = auth()->user();
        abort_unless($user?->can('kitchen.update'), 403);

        $item = OrderItem::query()
            ->with(['order.restaurant', 'order.table', 'order.customer', 'product'])
            ->findOrFail($orderItemId);

        if ($item->target_station !== 'kitchen') {
            FilamentNotification::make()->warning()->title('No es ítem de cocina')->send();

            return;
        }

        if (! in_array($item->status, [OrderItemStatus::Pending, OrderItemStatus::Sent, OrderItemStatus::Preparing], true)) {
            FilamentNotification::make()->warning()->title('Este plato ya no está en cola')->send();

            return;
        }

        $order = $item->order;
        if (! $order || ($user->restaurant_id && $order->restaurant_id !== $user->restaurant_id)) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($item, $user, $order): void {
                $this->deductStockWhenMarkingReady($item);

                $from = $item->status;
                $item->update([
                    'status' => OrderItemStatus::Ready,
                    'ready_at' => now(),
                ]);

                KitchenQueueAction::query()->create([
                    'order_item_id' => $item->id,
                    'user_id' => $user->id,
                    'restaurant_id' => $order->restaurant_id,
                    'from_status' => $from->value,
                    'to_status' => OrderItemStatus::Ready->value,
                ]);

                $this->syncOrderKitchenCompleted($order->fresh(['items']));
            });
        } catch (Throwable $e) {
            report($e);
            FilamentNotification::make()
                ->danger()
                ->title('No se pudo marcar como listo')
                ->body($e->getMessage())
                ->send();

            return;
        }

        FilamentNotification::make()
            ->success()
            ->title('Marcado como listo')
            ->body($item->product?->name ?? 'Plato')
            ->send();
    }

    public function undoLast(): void
    {
        $user = auth()->user();
        abort_unless($user?->can('kitchen.update'), 403);

        $restaurantId = $user->restaurant_id;
        if (! $restaurantId) {
            FilamentNotification::make()->warning()->title('Sin restaurante asignado')->send();

            return;
        }

        $action = KitchenQueueAction::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('undone_at')
            ->whereHas('orderItem', fn (Builder $q) => $q->where('target_station', 'kitchen'))
            ->orderByDesc('id')
            ->first();

        if (! $action) {
            FilamentNotification::make()->warning()->title('No hay acciones para deshacer')->send();

            return;
        }

        DB::transaction(function () use ($action): void {
            $item = OrderItem::query()->with('order.items')->find($action->order_item_id);
            if (! $item) {
                $action->update(['undone_at' => now()]);

                return;
            }

            if ($action->to_status === OrderItemStatus::Ready->value) {
                $this->restoreStockWhenUndoingReady($item);
            }

            $from = OrderItemStatus::from($action->from_status);
            $item->update([
                'status' => $from,
                'ready_at' => in_array($from, [OrderItemStatus::Ready, OrderItemStatus::Delivered], true)
                    ? $item->ready_at
                    : null,
            ]);

            $action->update(['undone_at' => now()]);

            $order = $item->order;
            if ($order) {
                $this->syncOrderKitchenCompleted($order->fresh(['items']));
            }
        });

        FilamentNotification::make()
            ->success()
            ->title('Acción deshecha')
            ->send();
    }

    protected function syncOrderKitchenCompleted(Order $order): void
    {
        $kitchenItems = $order->items->where('target_station', 'kitchen');
        if ($kitchenItems->isEmpty()) {
            $updates = ['kitchen_completed_at' => null];
            if ($order->status === OrderStatus::KitchenDone) {
                $updates['status'] = OrderStatus::InProgress;
            }
            $order->update($updates);

            return;
        }

        $allDone = $kitchenItems->every(function (OrderItem $i): bool {
            return in_array($i->status, [
                OrderItemStatus::Ready,
                OrderItemStatus::Delivered,
                OrderItemStatus::Cancelled,
            ], true);
        });

        if (! $allDone) {
            $updates = ['kitchen_completed_at' => null];
            if ($order->status === OrderStatus::KitchenDone) {
                $updates['status'] = OrderStatus::InProgress;
            }
            $order->update($updates);

            return;
        }

        if ($order->kitchen_completed_at !== null) {
            return;
        }

        $updates = ['kitchen_completed_at' => now()];
        if (! in_array($order->status, [OrderStatus::Closed, OrderStatus::Cancelled, OrderStatus::Delivered], true)) {
            $updates['status'] = OrderStatus::KitchenDone;
        }

        $order->update($updates);
        $this->notifyKitchenComplete($order->fresh(['restaurant', 'table', 'customer', 'waiter']));
    }

    protected function notifyKitchenComplete(Order $order): void
    {
        $table = $order->table;
        $mesa = $table ? (string) ($table->display_name ?? $table->number) : '—';

        $waiters = collect();
        if ($order->waiter_id) {
            $w = User::query()->find($order->waiter_id);
            if ($w) {
                $waiters->push($w);
            }
        }
        if ($waiters->isEmpty()) {
            $waiters = User::role(Role::Waiter->value)
                ->where('restaurant_id', $order->restaurant_id)
                ->get();
        }

        foreach ($waiters->unique('id') as $waiter) {
            $waiter->notify(new KitchenOrderReadyForWaiter($mesa));
        }

        $customer = $order->customer;
        if ($customer?->email) {
            try {
                $restaurantName = $order->restaurant?->name ?? 'Restaurante';
                $email = $customer->email;
                Mail::raw(
                    "Tu pedido en la mesa {$mesa} está listo en cocina.\n\n".$restaurantName,
                    static function ($message) use ($email, $restaurantName): void {
                        $message->to($email)
                            ->subject('Pedido listo — '.$restaurantName);
                    }
                );
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
