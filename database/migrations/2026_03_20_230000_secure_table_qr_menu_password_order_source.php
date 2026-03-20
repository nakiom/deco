<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('menu_public_password_enabled')->default(false)->after('is_active');
            $table->string('menu_public_password_hash')->nullable()->after('menu_public_password_enabled');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->uuid('qr_public_uuid')->nullable()->unique()->after('status');
            $table->string('qr_secret_hash', 64)->nullable()->after('qr_public_uuid');
            $table->string('qr_access_status', 20)->default('active')->after('qr_secret_hash');
        });

        $key = config('app.key');
        $rows = DB::table('restaurant_tables')->select('id', 'qr_token')->get();

        foreach ($rows as $row) {
            $uuid = (string) Str::uuid();

            if (! empty($row->qr_token)) {
                $hash = hash_hmac('sha256', $row->qr_token, $key);
            } else {
                $secret = bin2hex(random_bytes(32));
                $hash = hash_hmac('sha256', $secret, $key);
                Cache::put('table_qr_plain_once:'.$row->id, $secret, now()->addHours(48));
            }

            DB::table('restaurant_tables')->where('id', $row->id)->update([
                'qr_public_uuid' => $uuid,
                'qr_secret_hash' => $hash,
                'qr_access_status' => 'active',
            ]);
        }

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 32)->default('staff')->after('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropColumn(['qr_public_uuid', 'qr_secret_hash', 'qr_access_status']);
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique();
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['menu_public_password_enabled', 'menu_public_password_hash']);
        });
    }
};
