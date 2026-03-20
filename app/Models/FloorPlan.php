<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FloorPlan extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'version',
        'is_active',
        'width',
        'height',
        'base_shape',
        'grid_size',
        'show_grid',
        'zones',
        'fixed_elements',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_grid' => 'boolean',
            'zones' => 'array',
            'fixed_elements' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }
}
