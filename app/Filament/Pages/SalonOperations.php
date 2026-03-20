<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\OrderItemSplitMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableStatus;
use App\Models\FloorPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\SplitBillSummary;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SalonOperations extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Mapa Operativo';

    protected static ?string $title = 'Mapa Operativo (Tiempo Real)';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.salon-operations';

    public ?int $selectedTableId = null;

    public string $billDiscount = '0';

    public ?string $billPaymentMethod = null;

    public bool $menuPublicOrderingEnabled = false;

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('tables.view');
    }

    public function selectTable(int $tableId): void
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return;
        }

        $exists = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($tableId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedTableId = $tableId;
        $this->billDiscount = '0';
        $this->billPaymentMethod = null;

        $t = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($tableId)
            ->first();
        $this->menuPublicOrderingEnabled = (bool) ($t?->menu_public_ordering_enabled ?? false);
    }

    public function closeBillPanel(): void
    {
        $this->selectedTableId = null;
        $this->menuPublicOrderingEnabled = false;
    }

    public function updatedMenuPublicOrderingEnabled(mixed $value): void
    {
        if (! auth()->user()?->can('tables.update')) {
            return;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! $this->selectedTableId) {
            return;
        }

        RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($this->selectedTableId)
            ->update(['menu_public_ordering_enabled' => (bool) $value]);
    }

    /**
     * Marca la mesa como libre (sin pedidos abiertos o para corregir estado).
     */
    public function freeTableOnly(): void
    {
        if (! auth()->user()?->can('tables.update')) {
            abort(403);
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! $this->selectedTableId) {
            return;
        }

        $table = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($this->selectedTableId)
            ->first();

        if (! $table) {
            return;
        }

        $open = Order::query()
            ->where('table_id', $table->id)
            ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
            ->exists();

        if ($open) {
            Notification::make()
                ->title('Hay pedidos abiertos')
                ->body('Cerrá la cuenta antes de liberar la mesa.')
                ->warning()
                ->send();

            return;
        }

        $table->update(['status' => TableStatus::Free]);
        Notification::make()->title('Mesa liberada')->success()->send();
        $this->closeBillPanel();
    }

    public function submitBill(): void
    {
        if (! auth()->user()?->can('orders.update')) {
            abort(403);
        }

        $this->validate([
            'billDiscount' => ['nullable', 'numeric', 'min:0'],
            'billPaymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
        ]);

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! $this->selectedTableId) {
            return;
        }

        $discount = round((float) ($this->billDiscount ?: 0), 2);
        $payment = PaymentMethod::from((string) $this->billPaymentMethod);

        $table = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($this->selectedTableId)
            ->first();

        if (! $table) {
            return;
        }

        try {
            $outcome = DB::transaction(function () use ($discount, $payment, $table): string {
                $orders = Order::query()
                    ->where('table_id', $table->id)
                    ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($orders->isEmpty()) {
                    return 'empty';
                }

                $subtotalSum = (float) $orders->sum(fn (Order $o): float => (float) $o->subtotal);

                if ($discount > $subtotalSum + 0.0001) {
                    return 'bad_discount';
                }

                $byOrderDiscount = $this->splitDiscountAcrossOrders($orders, $discount);

                foreach ($orders as $order) {
                    $d = $byOrderDiscount[$order->id] ?? 0.0;
                    $newTotal = max(0.0, round((float) $order->subtotal - $d, 2));

                    $order->update([
                        'discount_amount' => round($d, 2),
                        'payment_method' => $payment,
                        'total' => $newTotal,
                        'status' => OrderStatus::Closed,
                        'closed_at' => now(),
                    ]);
                }

                $table->update(['status' => TableStatus::Free]);

                return 'ok';
            });
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('No se pudo registrar el cobro')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($outcome === 'empty') {
            Notification::make()
                ->title('Sin cuenta abierta')
                ->body('No hay consumos para cobrar en esta mesa.')
                ->warning()
                ->send();

            return;
        }

        if ($outcome === 'bad_discount') {
            Notification::make()
                ->title('Descuento inválido')
                ->body('El descuento no puede superar el subtotal.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Cuenta facturada')
            ->body('Comprobante registrado. Mesa liberada.')
            ->success()
            ->send();

        $this->closeBillPanel();
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, float>
     */
    private function splitDiscountAcrossOrders(Collection $orders, float $totalDiscount): array
    {
        if ($orders->isEmpty() || $totalDiscount <= 0) {
            return $orders->mapWithKeys(fn (Order $o): array => [$o->id => 0.0])->all();
        }

        $totalSub = (float) $orders->sum(fn (Order $o): float => (float) $o->subtotal);
        if ($totalSub <= 0) {
            return $orders->mapWithKeys(fn (Order $o): array => [$o->id => 0.0])->all();
        }

        $out = [];
        $allocated = 0.0;
        $list = $orders->values();
        $last = $list->count() - 1;

        foreach ($list as $i => $order) {
            if ($i === $last) {
                $out[$order->id] = round($totalDiscount - $allocated, 2);
            } else {
                $share = round($totalDiscount * ((float) $order->subtotal / $totalSub), 2);
                $out[$order->id] = $share;
                $allocated += $share;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSelectedTableBillPayload(): ?array
    {
        if (! $this->selectedTableId) {
            return null;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return null;
        }

        $table = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereKey($this->selectedTableId)
            ->first();

        if (! $table) {
            return null;
        }

        $orders = Order::query()
            ->where('table_id', $table->id)
            ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
            ->with(['items.product'])
            ->orderBy('id')
            ->get();

        $lines = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $notes = $item->notes !== null && $item->notes !== '' ? trim((string) $item->notes) : null;
                $lines[] = [
                    'product_name' => $item->product?->name ?? '—',
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => round((float) $item->unit_price * (int) $item->quantity, 2),
                    'participant_line' => $this->lineParticipantSummary($item),
                    'line_notes' => $notes,
                ];
            }
        }

        $subtotal = (float) $orders->sum(fn (Order $o): float => (float) $o->subtotal);

        $participantSplit = SplitBillSummary::totalsByParticipant($orders);

        return [
            'table' => [
                'id' => $table->id,
                'number' => (int) $table->number,
                'name' => $table->name,
                'status' => $table->status->value,
                'status_label' => $table->status->label(),
            ],
            'orders_count' => $orders->count(),
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'has_open_orders' => $orders->isNotEmpty(),
            'menu_public_ordering_enabled' => (bool) $table->menu_public_ordering_enabled,
            'participant_split' => $participantSplit,
            'participant_split_sum' => SplitBillSummary::sumTotals($participantSplit),
        ];
    }

    private function lineParticipantSummary(OrderItem $item): string
    {
        $mode = $item->split_mode instanceof OrderItemSplitMode
            ? $item->split_mode
            : OrderItemSplitMode::tryFrom((string) $item->split_mode) ?? OrderItemSplitMode::Individual;

        if ($mode === OrderItemSplitMode::SharedEqual) {
            $names = is_array($item->shared_with_labels) ? $item->shared_with_labels : [];

            return 'Compartido: '.implode(', ', array_map(static fn ($n): string => (string) $n, $names));
        }

        return $item->participant_label !== null && $item->participant_label !== ''
            ? (string) $item->participant_label
            : 'Sin asignar';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationalPayload(): array
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return [
                'restaurant' => null,
                'floor' => null,
                'tables' => [],
            ];
        }

        if (! $this->supportsFloorPlanVersioning()) {
            $tables = RestaurantTable::query()
                ->where('restaurant_id', $restaurant->id)
                ->orderBy('number')
                ->get();

            $extras = $this->openOrderTotalsByTable($tables->pluck('id')->all());

            return [
                'restaurant' => ['id' => $restaurant->id, 'name' => $restaurant->name],
                'floor' => [
                    'id' => null,
                    'name' => 'Salón principal',
                    'version' => 1,
                    'width' => 1000,
                    'height' => 640,
                ],
                'tables' => $tables->map(fn (RestaurantTable $table): array => $this->tablePayloadRow($table, $extras))->all(),
            ];
        }

        $floorPlan = FloorPlan::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->first();

        if (! $floorPlan) {
            return [
                'restaurant' => ['id' => $restaurant->id, 'name' => $restaurant->name],
                'floor' => null,
                'tables' => [],
            ];
        }

        $tables = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('floor_plan_id', $floorPlan->id)
            ->orderBy('number')
            ->get();

        $extras = $this->openOrderTotalsByTable($tables->pluck('id')->all());

        return [
            'restaurant' => ['id' => $restaurant->id, 'name' => $restaurant->name],
            'floor' => [
                'id' => $floorPlan->id,
                'name' => $floorPlan->name,
                'version' => $floorPlan->version,
                'width' => $floorPlan->width,
                'height' => $floorPlan->height,
            ],
            'tables' => $tables->map(fn (RestaurantTable $table): array => $this->tablePayloadRow($table, $extras))->all(),
        ];
    }

    /**
     * @param  array<int, array{open_total: float, open_count: int}>  $extras
     * @return array<string, mixed>
     */
    private function tablePayloadRow(RestaurantTable $table, array $extras): array
    {
        $e = $extras[$table->id] ?? ['open_total' => 0.0, 'open_count' => 0];

        return [
            'id' => $table->id,
            'number' => (int) $table->number,
            'capacity' => (int) $table->capacity,
            'shape' => $table->shape,
            'status' => $table->status->value,
            'status_label' => $table->status->label(),
            'x' => (float) $table->pos_x,
            'y' => (float) $table->pos_y,
            'width' => (float) $table->width,
            'height' => (float) $table->height,
            'rotation' => (float) ($table->rotation ?? 0),
            'open_total' => $e['open_total'],
            'open_count' => $e['open_count'],
        ];
    }

    /**
     * @param  array<int>  $tableIds
     * @return array<int, array{open_total: float, open_count: int}>
     */
    private function openOrderTotalsByTable(array $tableIds): array
    {
        if ($tableIds === []) {
            return [];
        }

        $rows = Order::query()
            ->whereIn('table_id', $tableIds)
            ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
            ->selectRaw('table_id, SUM(subtotal) as open_total, COUNT(*) as open_count')
            ->groupBy('table_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $tid = (int) $row->table_id;
            $out[$tid] = [
                'open_total' => round((float) $row->open_total, 2),
                'open_count' => (int) $row->open_count,
            ];
        }

        return $out;
    }

    private function resolveRestaurant(): ?Restaurant
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->restaurant_id) {
            return Restaurant::find($user->restaurant_id);
        }

        if ($user->hasRole('dev')) {
            return Restaurant::query()->oldest('id')->first();
        }

        return null;
    }

    private function supportsFloorPlanVersioning(): bool
    {
        return Schema::hasTable('floor_plans')
            && Schema::hasTable('restaurant_tables')
            && Schema::hasColumn('restaurant_tables', 'floor_plan_id');
    }
}
