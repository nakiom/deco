<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->boolean('menu_public_ordering_enabled')->default(false)->after('qr_access_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('participant_label')->nullable()->after('notes');
            $table->string('split_mode', 32)->default('individual')->after('participant_label');
            $table->json('shared_with_labels')->nullable()->after('split_mode');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['participant_label', 'split_mode', 'shared_with_labels']);
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropColumn('menu_public_ordering_enabled');
        });
    }
};
