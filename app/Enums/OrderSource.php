<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderSource: string
{
    /** Pedido creado por personal (Filament / táctil). */
    case Staff = 'staff';
    /** Pedido originado desde carta con QR de mesa validado en backend. */
    case QrTable = 'qr_table';
    /** Carta pública por slug sin mesa (sin contexto QR). */
    case PublicSlug = 'public_slug';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Personal / POS',
            self::QrTable => 'QR mesa',
            self::PublicSlug => 'Carta pública (sin mesa)',
        };
    }
}
