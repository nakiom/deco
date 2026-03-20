<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case InProgress = 'in_progress';
    /** Cocina terminó todos los platos de cocina del pedido. */
    case KitchenDone = 'kitchen_done';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Sent => 'Enviado',
            self::InProgress => 'En preparación',
            self::KitchenDone => 'Realizado',
            self::Ready => 'Listo',
            self::Delivered => 'Entregado',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelado',
        };
    }
}
