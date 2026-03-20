<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('number');
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('capacity')->default(4);
            $table->string('shape')->default('rectangle'); // rectangle, square, round
            $table->string('status')->default('free');
            $table->string('qr_token')->nullable()->unique();
            $table->decimal('pos_x', 10, 2)->default(0);
            $table->decimal('pos_y', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(80);
            $table->decimal('height', 10, 2)->default(60);
            $table->decimal('rotation', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
