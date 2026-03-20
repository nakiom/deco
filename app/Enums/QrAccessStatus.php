<?php

declare(strict_types=1);

namespace App\Enums;

enum QrAccessStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa (QR habilitado)',
            self::Inactive => 'Inactiva',
            self::Suspended => 'Suspendida',
        };
    }
}
