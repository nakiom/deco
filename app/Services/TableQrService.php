<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class TableQrService
{
    public static function hashSecret(string $secret): string
    {
        return hash_hmac('sha256', $secret, config('app.key'));
    }

    public function verifySecret(RestaurantTable $table, string $secret): bool
    {
        if ($table->qr_secret_hash === null || $table->qr_secret_hash === '') {
            return false;
        }

        return hash_equals($table->qr_secret_hash, self::hashSecret($secret));
    }

    public function resolveByUuidAndSecret(string $uuid, string $secret): ?RestaurantTable
    {
        $table = RestaurantTable::query()
            ->where('qr_public_uuid', $uuid)
            ->with('restaurant')
            ->first();

        if ($table === null || ! $this->verifySecret($table, $secret)) {
            return null;
        }

        return $table;
    }

    public function resolveByLegacyToken(string $token): ?RestaurantTable
    {
        $hash = self::hashSecret($token);

        return RestaurantTable::query()
            ->where('qr_secret_hash', $hash)
            ->with('restaurant')
            ->first();
    }

    /**
     * Rota el secreto del QR; invalida el anterior. El texto plano queda en cache unos minutos para mostrarlo en el panel.
     */
    public function rotate(RestaurantTable $table): string
    {
        $secret = bin2hex(random_bytes(32));
        $table->qr_secret_hash = self::hashSecret($secret);
        if (empty($table->qr_public_uuid)) {
            $table->qr_public_uuid = (string) Str::uuid();
        }
        $table->save();

        Cache::put('table_qr_plain_once:'.$table->id, $secret, now()->addMinutes(3));

        return $secret;
    }

    public function publicMenuUrl(RestaurantTable $table, string $plainSecret): string
    {
        return route('menu.public', [
            'qrUuid' => $table->qr_public_uuid,
            'secret' => $plainSecret,
        ]);
    }

    public function takePlainSecretFromCache(RestaurantTable $table): ?string
    {
        $key = 'table_qr_plain_once:'.$table->id;
        $secret = Cache::get($key);
        if ($secret !== null) {
            Cache::forget($key);
        }

        return is_string($secret) ? $secret : null;
    }

    public function peekPlainSecretFromCache(RestaurantTable $table): ?string
    {
        $secret = Cache::get('table_qr_plain_once:'.$table->id);

        return is_string($secret) ? $secret : null;
    }
}
