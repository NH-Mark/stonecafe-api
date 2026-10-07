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
        Schema::table('event_registrations', function (Blueprint $table) {
             $table->foreignId('event_time_slot_id')
                ->nullable()
                ->constrained('event_time_slots')
                ->nullOnDelete();

            $table->unsignedInteger('seat_count')
                ->default(1);
                    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            //
        });
    }
};
