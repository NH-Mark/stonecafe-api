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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')
                ->unique();


            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();


            $table->foreignId('order_type_id')
                ->constrained();


            $table->foreignId('order_source_id')
                ->constrained();


            $table->foreignId('table_id')
                ->nullable()
                ->constrained(
                    'restaurant_tables'
                )
                ->nullOnDelete();


            $table->foreignId('cashier_id')
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();


            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'completed',
                'cancelled'

            ])->default('pending');


            $table->enum('payment_status', [

                'unpaid',
                'partial',
                'paid',
                'refunded'

            ])->default('unpaid');



            $table->decimal(
                'subtotal',
                12,
                2
            )->default(0);



            $table->decimal(
                'discount_amount',
                12,
                2
            )->default(0);



            $table->decimal(
                'tax_amount',
                12,
                2
            )->default(0);



            $table->decimal(
                'service_charge',
                12,
                2
            )->default(0);



            $table->decimal(
                'total_amount',
                12,
                2
            )->default(0);



            $table->text('notes')
                ->nullable();



            $table->timestamp('ordered_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
