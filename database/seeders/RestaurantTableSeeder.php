<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [];

        for ($i = 1; $i <= 10; $i++) {
            $tables[] = [
                'location_id' => 1,
                'name' => 'Table ' . $i,
                'capacity' => 2,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('restaurant_tables')->insert($tables);
    }
}
