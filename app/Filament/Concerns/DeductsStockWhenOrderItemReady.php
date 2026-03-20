<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\OrderItem;
use App\Models\Product;

trait DeductsStockWhenOrderItemReady
{
    protected function deductStockWhenMarkingReady(OrderItem $item): void
    {
        $product = Product::query()->lockForUpdate()->find($item->product_id);
        if ($product === null || ! $product->stock_control) {
            return;
        }

        if ($product->current_stock < $item->quantity) {
            throw new \RuntimeException('Stock insuficiente para '.$product->name);
        }

        $product->decrement('current_stock', $item->quantity);
    }

    protected function restoreStockWhenUndoingReady(OrderItem $item): void
    {
        $product = Product::query()->lockForUpdate()->find($item->product_id);
        if ($product === null || ! $product->stock_control) {
            return;
        }

        $product->increment('current_stock', $item->quantity);
    }
}
