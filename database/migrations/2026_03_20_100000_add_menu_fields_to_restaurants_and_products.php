<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('menu_logo')->nullable()->after('is_active');
            $table->string('menu_header_image')->nullable()->after('menu_logo');
            $table->json('menu_theme')->nullable()->after('menu_header_image');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('promo_label')->nullable()->after('highlighted');
            $table->string('promo_style')->nullable()->after('promo_label');
            $table->text('menu_note')->nullable()->after('long_description');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['menu_logo', 'menu_header_image', 'menu_theme']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['promo_label', 'promo_style', 'menu_note']);
        });
    }
};
