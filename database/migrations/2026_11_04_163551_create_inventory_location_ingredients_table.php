<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_location_ingredients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ingredient_id')
                ->constrained('inventory_ingredients')
                ->cascadeOnDelete();

            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();

            $table->decimal('opening_balance', 15, 4)->default(0);

            // Minimum stock quantity before reorder
            $table->decimal('reorder_level', 15, 4)->default(0);

            // Whether stock is tracked at this location
            $table->boolean('track_inventory')->default(true);

            $table->timestamps();

            // One ingredient configuration per location
            $table->unique(['location_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_location_ingredients');
    }
};