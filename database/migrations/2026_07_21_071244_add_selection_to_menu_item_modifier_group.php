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
        Schema::table('menu_item_modifier_group', function (Blueprint $table) {
            $table->enum('selection_type', [
                'single',
                'multiple',
            ])
            ->nullable();


            $table->integer('min_selection')
                ->nullable();


            $table->integer('max_selection')
                ->nullable();


            $table->boolean('required')
                ->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_item_modifier_group', function (Blueprint $table) {
            //
        });
    }
};
