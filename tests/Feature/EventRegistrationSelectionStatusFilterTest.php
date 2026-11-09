<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationSelectionStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_registrations_by_selection_status(): void
    {
        $user = User::factory()->create();

        $event = Event::create([
            'name' => 'Spring Expo',
            'slug' => 'spring-expo',
            'description' => 'Test event',
            'date' => '2026-12-15',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'location' => 'Main Hall',
            'fee' => 0,
            'currency' => 'USD',
            'capacity' => 100,
            'is_active' => true,
        ]);

        $slot = EventTimeSlot::create([
            'event_id' => $event->id,
            'name' => 'Morning Slot',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'capacity' => 50,
            'is_active' => true,
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Selected Candidate',
            'email' => 'selected@example.com',
            'phone' => '1111111111',
            'company' => 'Acme',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'event_time_slot_id' => $slot->id,
            'selection_status' => 'selected',
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Not Selected Candidate',
            'email' => 'notselected@example.com',
            'phone' => '2222222222',
            'company' => 'Beta',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'event_time_slot_id' => $slot->id,
            'selection_status' => 'not_selected',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/event-registrations?filters[0][id]=selection_status&filters[0][value]=selected');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $this->assertSame('selected', $response->json('data.0.selection_status'));
    }
}
