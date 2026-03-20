<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'long_description',
        'menu_note',
        'price',
        'image',
        'sku',
        'item_type',
        'status',
        'highlighted',
        'promo_label',
        'promo_style',
        'prep_time_minutes',
        'sort_order',
        'tags',
        'stock_control',
        'current_stock',
        'minimum_stock',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'status' => ProductStatus::class,
            'highlighted' => 'boolean',
            'stock_control' => 'boolean',
            'tags' => 'array',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeForMenu($query)
    {
        return $query
            ->where('status', ProductStatus::Available)
            ->where(function ($q): void {
                $q->where('stock_control', false)
                    ->orWhere('current_stock', '>', 0);
            });
    }

    public function isAvailable(): bool
    {
        if ($this->status === ProductStatus::Available) {
            if ($this->stock_control && $this->current_stock <= 0) {
                return false;
            }

            return true;
        }

        return false;
    }
}
