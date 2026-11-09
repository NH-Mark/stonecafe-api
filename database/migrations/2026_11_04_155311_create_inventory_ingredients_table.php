<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_ingredients', function (Blueprint $table) {
            $table->id();

            // ingredient, sub_recipe
            $table->string('ingredient_type', 30)->default('ingredient');

            $table->string('name');
            $table->string('sku')->nullable();

            $table->text('description')->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('inventory_ingredient_categories')
                ->nullOnDelete();

            // Stock and recipe measurement unit
            $table->foreignId('measurement_unit_id')
                ->constrained('inventory_measurement_units')
                ->restrictOnDelete();

            // Purchasing details
            $table->string('buying_unit_name')->nullable();

            $table->boolean('has_sub_unit')->default(false);

            // Example: 1 Box = 10 KG
            $table->decimal('buying_unit_quantity', 15, 4)
                ->default(1);

            // Example: 1 Box = 9.5 KG usable
            $table->decimal('net_quantity', 15, 4)
                ->nullable();

            // Last purchase price (cached)
            $table->decimal('last_buying_price', 15, 4)
                ->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('sku');
            $table->index('ingredient_type');
            $table->index('category_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ingredients');
    }
};