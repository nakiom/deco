<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Debit = 'debit';
    case Credit = 'credit';
    case Transfer = 'transfer';
    case Qr = 'qr';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Debit => 'Débito',
            self::Credit => 'Crédito',
            self::Transfer => 'Transferencia',
            self::Qr => 'QR / billetera',
            self::Other => 'Otro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
