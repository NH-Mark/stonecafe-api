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
        Schema::create('historical_import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('historical_import_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('external_order_no');

            $table->string('status')->default('pending');
            // pending
            // valid
            // invalid
            // imported
            // skipped

            $table->decimal('source_total', 12, 2)->nullable();
            $table->decimal('calculated_total', 12, 2)->nullable();

            $table->json('data')->nullable();
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();

            $table->unsignedBigInteger('order_id')->nullable();

            $table->timestamps();

            $table->index(
                ['historical_import_id', 'external_order_no'],
                'hist_import_order_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_import_rows');
    }
};
