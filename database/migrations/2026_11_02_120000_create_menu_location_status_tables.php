<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_item_location_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(
                ['menu_item_id', 'location_id'],
                'mi_loc_status_unique'
            );
        });

        Schema::create('menu_category_location_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_category_id')
                ->constrained('menu_categories')
                ->cascadeOnDelete();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(
                ['menu_category_id', 'location_id'],
                'mc_loc_status_unique'
            );
        });

        $locationIds = DB::table('locations')->pluck('id');

        if ($locationIds->isEmpty()) {
            return;
        }

        $now = now();

        $itemRows = [];
        foreach (DB::table('menu_items')->select(['id', 'active'])->cursor() as $item) {
            foreach ($locationIds as $locationId) {
                $itemRows[] = [
                    'menu_item_id' => $item->id,
                    'location_id' => $locationId,
                    'active' => (bool) $item->active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($itemRows) >= 1000) {
                    DB::table('menu_item_location_statuses')->insert($itemRows);
                    $itemRows = [];
                }
            }
        }

        if ($itemRows !== []) {
            DB::table('menu_item_location_statuses')->insert($itemRows);
        }

        $categoryRows = [];
        foreach (DB::table('menu_categories')->select(['id', 'active'])->cursor() as $category) {
            foreach ($locationIds as $locationId) {
                $categoryRows[] = [
                    'menu_category_id' => $category->id,
                    'location_id' => $locationId,
                    'active' => (bool) $category->active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($categoryRows) >= 1000) {
                    DB::table('menu_category_location_statuses')->insert($categoryRows);
                    $categoryRows = [];
                }
            }
        }

        if ($categoryRows !== []) {
            DB::table('menu_category_location_statuses')->insert($categoryRows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_category_location_statuses');
        Schema::dropIfExists('menu_item_location_statuses');
    }
};
