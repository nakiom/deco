<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\OrderItemSplitMode;
use App\Enums\OrderItemStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Services\TableQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class MenuOrderController extends Controller
{
    public function __construct(
        private readonly TableQrService $tableQr,
    ) {}

    public function submitCart(Request $request, string $qrUuid, string $secret): JsonResponse
    {
        return $this->submitCartForTable($request, $this->tableQr->resolveByUuidAndSecret($qrUuid, $secret));
    }

    public function submitCartLegacy(Request $request, string $legacyToken): JsonResponse
    {
        return $this->submitCartForTable($request, $this->tableQr->resolveByLegacyToken($legacyToken));
    }

    private function submitCartForTable(Request $request, ?RestaurantTable $table): JsonResponse
    {
        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        if (! $table->menu_public_ordering_enabled) {
            return response()->json([
                'message' => 'Los pedidos desde la carta no están habilitados para esta mesa. Pedile al mozo que lo active.',
            ], 403);
        }

        $restaurant = $table->restaurant;
        if ($restaurant === null || ! $restaurant->is_active) {
            abort(404);
        }

        $key = 'menu-order-submit:'.$table->id;
        if (RateLimiter::tooManyAttempts($key, 12)) {
            return response()->json(['message' => 'Demasiados envíos. Esperá un momento.'], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'lines.*.split_mode' => ['required', 'string', 'in:individual,shared_equal'],
            'lines.*.participant_label' => ['nullable', 'string', 'max:120'],
            'lines.*.shared_with_labels' => ['nullable', 'array', 'max:20'],
            'lines.*.shared_with_labels.*' => ['string', 'max:120'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $normalized = [];
        foreach ($validated['lines'] as $i => $line) {
            $splitMode = OrderItemSplitMode::from($line['split_mode']);
            $participantLabel = trim((string) ($line['participant_label'] ?? ''));

            if ($splitMode === OrderItemSplitMode::Individual && $participantLabel === '') {
                return response()->json([
                    'message' => 'En la línea '.($i + 1).' falta el nombre para el consumo.',
                ], 422);
            }

            $shared = [];
            if ($splitMode === OrderItemSplitMode::SharedEqual) {
                $shared = array_values(array_unique(array_filter(array_map(
                    static fn ($s): string => trim((string) $s),
                    $line['shared_with_labels'] ?? []
                ), static fn (string $s): bool => $s !== '')));

                if (count($shared) < 2) {
                    return response()->json([
                        'message' => 'En la línea '.($i + 1).': para compartir, indicá al menos dos nombres.',
                    ], 422);
                }
            }

            $userNotes = trim((string) ($line['notes'] ?? ''));
            $parts = [];
            if ($splitMode === OrderItemSplitMode::SharedEqual && $participantLabel !== '') {
                $parts[] = 'Pedido por: '.$participantLabel;
            }
            if ($userNotes !== '') {
                $parts[] = $userNotes;
            }
            $notes = $parts !== [] ? implode("\n\n", $parts) : null;

            $normalized[] = [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
                'split_mode' => $splitMode,
                'participant_label' => $splitMode === OrderItemSplitMode::Individual ? $participantLabel : null,
                'shared_with_labels' => $splitMode === OrderItemSplitMode::SharedEqual ? $shared : null,
                'notes' => $notes,
            ];
        }

        $qtyByProduct = [];
        foreach ($normalized as $line) {
            $pid = $line['product_id'];
            $qtyByProduct[$pid] = ($qtyByProduct[$pid] ?? 0) + $line['quantity'];
        }

        try {
            $payload = DB::transaction(function () use ($table, $restaurant, $normalized, $qtyByProduct) {
                $productIds = array_keys($qtyByProduct);
                $products = Product::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($qtyByProduct as $pid => $needQty) {
                    $product = $products->get($pid);
                    if (! $product || $product->status !== ProductStatus::Available) {
                        throw new \RuntimeException('Un producto ya no está disponible.');
                    }
                    if ($product->stock_control && $product->current_stock < $needQty) {
                        throw new \RuntimeException('Stock insuficiente para '.$product->name.'.');
                    }
                }

                $order = Order::query()
                    ->where('table_id', $table->id)
                    ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($order === null) {
                    $order = Order::query()->create([
                        'restaurant_id' => $restaurant->id,
                        'source' => OrderSource::QrTable,
                        'table_id' => $table->id,
                        'customer_id' => null,
                        'waiter_id' => null,
                        'status' => OrderStatus::Pending,
                        'subtotal' => 0,
                        'total' => 0,
                        'notes' => null,
                        'opened_at' => now(),
                    ]);
                }

                foreach ($normalized as $line) {
                    $product = $products->get($line['product_id']);
                    if (! $product) {
                        throw new \RuntimeException('Producto inválido.');
                    }

                    $unit = (float) $product->price;
                    $sm = $line['split_mode'];

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $unit,
                        'notes' => $line['notes'],
                        'participant_label' => $sm === OrderItemSplitMode::Individual ? $line['participant_label'] : null,
                        'split_mode' => $sm->value,
                        'shared_with_labels' => $sm === OrderItemSplitMode::SharedEqual ? $line['shared_with_labels'] : null,
                        'target_station' => $this->targetStationForProduct($product),
                        'status' => OrderItemStatus::Pending,
                    ]);
                }

                $order->refresh();
                $this->refreshOrderTotals($order);

                if ($table->status === TableStatus::Free) {
                    $table->update(['status' => TableStatus::Occupied]);
                }

                return [
                    'order_id' => $order->id,
                    'lines_count' => count($normalized),
                ];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo enviar el pedido.'], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Pedido enviado a cocina y barra.',
            ...$payload,
        ]);
    }

    private function refreshOrderTotals(Order $order): void
    {
        $subtotal = (float) OrderItem::query()
            ->where('order_id', $order->id)
            ->get()
            ->sum(fn (OrderItem $i): float => (float) $i->unit_price * (int) $i->quantity);
        $discount = (float) ($order->discount_amount ?? 0);
        $order->update([
            'subtotal' => round($subtotal, 2),
            'total' => round(max(0, $subtotal - $discount), 2),
        ]);
    }

    private function targetStationForProduct(Product $product): string
    {
        return match ($product->item_type) {
            ItemType::Bar => 'bar',
            ItemType::Kitchen, ItemType::Mixed => 'kitchen',
        };
    }
}
