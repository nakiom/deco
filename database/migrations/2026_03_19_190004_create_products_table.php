<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->string('sku')->nullable();
            $table->string('item_type')->default('kitchen'); // kitchen, bar, mixed
            $table->string('status')->default('available');
            $table->boolean('highlighted')->default(false);
            $table->unsignedSmallInteger('prep_time_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('tags')->nullable(); // vegano, picante, sin_tacc, etc.
            $table->boolean('stock_control')->default(false);
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->timestamps();

            $table->unique(['restaurant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
