<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderItemStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Notifications\KitchenOrderReadyForWaiter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOrdersForDemoCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'deco:clear-orders
                            {--force : Ejecutar sin confirmación}
                            {--no-stock : No restaurar stock de productos}
                            {--no-reset-tables : No marcar mesas como libres}
                            {--keep-notifications : No borrar notificaciones de “pedido listo en cocina”}';

    /**
     * @var string
     */
    protected $description = 'Elimina todos los pedidos (órdenes e ítems) para simulacros; opcionalmente restaura stock y deja mesas libres';

    /**
     * @var array<int, string>
     */
    protected $aliases = ['deco:simulacro-reset'];

    public function handle(): int
    {
        $count = Order::query()->count();
        if ($count === 0) {
            $this->info('No hay pedidos para borrar.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Se borrarán {$count} pedido(s) y sus líneas. ¿Continuar?", false)) {
            $this->warn('Cancelado.');

            return self::FAILURE;
        }

        $itemsCount = OrderItem::query()->count();

        DB::transaction(function (): void {
            if (! $this->option('no-stock')) {
                $items = OrderItem::query()->with('product')->get();
                foreach ($items as $item) {
                    $product = $item->product;
                    if ($product === null || ! $product->stock_control) {
                        continue;
                    }
                    // El stock se descuenta al marcar listo en cocina/barra, no al crear el pedido.
                    if (! in_array($item->status, [OrderItemStatus::Ready, OrderItemStatus::Delivered], true)) {
                        continue;
                    }
                    $product->increment('current_stock', $item->quantity);
                }
            }

            Order::query()->delete();

            if (! $this->option('keep-notifications')) {
                DB::table('notifications')
                    ->where('type', KitchenOrderReadyForWaiter::class)
                    ->delete();
            }

            if (! $this->option('no-reset-tables')) {
                RestaurantTable::query()->update([
                    'status' => TableStatus::Free,
                    'waiter_call_at' => null,
                ]);
            }
        });

        $this->info("Listo: {$count} pedido(s) y {$itemsCount} línea(s) eliminados.");
        if (! $this->option('no-reset-tables')) {
            $this->comment('Mesas marcadas como libres.');
        }
        if (! $this->option('no-stock')) {
            $this->comment('Stock restaurado según líneas borradas (productos con control de stock).');
        }

        return self::SUCCESS;
    }
}
