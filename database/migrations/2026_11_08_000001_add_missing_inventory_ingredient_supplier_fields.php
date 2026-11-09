<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_ingredient_suppliers', function (Blueprint $table) {
            $table->boolean('has_sub_unit')->default(false)->after('buying_unit');
            $table->string('sub_unit_name')->nullable()->after('has_sub_unit');
            $table->decimal('unit_to_sub_unit_quantity', 15, 4)->nullable()->after('sub_unit_name');
            $table->decimal('sub_unit_to_base_quantity', 15, 4)->nullable()->after('unit_to_sub_unit_quantity');
            $table->decimal('net_quantity', 15, 4)->nullable()->after('sub_unit_to_base_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_ingredient_suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'has_sub_unit',
                'sub_unit_name',
                'unit_to_sub_unit_quantity',
                'sub_unit_to_base_quantity',
                'net_quantity',
            ]);
        });
    }
};
