<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Dev = 'dev';
    case Owner = 'owner';
    case Manager = 'manager';
    case Kitchen = 'kitchen';
    case Bar = 'bar';
    case Waiter = 'waiter';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Dev => 'Desarrollador',
            self::Owner => 'Dueño',
            self::Manager => 'Gerente',
            self::Kitchen => 'Cocina',
            self::Bar => 'Barra',
            self::Waiter => 'Mozo',
            self::Client => 'Cliente',
        };
    }
}
