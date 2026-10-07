<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventTimeSlot;

class EventTimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $event = Event::where(
            'slug',
            'omakase-private-session'
        )->firstOrFail();

        $slots = [
            [
                'name' => 'Session 1',
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'capacity' => 5,
            ],
            [
                'name' => 'Session 2',
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'capacity' => 5,
            ],
            [
                'name' => 'Session 3',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'capacity' => 5,
            ],
            [
                'name' => 'Session 4',
                'start_time' => '18:00:00',
                'end_time' => '19:00:00',
                'capacity' => 5,
            ],
        ];

        foreach ($slots as $slot) {
            EventTimeSlot::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $slot['name'],
                ],
                [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'capacity' => $slot['capacity'],
                    'is_active' => true,
                ]
            );
        }
    }
}
