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
        Schema::create('historical_imports', function (Blueprint $table) {
            $table->id();

            $table->string('source'); // sapaad
            $table->string('status')->default('uploaded');
            // uploaded
            // validating
            // validation_failed
            // ready
            // importing
            // completed
            // failed

            $table->string('orders_file_path')->nullable();
            $table->string('items_file_path')->nullable();

            $table->unsignedBigInteger('location_id')->nullable();

            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('valid_orders')->default(0);
            $table->unsignedInteger('invalid_orders')->default(0);
            $table->unsignedInteger('imported_orders')->default(0);

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_imports');
    }
};
