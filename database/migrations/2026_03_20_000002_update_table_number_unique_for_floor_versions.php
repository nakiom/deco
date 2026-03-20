<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->dropUnique(['restaurant_id', 'number']);
            $table->unique(['restaurant_id', 'floor_plan_id', 'number'], 'restaurant_tables_rest_floor_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->dropUnique('restaurant_tables_rest_floor_number_unique');
            $table->unique(['restaurant_id', 'number']);
        });
    }
};
