<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notificación en base de datos compatible con el centro de notificaciones de Filament (sin cola).
 */
class KitchenOrderReadyForWaiter extends Notification
{
    public function __construct(
        public string $mesaLabel,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Cocina: pedido listo',
            'body' => 'Mesa '.$this->mesaLabel.' — la cocina terminó los platos.',
            'icon' => 'heroicon-o-check-circle',
            'status' => 'success',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
