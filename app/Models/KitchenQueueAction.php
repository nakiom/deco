<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenQueueAction extends Model
{
    protected $fillable = [
        'order_item_id',
        'user_id',
        'restaurant_id',
        'from_status',
        'to_status',
        'undone_at',
    ];

    protected function casts(): array
    {
        return [
            'undone_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
