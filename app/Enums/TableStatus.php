<?php

declare(strict_types=1);

namespace App\Enums;

enum TableStatus: string
{
    case Free = 'free';
    case Occupied = 'occupied';
    case Reserved = 'reserved';
    case PendingPayment = 'pending_payment';
    case Cleaning = 'cleaning';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Libre',
            self::Occupied => 'Ocupada',
            self::Reserved => 'Reservada',
            self::PendingPayment => 'Pendiente cobro',
            self::Cleaning => 'Limpieza',
            self::Blocked => 'Bloqueada',
        };
    }
}
