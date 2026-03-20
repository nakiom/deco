<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderItemSplitMode: string
{
    case Individual = 'individual';
    case SharedEqual = 'shared_equal';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::SharedEqual => 'Compartido (partes iguales)',
        };
    }
}
