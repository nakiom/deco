<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->foreignId('floor_plan_id')
                ->nullable()
                ->after('restaurant_id')
                ->constrained('floor_plans')
                ->nullOnDelete();
            $table->text('internal_notes')->nullable()->after('rotation');
            $table->json('layout_meta')->nullable()->after('internal_notes');

            $table->index(['restaurant_id', 'floor_plan_id']);
        });

        $restaurantIds = DB::table('restaurant_tables')
            ->select('restaurant_id')
            ->distinct()
            ->pluck('restaurant_id');

        foreach ($restaurantIds as $restaurantId) {
            $floorPlanId = DB::table('floor_plans')->insertGetId([
                'restaurant_id' => $restaurantId,
                'name' => 'Salón principal',
                'version' => 1,
                'is_active' => true,
                'width' => 1000,
                'height' => 640,
                'base_shape' => 'rectangle',
                'grid_size' => 20,
                'show_grid' => true,
                'zones' => json_encode([]),
                'fixed_elements' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('restaurant_tables')
                ->where('restaurant_id', $restaurantId)
                ->whereNull('floor_plan_id')
                ->update([
                    'floor_plan_id' => $floorPlanId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->dropIndex(['restaurant_id', 'floor_plan_id']);
            $table->dropConstrainedForeignId('floor_plan_id');
            $table->dropColumn(['internal_notes', 'layout_meta']);
        });
    }
};
