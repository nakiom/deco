<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderItemSplitMode;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

final class SplitBillSummary
{
    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, float> nombre => monto (2 decimales)
     */
    public static function totalsByParticipant(Collection $orders): array
    {
        $totals = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                self::addItemToTotals($item, $totals);
            }
        }

        ksort($totals, SORT_NATURAL | SORT_FLAG_CASE);

        return array_map(fn (float $v): float => round($v, 2), $totals);
    }

    /**
     * @param  array<string, float>  $totals
     */
    public static function sumTotals(array $totals): float
    {
        return round(array_sum($totals), 2);
    }

    /**
     * @param  array<string, float>  $totals
     */
    private static function addItemToTotals(OrderItem $item, array &$totals): void
    {
        $lineTotal = round((float) $item->unit_price * (int) $item->quantity, 2);

        $mode = $item->split_mode instanceof OrderItemSplitMode
            ? $item->split_mode
            : OrderItemSplitMode::tryFrom((string) $item->split_mode) ?? OrderItemSplitMode::Individual;

        if ($mode === OrderItemSplitMode::SharedEqual) {
            $names = is_array($item->shared_with_labels) ? $item->shared_with_labels : [];
            $names = array_values(array_unique(array_filter(array_map(
                static fn ($n): string => trim((string) $n),
                $names
            ), static fn (string $n): bool => $n !== '')));

            $n = count($names);
            if ($n < 2) {
                $totals['Sin asignar'] = ($totals['Sin asignar'] ?? 0) + $lineTotal;

                return;
            }

            $shares = self::splitAmountEqually($lineTotal, $n);
            foreach ($names as $i => $name) {
                $totals[$name] = ($totals[$name] ?? 0) + ($shares[$i] ?? 0);
            }

            return;
        }

        $name = trim((string) $item->participant_label);
        if ($name === '') {
            $name = 'Sin asignar';
        }
        $totals[$name] = ($totals[$name] ?? 0) + $lineTotal;
    }

    /**
     * Reparte un monto en partes iguales; el último absorbe el resto por redondeo.
     *
     * @return array<int, float>
     */
    public static function splitAmountEqually(float $amount, int $parts): array
    {
        if ($parts < 1) {
            return [];
        }

        $cents = (int) round($amount * 100);
        $base = intdiv($cents, $parts);
        $remainder = $cents % $parts;
        $out = [];
        for ($i = 0; $i < $parts; $i++) {
            $extra = $i < $remainder ? 1 : 0;
            $out[$i] = round(($base + $extra) / 100, 2);
        }

        return $out;
    }
}
