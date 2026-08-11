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
        Schema::create('print_job_orders', function (Blueprint $table) {
               $table->id();

                $table->foreignId('print_job_id')
                    ->constrained('print_jobs')
                    ->cascadeOnDelete();

                $table->foreignId('order_id')
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique([
                    'print_job_id',
                    'order_id',
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_job_orders', function (Blueprint $table) {
            //
        });
    }
};
