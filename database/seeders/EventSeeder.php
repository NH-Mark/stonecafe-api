<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::updateOrCreate(
            [
                'slug' => 'omakase-private-session',
            ],
            [
                'name' => 'Omakase Private Session',
                'description' => 'A private Omakase session featuring a curated coffee experience.',
                'image' => '/images/omakase.jpg',

                'date' => '2026-10-20',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',

                'location' => null,

                'fee' => 150.00,
                'currency' => 'QAR',

                'capacity' => null,

                'is_active' => true,
            ]
        );

        Event::updateOrCreate(
            [
                'slug' => 'brewers-throwdown',
            ],
            [
                'name' => 'Brewers Throwdown',
                'description' => 'A friendly brewing competition bringing coffee brewers together.',
                'image' => '/images/barista.jpg',

                'date' => '2026-10-25',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',

                'location' => null,

                'fee' => 0.00,
                'currency' => 'QAR',

                'capacity' => null,

                'is_active' => true,
            ]
        );
    }
}