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
        Schema::create('dining_sessions', function (Blueprint $table) {
            $table->id();
                $table->foreignId('table_id')
                ->constrained('restaurant_tables')
                ->restrictOnDelete();

            $table->unsignedInteger('guest_count');

            $table->enum('status', [
                'open',
                'billing',
                'closed',
                'cancelled',
            ])->default('open');

            $table->decimal(
                'subtotal',
                10,
                2
            )->default(0);

            $table->decimal(
                'discount_amount',
                10,
                2
            )->default(0);

            $table->decimal(
                'total',
                10,
                2
            )->default(0);

            $table->timestamp('opened_at')
                ->nullable();

            $table->timestamp('closed_at')
                ->nullable();
            $table->timestamps();

            $table->index([
                'table_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dining_sessions');
    }
};
