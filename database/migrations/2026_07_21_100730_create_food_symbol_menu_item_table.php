<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_symbol_menu_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('food_symbol_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'menu_item_id',
                'food_symbol_id'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_symbol_menu_item');
    }
};
