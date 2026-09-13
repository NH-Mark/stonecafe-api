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
        Schema::table('daily_sales_email_settings', function (Blueprint $table) {
             $table->date('from_date')
                ->default(now()->subDay()->toDateString());

            $table->date('to_date')
                ->default(now()->subDay()->toDateString());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales_email_settings', function (Blueprint $table) {
            //
        });
    }
};
