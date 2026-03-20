<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemType: string
{
    case Kitchen = 'kitchen';
    case Bar = 'bar';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Kitchen => 'Cocina',
            self::Bar => 'Barra',
            self::Mixed => 'Mixto',
        };
    }
}
