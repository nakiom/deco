<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Filament\Concerns\DeductsStockWhenOrderItemReady;
use App\Models\KitchenQueueAction;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BarQueue extends Page
{
    use DeductsStockWhenOrderItemReady;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Barra';

    protected static ?string $title = 'Cola de Barra';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.bar-queue';

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('bar.view') ?? false;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getBarItems()
    {
        $restaurantId = auth()->user()?->restaurant_id;
        $query = OrderItem::with(['order.table', 'product'])
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Sent->value,
                OrderStatus::InProgress->value,
                OrderStatus::KitchenDone->value,
            ]))
            ->where('target_station', 'bar')
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
     * @return Collection<int, KitchenQueueAction>
     */
    public function getRecentHistoryBar()
    {
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return KitchenQueueAction::query()->whereRaw('1 = 0')->get();
        }

        return KitchenQueueAction::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('undone_at')
            ->whereHas('orderItem', fn (Builder $q) => $q->where('target_station', 'bar'))
            ->with(['orderItem.product', 'orderItem.order.table', 'user'])
            ->orderByDesc('id')
            ->limit(30)
            ->get();
    }

    public function markReady(int $orderItemId): void
    {
        $user = auth()->user();
        abort_unless($user?->can('bar.update'), 403);

        $item = OrderItem::query()
            ->with(['order.restaurant', 'product'])
            ->findOrFail($orderItemId);

        if ($item->target_station !== 'bar') {
            FilamentNotification::make()->warning()->title('No es ítem de barra')->send();

            return;
        }

        if (! in_array($item->status, [OrderItemStatus::Pending, OrderItemStatus::Sent, OrderItemStatus::Preparing], true)) {
            FilamentNotification::make()->warning()->title('Este ítem ya no está en cola')->send();

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
            ->body($item->product?->name ?? 'Bebida')
            ->send();
    }

    public function undoLast(): void
    {
        $user = auth()->user();
        abort_unless($user?->can('bar.update'), 403);

        $restaurantId = $user->restaurant_id;
        if (! $restaurantId) {
            FilamentNotification::make()->warning()->title('Sin restaurante asignado')->send();

            return;
        }

        $action = KitchenQueueAction::query()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('undone_at')
            ->whereHas('orderItem', fn (Builder $q) => $q->where('target_station', 'bar'))
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
        });

        FilamentNotification::make()
            ->success()
            ->title('Acción deshecha')
            ->send();
    }
}
