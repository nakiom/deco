<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floor_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Salón principal');
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('width')->default(1000);
            $table->unsignedSmallInteger('height')->default(640);
            $table->string('base_shape')->default('rectangle');
            $table->unsignedTinyInteger('grid_size')->default(20);
            $table->boolean('show_grid')->default(true);
            $table->json('zones')->nullable();
            $table->json('fixed_elements')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plans');
    }
};
