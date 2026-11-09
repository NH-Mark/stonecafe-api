<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_ingredient_suppliers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ingredient_id')
                ->constrained('inventory_ingredients')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('inventory_suppliers')
                ->cascadeOnDelete();

            $table->decimal('price', 15, 4)->default(0);

            $table->string('buying_unit')->nullable();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['ingredient_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ingredient_suppliers');
    }
};