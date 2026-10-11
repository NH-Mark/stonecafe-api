<?php

namespace App\Exports;

use App\Models\EventRegistration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventRegistrationsExport implements FromCollection, WithHeadings, WithMapping
{
    private Collection $registrations;
    private array $metadataKeys = [];
    private bool $hasOmakase = false;
    private bool $hasNonOmakase = false;

    public function __construct(
        private readonly int $eventId = 0,
        private readonly string $search = '',
        private readonly array $filters = [],
    ) {
        // Load all matching registrations before headings are generated.
        $this->registrations = $this->baseQuery()->get();

        $this->hasOmakase = $this->registrations->contains(
            fn ($registration) =>
                $registration->registration_type === 'omakase_booking'
        );

        $this->hasNonOmakase = $this->registrations->contains(
            fn ($registration) =>
                $registration->registration_type !== 'omakase_booking'
        );

        // Discover metadata keys across every matching registration.
        $keys = [];

        foreach ($this->registrations as $registration) {
            $metadata = $this->normalizeMetadata($registration->metadata);

            foreach (array_keys($metadata) as $key) {
                $keys[$key] = true;
            }
        }

        $this->metadataKeys = array_keys($keys);
    }

    /**
     * Apply the same search and filters as the index endpoint.
     */
    private function baseQuery()
    {
        $query = EventRegistration::query()
            ->with(['event', 'timeSlot']);

        if ($this->eventId > 0) {
            $query->where('event_id', $this->eventId);
        }

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhereHas('event', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('timeSlot', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        foreach ($this->filters as $filter) {
            $column = $filter['id'] ?? null;
            $value = $filter['value'] ?? null;

            if (!$column || $value === null || $value === '') {
                continue;
            }

            switch ($column) {
                case 'full_name':
                case 'email':
                case 'phone':
                case 'company':
                    $query->where($column, 'like', "%{$value}%");
                    break;

                case 'event_id':
                    // Preserve the current index() behavior.
                    $query->whereHas('event', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%");
                    });
                    break;

                case 'event_time_slot_id':
                case 'status':
                case 'payment_status':
                    $query->where($column, $value);
                    break;
            }
        }

        return $query->latest('id');
    }

    /**
     * Normalize metadata to an associative array.
     */
    private function normalizeMetadata(mixed $metadata): array
    {
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        return is_array($metadata) ? $metadata : [];
    }

    public function collection(): Collection
    {
        return $this->registrations;
    }

    public function headings(): array
    {
        $headings = [
            'Registration ID',
            'Event',
        ];

        if ($this->hasOmakase) {
            $headings[] = 'Session';
            $headings[] = 'Seats';
        } else {
            $headings[] = 'Time Slot';
        }

        $headings = array_merge($headings, [
            'Full Name',
            'Email',
            'Phone',
        ]);

        if ($this->hasNonOmakase) {
            $headings[] = 'Gender';
            $headings[] = 'Company';
        }

        $headings = array_merge($headings, [
            'Registration Status',
            'Selection Status',
            'Payment Status',
            'Payment Amount',
            'Payment Currency',
            'Payment Reference',
            'Paid At',
        ]);

        if (!$this->hasOmakase) {
            $headings[] = 'Seat Count';
        }

        $headings[] = 'Registered At';

        foreach ($this->metadataKeys as $key) {
            $headings[] = $key;
        }

        return $headings;
    }

    public function map($registration): array
    {
        $isOmakase =
            $registration->registration_type === 'omakase_booking';

        $metadata = $this->normalizeMetadata($registration->metadata);
        $session = $registration->timeSlot;

        $sessionLabel = '';

        if ($session) {
            $sessionName = $session->name ?? 'Session';
            $startTime = $session->start_time ?? '';
            $endTime = $session->end_time ?? '';

            $timeRange = trim($startTime . ' - ' . $endTime);

            $sessionLabel = trim(
                $sessionName . ($timeRange !== '' ? ' ' . $timeRange : '')
            );
        }

        $row = [
            $registration->id,
            $registration->event?->name,
        ];

        if ($this->hasOmakase) {
            $row[] = $isOmakase
                ? $sessionLabel
                : ($session?->name ?? '');

            $row[] = $isOmakase
                ? $registration->seat_count
                : '';
        } else {
            $row[] = $session?->name;
        }

        $row = array_merge($row, [
            $registration->full_name,
            $registration->email,
            $registration->phone,
        ]);

        if ($this->hasNonOmakase) {
            $row[] = $isOmakase ? '' : $registration->gender;
            $row[] = $isOmakase ? '' : $registration->company;
        }

        $row = array_merge($row, [
            $registration->status,
            $registration->selection_status,
            $registration->payment_status,
            $registration->payment_amount,
            $registration->payment_currency,
            $registration->payment_reference,
            $registration->paid_at?->format('Y-m-d H:i:s'),
        ]);

        if (!$this->hasOmakase) {
            $row[] = $registration->seat_count;
        }

        $row[] = $registration->created_at?->format('Y-m-d H:i:s');

        foreach ($this->metadataKeys as $key) {
            $value = $metadata[$key] ?? '';

            if (is_array($value) || is_object($value)) {
                $value = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            $row[] = $value;
        }

        return $row;
    }
}