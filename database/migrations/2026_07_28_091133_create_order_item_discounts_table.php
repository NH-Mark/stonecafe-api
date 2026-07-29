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
        Schema::create('order_item_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('discount_id')
                ->constrained();


            $table->decimal(
                'amount',
                10,
                2
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_discounts');
    }
};
