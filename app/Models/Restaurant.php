<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'address',
        'owner_id',
        'is_active',
        'menu_public_password_enabled',
        'menu_public_password_hash',
        'menu_logo',
        'menu_header_image',
        'menu_theme',
    ];

    protected $hidden = [
        'menu_public_password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'menu_public_password_enabled' => 'boolean',
            'menu_theme' => 'array',
        ];
    }

    public function verifyMenuPublicPassword(string $plain): bool
    {
        if (! $this->menu_public_password_enabled || $this->menu_public_password_hash === null) {
            return true;
        }

        return password_verify($plain, $this->menu_public_password_hash);
    }

    protected static function booted(): void
    {
        static::creating(function (Restaurant $restaurant) {
            if ($restaurant->menu_theme === null || $restaurant->menu_theme === []) {
                $restaurant->menu_theme = self::defaultMenuTheme();
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultMenuTheme(): array
    {
        return [
            'tagline' => null,
            'footer_note' => null,
            'background' => 'parchment',
            'accent_color' => '#c2410c',
            'font_pair' => 'classic',
            'layout_columns' => 2,
            'ribbon_preset' => 'accent',
            'show_category_images' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedMenuTheme(): array
    {
        return array_merge(self::defaultMenuTheme(), $this->menu_theme ?? []);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'restaurant_id');
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
