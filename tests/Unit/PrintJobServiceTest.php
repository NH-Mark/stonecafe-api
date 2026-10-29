<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Models\Order;
use App\Services\PrintJobService;
use Tests\TestCase;

class PrintJobServiceTest extends TestCase
{
    public function test_it_only_prints_for_main_location_id_or_code(): void
    {
        $service = new PrintJobService();

        $byId = new Order(['location_id' => 1]);
        $this->assertTrue($service->shouldPrintOrder($byId));

        $byCode = new Order(['location_id' => 99]);
        $byCode->setRelation('location', new Location(['code' => 'MAIN']));
        $this->assertTrue($service->shouldPrintOrder($byCode));

        $blocked = new Order(['location_id' => 99]);
        $blocked->setRelation('location', new Location(['code' => 'OTHER']));
        $this->assertFalse($service->shouldPrintOrder($blocked));
    }
}
