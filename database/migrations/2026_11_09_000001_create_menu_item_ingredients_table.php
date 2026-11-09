<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_ingredients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete();

            $table->foreignId('ingredient_id')
                ->constrained('inventory_ingredients')
                ->cascadeOnDelete();

            $table->string('unit_name')->nullable();
            $table->decimal('cost_per_unit', 15, 6)->default(0);
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['menu_item_id', 'ingredient_id']);
            $table->index(['menu_item_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_ingredients');
    }
};
