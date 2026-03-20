<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QrAccessStatus;
use App\Enums\TableStatus;
use App\Services\TableQrService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    protected $table = 'restaurant_tables';

    /** @var string|null Secreto en texto plano solo durante creating (no persistido). */
    public ?string $plainQrSecretOnce = null;

    protected $fillable = [
        'restaurant_id',
        'floor_plan_id',
        'sector_id',
        'number',
        'name',
        'capacity',
        'shape',
        'status',
        'qr_public_uuid',
        'qr_secret_hash',
        'qr_access_status',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'rotation',
        'internal_notes',
        'layout_meta',
        'waiter_call_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TableStatus::class,
            'qr_access_status' => QrAccessStatus::class,
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rotation' => 'decimal:2',
            'layout_meta' => 'array',
            'waiter_call_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (empty($table->qr_public_uuid)) {
                $table->qr_public_uuid = (string) Str::uuid();
            }
            if (empty($table->qr_secret_hash)) {
                $secret = bin2hex(random_bytes(32));
                $table->qr_secret_hash = TableQrService::hashSecret($secret);
                $table->plainQrSecretOnce = $secret;
            }
            if ($table->qr_access_status === null) {
                $table->qr_access_status = QrAccessStatus::Active;
            }

            if (
                empty($table->floor_plan_id)
                && ! empty($table->restaurant_id)
                && Schema::hasTable('floor_plans')
                && Schema::hasColumn('restaurant_tables', 'floor_plan_id')
            ) {
                $floorPlan = FloorPlan::query()->firstOrCreate(
                    [
                        'restaurant_id' => $table->restaurant_id,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Salón principal',
                        'version' => 1,
                        'width' => 1000,
                        'height' => 640,
                        'base_shape' => 'rectangle',
                        'grid_size' => 20,
                        'show_grid' => true,
                        'zones' => [],
                        'fixed_elements' => [],
                    ],
                );

                $table->floor_plan_id = $floorPlan->id;
            }
        });

        static::created(function (RestaurantTable $table): void {
            if ($table->plainQrSecretOnce !== null) {
                Cache::put(
                    'table_qr_plain_once:'.$table->id,
                    $table->plainQrSecretOnce,
                    now()->addMinutes(3),
                );
                $table->plainQrSecretOnce = null;
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? (string) $this->number;
    }

    /**
     * Mesa y local aptos para servir carta y pedidos vía QR (validación backend).
     */
    public function allowsQrMenuAccess(): bool
    {
        if ($this->restaurant === null || ! $this->restaurant->is_active) {
            return false;
        }
        if ($this->qr_access_status !== QrAccessStatus::Active) {
            return false;
        }
        if ($this->qr_secret_hash === null || $this->qr_secret_hash === '') {
            return false;
        }

        return true;
    }

    public function menuUnlockSessionKey(): string
    {
        return 'menu_public_unlock_'.$this->restaurant_id;
    }
}
