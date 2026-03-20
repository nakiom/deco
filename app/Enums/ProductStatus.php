<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductStatus: string
{
    case Available = 'available';
    case OutOfStock = 'out_of_stock';
    case Paused = 'paused';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::OutOfStock => 'Sin stock',
            self::Paused => 'Pausado',
            self::Hidden => 'Oculto',
        };
    }
}
