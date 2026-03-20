<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Contexto de sesión tras validar QR + secreto en backend (para futuros endpoints de pedido público).
 */
final class MenuQrContext
{
    public static function isValidForTable(int $tableId, int $restaurantId): bool
    {
        $data = session('deco_menu_qr');
        if (! is_array($data)) {
            return false;
        }

        return (int) ($data['table_id'] ?? 0) === $tableId
            && (int) ($data['restaurant_id'] ?? 0) === $restaurantId;
    }

    /**
     * @return array{table_id: int, restaurant_id: int, verified_at: int}|null
     */
    public static function sessionPayload(): ?array
    {
        $data = session('deco_menu_qr');

        return is_array($data)
            && isset($data['table_id'], $data['restaurant_id'], $data['verified_at'])
            ? [
                'table_id' => (int) $data['table_id'],
                'restaurant_id' => (int) $data['restaurant_id'],
                'verified_at' => (int) $data['verified_at'],
            ]
            : null;
    }
}
